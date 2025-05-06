<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendPaymentReceipt;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SendPaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected $payment;
    protected $lease;
    protected $tenant;
    protected $unit;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->tenant = Tenant::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);
        
        $this->unit = Unit::factory()->create([
            'unit_number' => 'A101',
        ]);
        
        $this->lease = Lease::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'monthly_rent' => 1000,
        ]);
        
        $this->payment = Payment::factory()->create([
            'lease_id' => $this->lease->id,
            'amount' => 1000,
            'status' => 'paid',
            'payment_date' => now(),
            'payment_method' => 'credit_card',
            'transaction_id' => 'txn_123456',
        ]);
    }

    /**
     * Test the job processes a payment receipt correctly.
     *
     * @return void
     */
    public function test_job_processes_payment_receipt()
    {
        // Fake the Log facade
        Log::shouldReceive('info')
            ->once()
            ->with('Sending payment receipt email', 
                \Mockery::on(function ($data) {
                    return $data['payment_id'] === $this->payment->id &&
                           $data['amount'] === $this->payment->amount &&
                           $data['tenant_email'] === $this->tenant->email &&
                           $data['tenant_name'] === 'John Doe' &&
                           $data['unit_number'] === 'A101';
                })
            );

        // Execute the job
        $job = new SendPaymentReceipt($this->payment);
        $job->handle();

        // The assertion is in the Log::shouldReceive expectation
    }

    /**
     * Test the job loads relationships correctly.
     *
     * @return void
     */
    public function test_job_loads_relationships()
    {
        // Create a payment without eager loading relationships
        $payment = Payment::find($this->payment->id);
        
        // Verify relationships aren't loaded yet
        $this->assertFalse($payment->relationLoaded('lease'));
        
        // Mock Log to prevent actual logging
        Log::shouldReceive('info')->once();
        
        // Execute the job
        $job = new SendPaymentReceipt($payment);
        $job->handle();
        
        // Verify relationships are now loaded
        $this->assertTrue($payment->relationLoaded('lease'));
        $this->assertTrue($payment->lease->relationLoaded('tenant'));
        $this->assertTrue($payment->lease->relationLoaded('unit'));
    }
}