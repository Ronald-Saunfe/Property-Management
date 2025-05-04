<?php

namespace Database\Factories;

use App\Models\Lease;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $dueDate = $this->faker->dateTimeBetween('-6 months', '+1 month');
        $paymentDate = clone $dueDate;
        $isPaid = $this->faker->boolean(80);
        
        if ($isPaid) {
            $paymentDate->modify($this->faker->randomElement(['-5 days', '-2 days', '+1 day', '+3 days']));
        } else {
            $paymentDate = null;
        }
        
        return [
            'lease_id' => Lease::factory(),
            'amount' => $this->faker->numberBetween(800, 3000),
            'due_date' => $dueDate,
            'payment_date' => $paymentDate,
            'payment_method' => $isPaid ? $this->faker->randomElement(['credit_card', 'bank_transfer', 'check', 'cash']) : null,
            'transaction_id' => $isPaid ? $this->faker->uuid() : null,
            'status' => $isPaid ? 'paid' : $this->faker->randomElement(['pending', 'late', 'partial']),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }
}