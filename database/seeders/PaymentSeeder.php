<?php

namespace Database\Seeders;

use App\Models\Lease;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        // Create payment history for each active lease
        Lease::where('status', 'active')->each(function ($lease) {
            // Create 6 months of payment history
            for ($i = 5; $i >= 0; $i--) {
                $dueDate = Carbon::now()->subMonths($i)->setDay($lease->payment_day);
                
                // 80% chance of payment being made
                $isPaid = rand(1, 100) <= 80;
                $paymentDate = $isPaid ? $dueDate->copy()->addDays(rand(-3, 5)) : null;
                
                Payment::create([
                    'lease_id' => $lease->id,
                    'amount' => $lease->monthly_rent,
                    'due_date' => $dueDate,
                    'payment_date' => $paymentDate,
                    'payment_method' => $isPaid ? array_rand(array_flip(['credit_card', 'bank_transfer', 'check', 'cash'])) : null,
                    'transaction_id' => $isPaid ? uniqid('txn-') : null,
                    'status' => $isPaid ? 'paid' : (Carbon::now()->gt($dueDate) ? 'late' : 'pending'),
                    'notes' => rand(1, 10) > 8 ? 'Payment note ' . rand(1, 100) : null,
                ]);
            }
        });
    }
}