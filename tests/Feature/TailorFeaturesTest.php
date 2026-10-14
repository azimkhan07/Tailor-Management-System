<?php

namespace Tests\Feature;

use App\Models\Leave;
use App\Models\Orders;
use App\Models\Staff;
use App\Models\User;
use App\Models\Holiday;
use App\Models\Customer;
use App\Models\Measurement;
use App\Services\OrderNotifier;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TailorFeaturesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): Customer
    {
        return Customer::create([
            'fullname' => 'Test Customer',
            'address' => 'Street 1',
            'phone' => '9876543210',
            'city' => 'Delhi',
            'email' => 'c@example.com',
            'comment' => '',
            'gender' => 'Male',
        ]);
    }

    private function makeOrder(array $attrs = []): Orders
    {
        return Orders::create(array_merge([
            'customer_id' => $this->customer()->id,
            'description' => 'Chest 40, Waist 34',
            'recieved_on' => '2026-10-14',
            'recieved_by' => 'shop',
            'amount_charged' => 500,
            'amount_paid' => 200,
            'collecting_on' => '2026-10-20',
        ], $attrs));
    }

    public function test_order_gets_auto_number_and_records_measurement(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();

        $this->actingAs($admin)->post('/orders', [
            'customer' => $customer->id,
            'description' => 'Chest 40, Waist 34',
            'recieved_on' => '2026-10-14',
            'receiver' => 'shop',
            'amount_charged' => 500,
            'amount_paid' => 200,
            'collecting_on' => '2026-10-20',
        ])->assertRedirect();

        $order = Orders::first();
        $this->assertNotNull($order->order_no);
        $this->assertMatchesRegularExpression('/^[A-Z]{3}-\d{4}-\d{6}-\d{4}/', $order->order_no);
        $this->assertSame('pending', $order->status);
        $this->assertDatabaseHas('measurements', ['customer_id' => $customer->id]);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'status' => 'pending']);
    }

    public function test_previous_measurements_endpoint(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        Measurement::create(['customer_id' => $customer->id, 'title' => 'Old', 'details' => 'Chest 41']);

        $this->actingAs($admin)
            ->getJson("/customers/{$customer->id}/measurements")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['details' => 'Chest 41']);
    }

    public function test_status_change_records_history_and_completion_time(): void
    {
        $admin = $this->admin();
        $order = $this->makeOrder();

        $this->actingAs($admin)
            ->post("/orders/{$order->id}/status", ['status' => 'completed', 'notes' => 'ready'])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->completed_at);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'status' => 'completed']);
    }

    public function test_employee_only_sees_and_edits_assigned_orders(): void
    {
        $staff = Staff::create(['fullname' => 'Karim', 'address' => 'a', 'gender' => 'Male', 'salary' => '100']);
        $employee = User::factory()->create(['role' => 'employee', 'staff_id' => $staff->id]);

        $mine = $this->makeOrder(['assigned_to' => $staff->id]);
        $other = $this->makeOrder();

        $this->actingAs($employee)->get('/my-work')
            ->assertOk()
            ->assertSee($mine->order_no)
            ->assertDontSee($other->order_no);

        // Employee cannot open an admin-only page.
        $this->actingAs($employee)->get('/users')->assertForbidden();

        // Employee cannot move an order that is not theirs.
        $this->actingAs($employee)
            ->post("/orders/{$other->id}/status", ['status' => 'completed'])
            ->assertForbidden();

        // Employee can move their own order.
        $this->actingAs($employee)
            ->post("/orders/{$mine->id}/status", ['status' => 'in_progress'])
            ->assertRedirect();
        $this->assertSame('in_progress', $mine->fresh()->status);
    }

    public function test_admin_assigns_order_and_employee_can_see_it(): void
    {
        $admin = $this->admin();
        $staff = Staff::create(['fullname' => 'Rahim', 'address' => 'a', 'gender' => 'Male', 'salary' => '100']);
        $order = $this->makeOrder();

        $this->actingAs($admin)
            ->post("/orders/{$order->id}/assign", ['assigned_to' => $staff->id])
            ->assertRedirect();

        $this->assertSame($staff->id, $order->fresh()->assigned_to);
    }

    public function test_leave_apply_and_admin_decision(): void
    {
        $admin = $this->admin();
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)->post('/leaves', [
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-16',
            'reason' => 'wedding',
        ])->assertRedirect();

        $leave = Leave::first();
        $this->assertSame('pending', $leave->status);

        $this->actingAs($employee)->get('/my-calendar')->assertOk();

        $this->actingAs($admin)
            ->post("/leaves/{$leave->id}/decide", ['status' => 'approved'])
            ->assertRedirect();

        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertSame($admin->id, $leave->fresh()->decided_by);
    }

    public function test_holiday_appears_on_calendar(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/holidays', [
            'title' => 'Eid',
            'date' => '2026-10-15',
        ])->assertRedirect();

        $this->assertDatabaseHas('holidays', ['title' => 'Eid']);
        $this->actingAs($admin)->get('/my-calendar?month=2026-10')
            ->assertOk()
            ->assertSee('Eid');
    }

    public function test_invoice_pdf_and_slip_render(): void
    {
        $admin = $this->admin();
        $order = $this->makeOrder();

        $this->actingAs($admin)->get("/orders/{$order->id}/slip")
            ->assertOk()
            ->assertSee($order->order_no);

        $this->actingAs($admin)->get("/orders/{$order->id}/invoice")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_notification_message_contains_order_number(): void
    {
        $order = $this->makeOrder();
        $notifier = app(OrderNotifier::class);

        $this->assertStringContainsString($order->order_no, $notifier->message($order));
        $this->assertStringContainsString('wa.me', $notifier->whatsappLink($order));
    }

    public function test_recording_a_payment_updates_balance_and_history(): void
    {
        $admin = $this->admin();
        $order = $this->makeOrder(['amount_charged' => 500, 'amount_paid' => 200]);

        $this->actingAs($admin)
            ->post("/orders/{$order->id}/pay", ['amount' => 100])
            ->assertRedirect();

        $this->assertSame(300.0, (float) $order->fresh()->amount_paid);
        $this->assertSame(200.0, $order->fresh()->balance);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'notes' => 'Payment received: 100. Balance: 200',
        ]);
    }
}
