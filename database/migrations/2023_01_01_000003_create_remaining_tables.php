<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create units table
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->string('unit_number', 50);
            $table->string('floor_plan', 100)->nullable();
            $table->float('square_feet')->nullable();
            $table->integer('bedrooms')->nullable();
            $table->float('bathrooms')->nullable();
            $table->decimal('monthly_rent', 10, 2);
            $table->enum('status', ['available', 'occupied', 'maintenance', 'reserved', 'vacant']);
            $table->text('features')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // Create tenants table
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email');
            $table->string('phone', 20);
            $table->date('date_of_birth')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('occupation')->nullable();
            $table->decimal('income', 10, 2)->nullable();
            $table->integer('credit_score')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending', 'evicted', 'former']);
            $table->timestamps();
        });

        // Create leases table
        Schema::create('leases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('monthly_rent', 10, 2);
            $table->decimal('security_deposit', 10, 2);
            $table->string('lease_type', 50);
            $table->integer('payment_day');
            $table->enum('status', ['active', 'pending', 'expired', 'terminated']);
            $table->string('document_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Create lease_tenants table
        Schema::create('lease_tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['lease_id', 'tenant_id']);
        });

        // Create payments table
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lease_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->date('payment_date')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('transaction_id', 100)->nullable();
            $table->enum('status', ['paid', 'pending', 'late', 'partial']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Create property_managers table
        Schema::create('property_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_primary')->default(false);
            $table->string('company_name')->nullable();
            $table->string('license_number')->nullable();
            $table->integer('years_of_experience')->nullable();
            $table->text('bio')->nullable();
            $table->timestamps();
            $table->unique(['property_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order to avoid foreign key constraints
        Schema::dropIfExists('property_managers');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('lease_tenants');
        Schema::dropIfExists('leases');
        Schema::dropIfExists('tenants');
        Schema::dropIfExists('units');
    }
};