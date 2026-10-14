<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PageSmokeTest extends TestCase
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

    public function test_all_admin_pages_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $pages = [
            '/', '/dashboard', '/orders', '/customers', '/users',
            '/designations', '/staff', '/expense-categories', '/expenses',
            '/income-categories', '/income', '/cloth-types', '/measurement-parts',
            '/settings', '/my-work', '/my-calendar', '/user-profile',
        ];

        foreach ($pages as $page) {
            $response = $this->actingAs($admin)->get($page);
            $this->assertContains(
                $response->getStatusCode(),
                [200, 302],
                "Admin page {$page} returned {$response->getStatusCode()}"
            );
        }
    }

    public function test_employee_pages_render(): void
    {
        $staff = Staff::create(['fullname' => 'Emp', 'address' => 'a', 'gender' => 'Male', 'salary' => '100']);
        $employee = User::factory()->create(['role' => 'employee', 'staff_id' => $staff->id]);

        foreach (['/my-work', '/my-calendar', '/user-profile', '/orders'] as $page) {
            $response = $this->actingAs($employee)->get($page);
            $this->assertContains(
                $response->getStatusCode(),
                [200, 302, 403],
                "Employee page {$page} returned {$response->getStatusCode()}"
            );
        }

        // Dashboard redirects employees.
        $this->actingAs($employee)->get('/dashboard')->assertRedirect('/my-work');
    }
}
