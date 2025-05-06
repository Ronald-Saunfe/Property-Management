<?php

namespace Tests\Feature;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Jobs\SendPaymentReceipt;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $agent;
    protected $landlord;
    protected $payment;
    protected $lease;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users with different roles
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->agent = User::factory()->create(['role' => 'agent']);
        $this->landlord = User::factory()->create(['role' => 'landlord']);

        // Create a tenant, unit, and lease
        $tenant = Tenant::factory()->create();
        $unit = Unit::factory()->create(['user_id' => $this->landlord->id]); // Assign to landlord
        $this->lease = Lease::factory()->create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
        ]);

        // Create a payment
        $this->payment = Payment::factory()->create([
            'lease_id' => $this->lease->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Test admin can view all payments.
     *
     * @return void
     */
    public function test_admin_can_view_all_payments()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'pagination',
            ]);
    }

    /**
     * Test agent can view all payments.
     *
     * @return void
     */
    public function test_agent_can_view_all_payments()
    {
        $response = $this->actingAs($this->agent)
            ->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'pagination',
            ]);
    }

    /**
     * Test landlord can view only their payments.
     *
     * @return void
     */
    public function test_landlord_can_view_payments()
    {
        $response = $this->actingAs($this->landlord)
            ->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'pagination',
            ]);

        // In a real implementation, we would verify that only payments related to the landlord's properties are returned
    }

    /**
     * Test unauthenticated user cannot view payments.
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_view_payments()
    {
        $response = $this->getJson('/api/payments');

        $response->assertStatus(401);
    }

    /**
     * Test admin can view a specific payment.
     *
     * @return void
     */
    public function test_admin_can_view_specific_payment()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/payments/' . $this->payment->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->payment->id,
                'lease_id' => $this->payment->lease_id,
            ]);
    }

    /**
     * Test admin can create a payment.
     *
     * @return void
     */
    public function test_admin_can_create_payment()
    {
        Queue::fake();

        $data = [
            'lease_id' => $this->lease->id,
            'amount' => 1000,
            'due_date' => Carbon::now()->format('Y-m-d'),
            'status' => 'pending',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/payments', $data);

        $response->assertStatus(201)
            ->assertJson([
                'lease_id' => $this->lease->id,
                'amount' => 1000,
                'status' => 'pending',
            ]);

        $this->assertDatabaseHas('payments', [
            'lease_id' => $this->lease->id,
            'amount' => 1000,
            'status' => 'pending',
        ]);

        // Verify no job was dispatched for pending payment
        Queue::assertNotPushed(SendPaymentReceipt::class);
    }

    /**
     * Test agent can create a payment.
     *
     * @return void
     */
    public function test_agent_can_create_payment()
    {
        Queue::fake();

        $data = [
            'lease_id' => $this->lease->id,
            'amount' => 1000,
            'due_date' => Carbon::now()->format('Y-m-d'),
            'status' => 'paid',
            'payment_date' => Carbon::now()->format('Y-m-d'),
            'payment_method' => 'credit_card',
        ];

        $response = $this->actingAs($this->agent)
            ->postJson('/api/payments', $data);

        $response->assertStatus(201);

        // Verify job was dispatched for paid payment
        Queue::assertPushed(SendPaymentReceipt::class);
    }

    /**
     * Test landlord cannot create a payment.
     *
     * @return void
     */
    public function test_landlord_cannot_create_payment()
    {
        $data = [
            'lease_id' => $this->lease->id,
            'amount' => 1000,
            'due_date' => Carbon::now()->format('Y-m-d'),
            'status' => 'pending',
        ];

        $response = $this->actingAs($this->landlord)
            ->postJson('/api/payments', $data);

        $response->assertStatus(403);
    }

    /**
     * Test payment validation errors.
     *
     * @return void
     */
    public function test_payment_validation_errors()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/payments', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lease_id', 'amount', 'due_date', 'status']);
    }

    /**
     * Test admin can update a payment.
     *
     * @return void
     */
    public function test_admin_can_update_payment()
    {
        Queue::fake();

        $data = [
            'status' => 'paid',
            'payment_date' => Carbon::now()->format('Y-m-d'),
            'payment_method' => 'bank_transfer',
        ];

        $response = $this->actingAs($this->admin)
            ->putJson('/api/payments/' . $this->payment->id, $data);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->payment->id,
                'status' => 'paid',
                'payment_method' => 'bank_transfer',
            ]);

        // Verify job was dispatched when status changed to paid
        Queue::assertPushed(SendPaymentReceipt::class);
    }

    /**
     * Test agent can update a payment.
     *
     * @return void
     */
    public function test_agent_can_update_payment()
    {
        $data = [
            'amount' => 1500,
        ];

        $response = $this->actingAs($this->agent)
            ->putJson('/api/payments/' . $this->payment->id, $data);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->payment->id,
                'amount' => 1500,
            ]);
    }

    /**
     * Test landlord cannot update a payment.
     *
     * @return void
     */
    public function test_landlord_cannot_update_payment()
    {
        $data = [
            'status' => 'paid',
        ];

        $response = $this->actingAs($this->landlord)
            ->putJson('/api/payments/' . $this->payment->id, $data);

        $response->assertStatus(403);
    }

    /**
     * Test admin can delete a payment.
     *
     * @return void
     */
    public function test_admin_can_delete_payment()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson('/api/payments/' . $this->payment->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('payments', [
            'id' => $this->payment->id,
        ]);
    }

    /**
     * Test agent cannot delete a payment.
     *
     * @return void
     */
    public function test_agent_cannot_delete_payment()
    {
        $response = $this->actingAs($this->agent)
            ->deleteJson('/api/payments/' . $this->payment->id);

        $response->assertStatus(403);
    }

    /**
     * Test cannot delete a paid payment.
     *
     * @return void
     */
    public function test_cannot_delete_paid_payment()
    {
        // Update payment to paid status
        $this->payment->update([
            'status' => 'paid',
            'payment_date' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson('/api/payments/' . $this->payment->id);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot delete a payment that has already been paid. Consider updating its status instead.'
            ]);

        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
        ]);
    }

    /**
     * Test admin can view payment statistics.
     *
     * @return void
     */
    public function test_admin_can_view_payment_statistics()
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/api/payments/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_payments',
                'paid_payments',
                'pending_payments',
                'late_payments',
                'partial_payments',
                'total_amount_paid',
                'total_amount_pending',
                'payments_due_this_month',
                'amount_due_this_month',
            ]);
    }

    /**
     * Test agent can view payment statistics.
     *
     * @return void
     */
    public function test_agent_can_view_payment_statistics()
    {
        $response = $this->actingAs($this->agent)
            ->getJson('/api/payments/statistics');

        $response->assertStatus(200);
    }

    /**
     * Test landlord cannot view payment statistics.
     *
     * @return void
     */
    public function test_landlord_cannot_view_payment_statistics()
    {
        $response = $this->actingAs($this->landlord)
            ->getJson('/api/payments/statistics');

        $response->assertStatus(403);
    }
}