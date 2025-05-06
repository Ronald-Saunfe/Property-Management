<?php

namespace Tests\Unit;

use App\Http\Controllers\PaymentController;
use App\Http\Requests\PaymentRequest;
use App\Jobs\SendPaymentReceipt;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $payment;
    protected $lease;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new PaymentController();
        
        // Create a tenant, unit, and lease
        $tenant = Tenant::factory()->create();
        $unit = Unit::factory()->create();
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
     * Test store method creates a payment and dispatches job when status is paid.
     *
     * @return void
     */
    public function test_store_method_creates_payment_and_dispatches_job_when_paid()
    {
        Queue::fake();

        // Mock the PaymentRequest
        $request = Mockery::mock(PaymentRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'lease_id' => $this->lease->id,
            'amount' => 1000,
            'due_date' => Carbon::now(),
            'status' => 'paid',
            'payment_date' => Carbon::now(),
            'payment_method' => 'credit_card',
        ]);

        $response = $this->controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertDatabaseHas('payments', [
            'lease_id' => $this->lease->id,
            'amount' => 1000,
            'status' => 'paid',
        ]);

        // Verify job was dispatched for paid payment
        Queue::assertPushed(SendPaymentReceipt::class);
    }

    /**
     * Test store method creates a payment but doesn't dispatch job when status is not paid.
     *
     * @return void
     */
    public function test_store_method_creates_payment_without_dispatching_job_when_not_paid()
    {
        Queue::fake();

        // Mock the PaymentRequest
        $request = Mockery::mock(PaymentRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'lease_id' => $this->lease->id,
            'amount' => 1000,
            'due_date' => Carbon::now(),
            'status' => 'pending',
        ]);

        $response = $this->controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertDatabaseHas('payments', [
            'lease_id' => $this->lease->id,
            'amount' => 1000,
            'status' => 'pending',
        ]);

        // Verify no job was dispatched for pending payment
        Queue::assertNotPushed(SendPaymentReceipt::class);
    }

    /**
     * Test update method updates a payment and dispatches job when status changes to paid.
     *
     * @return void
     */
    public function test_update_method_updates_payment_and_dispatches_job_when_status_changes_to_paid()
    {
        Queue::fake();

        // Mock the PaymentRequest
        $request = Mockery::mock(PaymentRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'status' => 'paid',
            'payment_date' => Carbon::now(),
            'payment_method' => 'bank_transfer',
        ]);

        $response = $this->controller->update($request, $this->payment->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'status' => 'paid',
            'payment_method' => 'bank_transfer',
        ]);

        // Verify job was dispatched when status changed to paid
        Queue::assertPushed(SendPaymentReceipt::class);
    }

    /**
     * Test update method updates a payment but doesn't dispatch job when status doesn't change to paid.
     *
     * @return void
     */
    public function test_update_method_updates_payment_without_dispatching_job_when_status_not_changed_to_paid()
    {
        Queue::fake();

        // First set the payment to paid
        $this->payment->update(['status' => 'paid']);

        // Mock the PaymentRequest
        $request = Mockery::mock(PaymentRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'amount' => 1500,
            'payment_method' => 'check',
        ]);

        $response = $this->controller->update($request, $this->payment->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
            'amount' => 1500,
            'payment_method' => 'check',
            'status' => 'paid',
        ]);

        // Verify no job was dispatched since status was already paid
        Queue::assertNotPushed(SendPaymentReceipt::class);
    }

    /**
     * Test destroy method deletes a payment.
     *
     * @return void
     */
    public function test_destroy_method_deletes_payment()
    {
        $response = $this->controller->destroy($this->payment->id);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertDatabaseMissing('payments', [
            'id' => $this->payment->id,
        ]);
    }

    /**
     * Test destroy method returns error for paid payment.
     *
     * @return void
     */
    public function test_destroy_method_returns_error_for_paid_payment()
    {
        // Update payment to paid status
        $this->payment->update([
            'status' => 'paid',
            'payment_date' => Carbon::now(),
        ]);

        $response = $this->controller->destroy($this->payment->id);

        $this->assertEquals(422, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Cannot delete a payment that has already been paid. Consider updating its status instead.', $responseData['message']);
        
        $this->assertDatabaseHas('payments', [
            'id' => $this->payment->id,
        ]);
    }

    /**
     * Test show method returns a payment with relationships.
     *
     * @return void
     */
    public function test_show_method_returns_payment_with_relationships()
    {
        $response = $this->controller->show($this->payment->id);

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals($this->payment->id, $responseData['id']);
        $this->assertEquals($this->payment->lease_id, $responseData['lease_id']);
        $this->assertArrayHasKey('lease', $responseData);
    }

    /**
     * Test statistics method returns payment statistics.
     *
     * @return void
     */
    public function test_statistics_method_returns_payment_statistics()
    {
        // Create additional payments with different statuses
        Payment::factory()->create(['status' => 'paid']);
        Payment::factory()->create(['status' => 'late']);
        Payment::factory()->create(['status' => 'partial']);

        $response = $this->controller->statistics();

        $this->assertEquals(200, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        
        $this->assertArrayHasKey('total_payments', $responseData);
        $this->assertArrayHasKey('paid_payments', $responseData);
        $this->assertArrayHasKey('pending_payments', $responseData);
        $this->assertArrayHasKey('late_payments', $responseData);
        $this->assertArrayHasKey('partial_payments', $responseData);
        $this->assertArrayHasKey('total_amount_paid', $responseData);
        $this->assertArrayHasKey('total_amount_pending', $responseData);
        $this->assertArrayHasKey('payments_due_this_month', $responseData);
        $this->assertArrayHasKey('amount_due_this_month', $responseData);
        
        // Verify counts are correct
        $this->assertEquals(4, $responseData['total_payments']);
        $this->assertEquals(1, $responseData['paid_payments']);
        $this->assertEquals(1, $responseData['pending_payments']);
        $this->assertEquals(1, $responseData['late_payments']);
        $this->assertEquals(1, $responseData['partial_payments']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}