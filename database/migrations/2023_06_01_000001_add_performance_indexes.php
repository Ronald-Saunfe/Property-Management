<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddPerformanceIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add indexes to the payments table
        Schema::table('payments', function (Blueprint $table) {
            // Add indexes to frequently filtered columns
            $table->index('lease_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index('due_date');
            $table->index('payment_date');
            
            // Add composite indexes for common query patterns
            $table->index(['lease_id', 'status']);
            $table->index(['due_date', 'status']);
        });

        // Add indexes to the leases table
        Schema::table('leases', function (Blueprint $table) {
            $table->index('unit_id');
            $table->index('tenant_id');
            $table->index('status');
            $table->index('lease_type');
            $table->index('start_date');
            $table->index('end_date');
            
            // Composite indexes
            $table->index(['status', 'end_date']);
            $table->index(['unit_id', 'status']);
        });

        // Add indexes to the tenants table
        Schema::table('tenants', function (Blueprint $table) {
            $table->index('status');
            $table->index(['first_name', 'last_name']);
            $table->index('email');
            $table->index('phone');
            
            // Add fulltext index for search if using MySQL 5.7+
            if (config('database.default') === 'mysql') {
                DB::statement('ALTER TABLE tenants ADD FULLTEXT search_index (first_name, last_name, email, occupation)');
            }
        });

        // Add indexes to the lease_tenants table
        Schema::table('lease_tenants', function (Blueprint $table) {
            $table->index(['lease_id', 'tenant_id']);
            $table->index('is_primary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove indexes from the payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['lease_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['due_date']);
            $table->dropIndex(['payment_date']);
            $table->dropIndex(['lease_id', 'status']);
            $table->dropIndex(['due_date', 'status']);
        });

        // Remove indexes from the leases table
        Schema::table('leases', function (Blueprint $table) {
            $table->dropIndex(['unit_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['lease_type']);
            $table->dropIndex(['start_date']);
            $table->dropIndex(['end_date']);
            $table->dropIndex(['status', 'end_date']);
            $table->dropIndex(['unit_id', 'status']);
        });

        // Remove indexes from the tenants table
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['first_name', 'last_name']);
            $table->dropIndex(['email']);
            $table->dropIndex(['phone']);
            
            // Remove fulltext index if using MySQL
            if (config('database.default') === 'mysql') {
                DB::statement('ALTER TABLE tenants DROP INDEX search_index');
            }
        });

        // Remove indexes from the lease_tenants table
        Schema::table('lease_tenants', function (Blueprint $table) {
            $table->dropIndex(['lease_id', 'tenant_id']);
            $table->dropIndex(['is_primary']);
        });
    }
}