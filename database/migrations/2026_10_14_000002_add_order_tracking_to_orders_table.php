<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_no')->nullable()->after('customer_id');
            $table->string('status')->default('pending')->after('description');
            $table->foreignId('assigned_to')->nullable()->after('status')
                ->constrained('staff')->nullOnDelete();
            $table->timestamp('completed_at')->nullable()->after('collecting_on');
            $table->timestamp('status_updated_at')->nullable()->after('completed_at');
        });

        // Back-fill an order number for orders created before this feature.
        $brand = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', config('app.name', 'TAIL')), 0, 3));
        if (strlen($brand) < 3) {
            $brand = str_pad($brand, 3, 'X');
        }

        DB::table('orders')->orderBy('id')->get()->each(function ($order) use ($brand) {
            $at = $order->created_at ? strtotime($order->created_at) : time();
            $base = sprintf(
                '%s-%s-%s-%s',
                $brand,
                date('dm', $at),
                date('His', $at),
                date('Y', $at)
            );

            $no = $base;
            $i = 1;
            while (DB::table('orders')->where('order_no', $no)->exists()) {
                $no = $base . '-' . (++$i);
            }

            DB::table('orders')->where('id', $order->id)->update([
                'order_no' => $no,
                'status_updated_at' => $order->created_at,
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unique('order_no');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['order_no']);
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropColumn(['order_no', 'status', 'completed_at', 'status_updated_at']);
        });
    }
};
