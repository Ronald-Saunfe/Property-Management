<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Lease;
use Illuminate\Http\Request;
use App\Http\Requests\PaymentRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Optimized version of the PaymentController with performance improvements
 * This is a sample implementation to demonstrate profiling improvements
 */
class OptimizedPaymentController extends Controller
{
    /**
     * Display a paginated, filtered, and sorted listing of payments with performance optimizations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {   
        try {
            // Generate a unique cache key based on all request parameters
            $cacheKey = 'payments_' . md5(json_encode($request->all()));
            
            // Try to get from cache first, or execute the query and cache the result
            return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($request) {
                // Start with a base query
                $query = Payment::query();
                
                // Only load relationships if needed
                $relationships = [];
                
                // Default relationships or if explicitly requested
                if ($request->has('include_relationships') || !$request->has('exclude_relationships')) {
                    // Determine which relationships to load
                    if ($request->has('include_lease') || !$request->has('exclude_lease')) {
                        $relationships[] = 'lease';
                    }
                    
                    if ($request->has('include_tenant') || !$request->has('exclude_tenant')) {
                        $relationships[] = 'lease.tenant';
                    }
                    
                    if ($request->has('include_unit') || !$request->has('exclude_unit')) {
                        $relationships[] = 'lease.unit';
                    }
                }
                
                // Apply relationships if any
                if (!empty($relationships)) {
                    $query->with($relationships);
                }
                
                // Select only necessary columns for better performance
                $query->select([
                    'id', 'lease_id', 'amount', 'due_date', 'payment_date',
                    'payment_method', 'transaction_id', 'status', 'notes',
                    'created_at', 'updated_at'
                ]);
                
                // Filtering - with query optimization
                if ($request->has('lease_id')) {
                    $query->where('lease_id', $request->lease_id);
                }
                
                if ($request->has('status')) {
                    $query->where('status', $request->status);
                }
                
                if ($request->has('payment_method')) {
                    $query->where('payment_method', $request->payment_method);
                }
                
                // Use whereRaw for range queries to leverage database indexes better
                if ($request->has('amount_min') || $request->has('amount_max')) {
                    if ($request->has('amount_min')) {
                        $query->where('amount', '>=', $request->amount_min);
                    }
                    
                    if ($request->has('amount_max')) {
                        $query->where('amount', '<=', $request->amount_max);
                    }
                }
                
                // Date range filtering
                if ($request->has('due_date_from') || $request->has('due_date_to')) {
                    if ($request->has('due_date_from')) {
                        $query->where('due_date', '>=', $request->due_date_from);
                    }
                    
                    if ($request->has('due_date_to')) {
                        $query->where('due_date', '<=', $request->due_date_to);
                    }
                }
                
                if ($request->has('payment_date_from') || $request->has('payment_date_to')) {
                    if ($request->has('payment_date_from')) {
                        $query->where('payment_date', '>=', $request->payment_date_from);
                    }
                    
                    if ($request->has('payment_date_to')) {
                        $query->where('payment_date', '<=', $request->payment_date_to);
                    }
                }
                
                // Optimized search - use database indexes more effectively
                if ($request->has('search')) {
                    $search = $request->search;
                    
                    // Use a more efficient search approach
                    $query->where(function (Builder $query) use ($search, $relationships) {
                        // Direct fields in the payments table
                        $query->where('notes', 'like', "%{$search}%")
                              ->orWhere('transaction_id', 'like', "%{$search}%");
                              
                        // Check if tenant relationship is being loaded
                        $hasTenantRelation = in_array('lease.tenant', $relationships) || 
                                            (in_array('lease', $relationships) && isset($request) && $request->has('include_tenant'));
                        
                        if ($hasTenantRelation) {
                            $query->orWhereHas('lease.tenant', function (Builder $query) use ($search) {
                                $query->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%");
                            });
                        }
                        
                        // Check if unit relationship is being loaded
                        $hasUnitRelation = in_array('lease.unit', $relationships) || 
                                          (in_array('lease', $relationships) && isset($request) && $request->has('include_unit'));
                        
                        if ($hasUnitRelation) {
                            $query->orWhereHas('lease.unit', function (Builder $query) use ($search) {
                                $query->where('unit_number', 'like', "%{$search}%");
                            });
                        }
                    });
                }
                
                // Sorting with validation
                $sortField = $request->input('sort_by', 'due_date');
                $sortDirection = $request->input('sort_direction', 'desc');
                
                // Validate sort field to prevent SQL injection
                $allowedSortFields = [
                    'id', 'lease_id', 'amount', 'due_date', 'payment_date',
                    'payment_method', 'status', 'created_at', 'updated_at'
                ];
                
                if (in_array($sortField, $allowedSortFields)) {
                    $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
                } else {
                    $query->orderBy('due_date', 'desc');
                }
                
                // Pagination with validation
                $perPage = (int) $request->input('per_page', 15);
                $perPage = min(max($perPage, 5), 100); // Ensure between 5 and 100
                
                // Use cursor pagination for better performance with large datasets
                if ($request->has('use_cursor') && $request->use_cursor) {
                    $payments = $query->cursorPaginate($perPage);
                } else {
                    $payments = $query->paginate($perPage);
                }
                
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
            });
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Payment index error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'message' => 'An error occurred while fetching payments',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Create a database migration to add indexes for better performance
     * This is a sample method to demonstrate what would be in a migration file
     */
    private function sampleMigration()
    {
        // This would be in a migration file
        /*
        Schema::table('payments', function (Blueprint $table) {
            // Add indexes to frequently filtered columns
            $table->index('lease_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index('due_date');
            $table->index('payment_date');
            
            // Add composite indexes for common query patterns
            $table->index(['lease_id', 'status']);
            $table->index(['due_date', 'status']);
        });
        */
    }
}