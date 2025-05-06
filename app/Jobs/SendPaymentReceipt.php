<?php

namespace App\Jobs;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPaymentReceipt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The payment instance.
     *
     * @var \App\Models\Payment
     */
    protected $payment;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\Payment  $payment
     * @return void
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Load necessary relationships
        $this->payment->load(['lease', 'lease.tenant', 'lease.unit']);
        
        $tenant = $this->payment->lease->tenant;
        $unit = $this->payment->lease->unit;
        
        // In a real application, we would send an actual email here
        // For now, we'll simulate with a log message
        Log::info('Sending payment receipt email', [
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'payment_date' => $this->payment->payment_date,
            'tenant_email' => $tenant->email,
            'tenant_name' => $tenant->first_name . ' ' . $tenant->last_name,
            'unit_number' => $unit->unit_number,
            'property_name' => $unit->property->name ?? 'Unknown Property',
        ]);
        
        // In a real implementation, you would use Laravel's Mail facade:
        // Mail::to($tenant->email)->send(new PaymentReceiptMail($this->payment));
    }
}