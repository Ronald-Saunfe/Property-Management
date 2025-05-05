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
     * @param  \App\Http\Requests\PaymentRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PaymentRequest $request)
    {
        $validated = $request->validated();

        // Check if lease exists
        $lease = Lease::findOrFail($validated['lease_id']);

        $payment = Payment::create($validated);

        // Load relationships for the response
        $payment->load(['lease', 'lease.tenant', 'lease.unit']);

        return response()->json($payment, 201);
    }

    /**
     * Display the specified payment.
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
     * @param  \App\Http\Requests\PaymentRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(PaymentRequest $request, $id)
    {
        $payment = Payment::findOrFail($id);
        $validated = $request->validated();

        $payment->update($validated);

        // Load relationships for the response
        $payment->load(['lease', 'lease.tenant', 'lease.unit']);

        return response()->json($payment);
    }

    /**
     * Remove the specified payment from storage.
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