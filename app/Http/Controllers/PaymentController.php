<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Lease;
use Illuminate\Http\Request;
use App\Http\Requests\PaymentRequest;
use Illuminate\Database\Eloquent\Builder;

class PaymentController extends Controller
{
    /**
     * Display a paginated, filtered, and sorted listing of payments.
     * 
     * @OA\Get(
     *     path="/payments",
     *     operationId="getPaymentsList",
     *     tags={"Payments"},
     *     summary="Get list of payments",
     *     description="Returns paginated list of payments with filtering and sorting options",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="lease_id",
     *         in="query",
     *         description="Filter by lease ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by payment status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"paid", "pending", "late", "partial"})
     *     ),
     *     @OA\Parameter(
     *         name="payment_method",
     *         in="query",
     *         description="Filter by payment method",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="amount_min",
     *         in="query",
     *         description="Filter by minimum amount",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="amount_max",
     *         in="query",
     *         description="Filter by maximum amount",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="due_date_from",
     *         in="query",
     *         description="Filter by due date (from)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="due_date_to",
     *         in="query",
     *         description="Filter by due date (to)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="payment_date_from",
     *         in="query",
     *         description="Filter by payment date (from)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="payment_date_to",
     *         in="query",
     *         description="Filter by payment date (to)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by notes, transaction ID, tenant name, or unit number",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_direction",
     *         in="query",
     *         description="Direction to sort by",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", format="int32")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="lease_id", type="integer"),
     *                 @OA\Property(property="amount", type="number", format="float"),
     *                 @OA\Property(property="due_date", type="string", format="date"),
     *                 @OA\Property(property="payment_date", type="string", format="date", nullable=true),
     *                 @OA\Property(property="payment_method", type="string", nullable=true),
     *                 @OA\Property(property="transaction_id", type="string", nullable=true),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="notes", type="string", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="lease", type="object"),
     *                 @OA\Property(property="lease.tenant", type="object"),
     *                 @OA\Property(property="lease.unit", type="object")
     *             )),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="to", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = Payment::with(['lease', 'lease.tenant', 'lease.unit']);

            // Filtering
            if ($request->has('lease_id')) {
                $query->where('lease_id', $request->lease_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->has('amount_min')) {
                $query->where('amount', '>=', $request->amount_min);
            }

            if ($request->has('amount_max')) {
                $query->where('amount', '<=', $request->amount_max);
            }

            if ($request->has('due_date_from')) {
                $query->where('due_date', '>=', $request->due_date_from);
            }

            if ($request->has('due_date_to')) {
                $query->where('due_date', '<=', $request->due_date_to);
            }

            if ($request->has('payment_date_from')) {
                $query->where('payment_date', '>=', $request->payment_date_from);
            }

            if ($request->has('payment_date_to')) {
                $query->where('payment_date', '<=', $request->payment_date_to);
            }

            // Search by notes or transaction_id
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function (Builder $query) use ($search) {
                    $query->where('notes', 'like', "%{$search}%")
                        ->orWhere('transaction_id', 'like', "%{$search}%")
                        ->orWhereHas('lease', function (Builder $query) use ($search) {
                            $query->whereHas('tenant', function (Builder $query) use ($search) {
                                $query->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('unit', function (Builder $query) use ($search) {
                                $query->where('unit_number', 'like', "%{$search}%");
                            });
                        });
                });
            }

            // Sorting
            $sortField = $request->input('sort_by', 'due_date');
            $sortDirection = $request->input('sort_direction', 'desc');
            
            // Validate sort field to prevent SQL injection
            $allowedSortFields = [
                'id', 'lease_id', 'amount', 'due_date', 'payment_date', 
                'payment_method', 'transaction_id', 'status', 'created_at', 'updated_at'
            ];
            
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
            } else {
                $query->orderBy('due_date', 'desc');
            }

            // Pagination
            $perPage = (int) $request->input('per_page', 15);
            $payments = $query->paginate($perPage);

            return response()->json([
                'data' => $payments->items(),
                'pagination' => [
                    'total' => $payments->total(),
                    'per_page' => $payments->perPage(),
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'from' => $payments->firstItem() ?? 0,
                    'to' => $payments->lastItem() ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching payments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created payment in storage.
     *
     * @OA\Post(
     *     path="/payments",
     *     operationId="storePayment",
     *     tags={"Payments"},
     *     summary="Store new payment",
     *     description="Creates a new payment and returns it",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lease_id", "amount", "due_date", "status"},
     *             @OA\Property(property="lease_id", type="integer", description="ID of the lease"),
     *             @OA\Property(property="amount", type="number", format="float", description="Payment amount"),
     *             @OA\Property(property="due_date", type="string", format="date", description="Due date for payment"),
     *             @OA\Property(property="payment_date", type="string", format="date", description="Date payment was made", nullable=true),
     *             @OA\Property(property="payment_method", type="string", description="Method of payment", nullable=true),
     *             @OA\Property(property="transaction_id", type="string", description="Transaction ID reference", nullable=true),
     *             @OA\Property(property="status", type="string", description="Payment status (paid, pending, late, partial)"),
     *             @OA\Property(property="notes", type="string", description="Additional notes", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="lease_id", type="integer"),
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="payment_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="payment_method", type="string", nullable=true),
     *             @OA\Property(property="transaction_id", type="string", nullable=true),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="notes", type="string", nullable=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="lease", type="object"),
     *             @OA\Property(property="lease.tenant", type="object"),
     *             @OA\Property(property="lease.unit", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * @param  \App\Http\Requests\PaymentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PaymentRequest $request)
    {
        $validated = $request->validated();

        // Check if lease exists
        $lease = Lease::findOrFail($validated['lease_id']);

        $payment = Payment::create($validated);

        // If payment status is 'paid', dispatch job to send payment receipt
        if ($payment->status === 'paid') {
            \App\Jobs\SendPaymentReceipt::dispatch($payment);
        }

        // Load relationships for the response
        $payment->load(['lease', 'lease.tenant', 'lease.unit']);

        return response()->json($payment, 201);
    }

    /**
     * Display the specified payment.
     *
     * @OA\Get(
     *     path="/payments/{id}",
     *     operationId="getPaymentById",
     *     tags={"Payments"},
     *     summary="Get payment information",
     *     description="Returns payment details by ID",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="lease_id", type="integer"),
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="payment_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="payment_method", type="string", nullable=true),
     *             @OA\Property(property="transaction_id", type="string", nullable=true),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="notes", type="string", nullable=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="lease", type="object"),
     *             @OA\Property(property="lease.tenant", type="object"),
     *             @OA\Property(property="lease.unit", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $payment = Payment::with(['lease', 'lease.tenant', 'lease.unit'])->findOrFail($id);
        return response()->json($payment);
    }

    /**
     * Update the specified payment in storage.
     *
     * @OA\Put(
     *     path="/payments/{id}",
     *     operationId="updatePayment",
     *     tags={"Payments"},
     *     summary="Update payment",
     *     description="Updates an existing payment and returns it",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="lease_id", type="integer", description="ID of the lease"),
     *             @OA\Property(property="amount", type="number", format="float", description="Payment amount"),
     *             @OA\Property(property="due_date", type="string", format="date", description="Due date for payment"),
     *             @OA\Property(property="payment_date", type="string", format="date", description="Date payment was made", nullable=true),
     *             @OA\Property(property="payment_method", type="string", description="Method of payment", nullable=true),
     *             @OA\Property(property="transaction_id", type="string", description="Transaction ID reference", nullable=true),
     *             @OA\Property(property="status", type="string", description="Payment status (paid, pending, late, partial)"),
     *             @OA\Property(property="notes", type="string", description="Additional notes", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="lease_id", type="integer"),
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="payment_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="payment_method", type="string", nullable=true),
     *             @OA\Property(property="transaction_id", type="string", nullable=true),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="notes", type="string", nullable=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="lease", type="object"),
     *             @OA\Property(property="lease.tenant", type="object"),
     *             @OA\Property(property="lease.unit", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * @param  \App\Http\Requests\PaymentRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(PaymentRequest $request, $id)
    {
        $payment = Payment::findOrFail($id);
        $oldStatus = $payment->status;
        $validated = $request->validated();

        $payment->update($validated);

        // Check if payment status changed to 'paid' and dispatch email job
        if ($oldStatus !== 'paid' && $payment->status === 'paid') {
            // Dispatch job to send payment receipt email
            \App\Jobs\SendPaymentReceipt::dispatch($payment);
        }

        // Load relationships for the response
        $payment->load(['lease', 'lease.tenant', 'lease.unit']);

        return response()->json($payment);
    }

    /**
     * Remove the specified payment from storage.
     *
     * @OA\Delete(
     *     path="/payments/{id}",
     *     operationId="deletePayment",
     *     tags={"Payments"},
     *     summary="Delete payment",
     *     description="Deletes a payment",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Payment ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Payment deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete payment",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot delete a payment that has already been paid. Consider updating its status instead.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        
        // Check if payment is already paid
        if ($payment->status === 'paid' && $payment->payment_date !== null) {
            return response()->json([
                'message' => 'Cannot delete a payment that has already been paid. Consider updating its status instead.'
            ], 422);
        }
        
        $payment->delete();

        return response()->json(null, 204);
    }

    /**
     * Get payment statistics.
     *
     * @OA\Get(
     *     path="/payments/statistics",
     *     operationId="getPaymentStatistics",
     *     tags={"Payments"},
     *     summary="Get payment statistics",
     *     description="Returns statistics about payments including counts by status and amounts",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_payments", type="integer"),
     *             @OA\Property(property="paid_payments", type="integer"),
     *             @OA\Property(property="pending_payments", type="integer"),
     *             @OA\Property(property="late_payments", type="integer"),
     *             @OA\Property(property="partial_payments", type="integer"),
     *             @OA\Property(property="total_amount_paid", type="number", format="float"),
     *             @OA\Property(property="total_amount_pending", type="number", format="float"),
     *             @OA\Property(property="payments_due_this_month", type="integer"),
     *             @OA\Property(property="amount_due_this_month", type="number", format="float")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * @return \Illuminate\Http\Response
     */
    public function statistics()
    {
        $stats = [
            'total_payments' => Payment::count(),
            'paid_payments' => Payment::where('status', 'paid')->count(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'late_payments' => Payment::where('status', 'late')->count(),
            'partial_payments' => Payment::where('status', 'partial')->count(),
            'total_amount_paid' => Payment::where('status', 'paid')->sum('amount'),
            'total_amount_pending' => Payment::whereIn('status', ['pending', 'late'])->sum('amount'),
            'payments_due_this_month' => Payment::whereMonth('due_date', now()->month)
                ->whereYear('due_date', now()->year)
                ->count(),
            'amount_due_this_month' => Payment::whereMonth('due_date', now()->month)
                ->whereYear('due_date', now()->year)
                ->sum('amount'),
        ];

        return response()->json($stats);
    }
}