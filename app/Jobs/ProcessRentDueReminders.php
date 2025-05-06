<?php

namespace App\Jobs;

use App\Models\Lease;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRentDueReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of days before the due date to send reminders.
     *
     * @var int
     */
    protected $daysBeforeDue;

    /**
     * Create a new job instance.
     *
     * @param int $daysBeforeDue
     * @return void
     */
    public function __construct(int $daysBeforeDue = 5)
    {
        $this->daysBeforeDue = $daysBeforeDue;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Get the target date for which we're sending reminders
        $targetDate = Carbon::now()->addDays($this->daysBeforeDue);
        
        // Find all active leases where the payment day matches the target date's day
        $leases = Lease::where('status', 'active')
            ->where('payment_day', $targetDate->day)
            ->with(['tenant', 'unit', 'unit.property'])
            ->get();
        
        Log::info('Processing rent due reminders', [
            'target_date' => $targetDate->toDateString(),
            'leases_count' => $leases->count(),
        ]);
        
        foreach ($leases as $lease) {
            // In a real application, we would send an actual email here
            // For now, we'll simulate with a log message
            Log::info('Sending rent due reminder', [
                'lease_id' => $lease->id,
                'due_date' => Carbon::now()->setDay($lease->payment_day)->toDateString(),
                'amount' => $lease->monthly_rent,
                'tenant_email' => $lease->tenant->email,
                'tenant_name' => $lease->tenant->first_name . ' ' . $lease->tenant->last_name,
                'unit_number' => $lease->unit->unit_number,
                'property_name' => $lease->unit->property->name ?? 'Unknown Property',
            ]);
            
            // In a real implementation, you would dispatch individual email jobs:
            // SendRentDueReminderEmail::dispatch($lease);
        }
    }
}