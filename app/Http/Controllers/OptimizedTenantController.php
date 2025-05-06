<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use App\Http\Requests\TenantRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
/**
 * Optimized version of the TenantController with performance improvements
 * This is a sample implementation to demonstrate profiling improvements
 */
class OptimizedTenantController extends Controller
{
    /**
     * Display a paginated, filtered, and sorted listing of tenants with performance optimizations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {   
        try {
            // Generate a unique cache key based on all request parameters
            // Use cache tags for better cache management
            $cacheKey = 'tenants_' . md5(json_encode($request->all()));
            
            // Try to get from cache first, or execute the query and cache the result
            return Cache::tags(['tenants', 'listings'])->remember($cacheKey, now()->addMinutes(15), function () use ($request) {
                // Start with a base query and select only necessary columns
                $query = Tenant::select([
                    'id', 'first_name', 'last_name', 'email', 'phone', 
                    'date_of_birth', 'occupation', 'income', 'credit_score', 
                    'status', 'emergency_contact_name', 'emergency_contact_phone', 
                    'created_at', 'updated_at'
                ]);
                
                // Filtering with optimized queries
                if ($request->has('first_name')) {
                    $query->where('first_name', 'like', "{$request->first_name}%"); // Use prefix search for better index usage
                }
                
                if ($request->has('last_name')) {
                    $query->where('last_name', 'like', "{$request->last_name}%"); // Use prefix search for better index usage
                }
                
                if ($request->has('email')) {
                    $query->where('email', 'like', "{$request->email}%"); // Use prefix search for better index usage
                }
                
                if ($request->has('phone')) {
                    $query->where('phone', 'like', "{$request->phone}%"); // Use prefix search for better index usage
                }
                
                if ($request->has('status')) {
                    $query->where('status', $request->status);
                }
                
                // Use whereRaw for range queries to leverage database indexes better
                if ($request->has('income_min') || $request->has('income_max')) {
                    if ($request->has('income_min')) {
                        $query->where('income', '>=', $request->income_min);
                    }
                    
                    if ($request->has('income_max')) {
                        $query->where('income', '<=', $request->income_max);
                    }
                }
                
                if ($request->has('credit_score_min') || $request->has('credit_score_max')) {
                    if ($request->has('credit_score_min')) {
                        $query->where('credit_score', '>=', $request->credit_score_min);
                    }
                    
                    if ($request->has('credit_score_max')) {
                        $query->where('credit_score', '<=', $request->credit_score_max);
                    }
                }
                
                if ($request->has('date_of_birth_from') || $request->has('date_of_birth_to')) {
                    if ($request->has('date_of_birth_from')) {
                        $query->where('date_of_birth', '>=', $request->date_of_birth_from);
                    }
                    
                    if ($request->has('date_of_birth_to')) {
                        $query->where('date_of_birth', '<=', $request->date_of_birth_to);
                    }
                }
                
                // Optimized search using full-text search when available
                if ($request->has('search')) {
                    $search = $request->search;
                    
                    // Use full-text search if available (MySQL 5.7+)
                    if (config('database.default') === 'mysql' && $this->hasFullTextIndex()) {
                        // Use MySQL's MATCH AGAINST for better performance
                        $query->whereRaw(
                            "MATCH(first_name, last_name, email, occupation) AGAINST(? IN BOOLEAN MODE)", 
                            ['+' . $search . '*']
                        );
                    } else {
                        // Fall back to LIKE searches with optimized approach
                        $query->where(function (Builder $query) use ($search) {
                            // Use individual column searches for better index usage
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('occupation', 'like', "%{$search}%")
                                ->orWhere('emergency_contact_name', 'like', "%{$search}%");
                        });
                    }
                }
                
                // Sorting with validation
                $sortField = $request->input('sort_by', 'created_at');
                $sortDirection = $request->input('sort_direction', 'desc');
                
                // Validate sort field to prevent SQL injection
                $allowedSortFields = [
                    'id', 'first_name', 'last_name', 'email', 'phone', 'date_of_birth',
                    'occupation', 'income', 'credit_score', 'status', 'created_at', 'updated_at'
                ];
                
                if (in_array($sortField, $allowedSortFields)) {
                    $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
                } else {
                    $query->orderBy('created_at', 'desc');
                }
                
                // Pagination with validation
                $perPage = (int) $request->input('per_page', 15);
                $perPage = min(max($perPage, 5), 100); // Ensure between 5 and 100
                
                $tenants = $query->paginate($perPage);
                
                return response()->json([
                    'data' => $tenants->items(),
                    'pagination' => [
                        'total' => $tenants->total(),
                        'per_page' => $tenants->perPage(),
                        'current_page' => $tenants->currentPage(),
                        'last_page' => $tenants->lastPage(),
                        'from' => $tenants->firstItem() ?? 0,
                        'to' => $tenants->lastItem() ?? 0,
                    ],
                ]);
            });
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Tenant index error: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'message' => 'An error occurred while fetching tenants',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    /**
     * Check if the tenants table has a full-text index
     * 
     * @return bool
     */
    private function hasFullTextIndex()
    {
        try {
            // Cache this check to avoid repeated queries
            return Cache::remember('tenants_has_fulltext_index', now()->addDay(), function () {
                if (config('database.default') === 'mysql') {
                    $indexes = DB::select("SHOW INDEX FROM tenants WHERE Index_type = 'FULLTEXT'");
                    return count($indexes) > 0;
                }
                return false;
            });
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Clear tenant-related caches when a tenant is updated
     * This would be called from update/store/destroy methods
     */
    private function clearTenantCaches()
    {
        // Clear all tenant-related caches
        Cache::tags(['tenants'])->flush();
    }
}