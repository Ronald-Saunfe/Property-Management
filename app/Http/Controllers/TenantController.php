<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use App\Http\Requests\TenantRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TenantController extends Controller
{
    /**
     * Display a paginated, filtered, and sorted listing of tenants.
     * 
     * @OA\Get(
     *     path="/tenants",
     *     operationId="getTenantsList",
     *     tags={"Tenants"},
     *     summary="Get list of tenants",
     *     description="Returns paginated list of tenants with filtering and sorting options",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="first_name",
     *         in="query",
     *         description="Filter by first name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="last_name",
     *         in="query",
     *         description="Filter by last name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="Filter by email",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="Filter by phone number",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "inactive", "pending", "evicted"})
     *     ),
     *     @OA\Parameter(
     *         name="income_min",
     *         in="query",
     *         description="Filter by minimum income",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="income_max",
     *         in="query",
     *         description="Filter by maximum income",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="credit_score_min",
     *         in="query",
     *         description="Filter by minimum credit score",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="credit_score_max",
     *         in="query",
     *         description="Filter by maximum credit score",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="date_of_birth_from",
     *         in="query",
     *         description="Filter by date of birth (from)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_of_birth_to",
     *         in="query",
     *         description="Filter by date of birth (to)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name, email, phone, occupation, or emergency contact name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string", enum={"id", "first_name", "last_name", "email", "phone", "date_of_birth", "occupation", "income", "credit_score", "status", "created_at", "updated_at"})
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
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="first_name", type="string"),
     *                     @OA\Property(property="last_name", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="phone", type="string"),
     *                     @OA\Property(property="date_of_birth", type="string", format="date"),
     *                     @OA\Property(property="occupation", type="string"),
     *                     @OA\Property(property="income", type="number"),
     *                     @OA\Property(property="credit_score", type="integer"),
     *                     @OA\Property(property="status", type="string", enum={"active", "inactive", "pending", "evicted"}),
     *                     @OA\Property(property="emergency_contact_name", type="string"),
     *                     @OA\Property(property="emergency_contact_phone", type="string"),
     *                     @OA\Property(property="emergency_contact_relationship", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 type="object",
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
            // Generate a unique cache key based on all request parameters
            $cacheKey = 'tenants_' . md5(json_encode($request->all()));
            
            // Try to get from cache first, or execute the query and cache the result
            return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($request) {
                // Only select the columns we need for the listing
                $query = Tenant::select([
                    'id', 'first_name', 'last_name', 'email', 'phone', 
                    'date_of_birth', 'occupation', 'income', 'credit_score', 
                    'status', 'emergency_contact_name', 'emergency_contact_phone', 
                    'created_at', 'updated_at'
                ]);

                // Filtering
                if ($request->has('first_name')) {
                    $query->where('first_name', 'like', "%{$request->first_name}%");
                }

                if ($request->has('last_name')) {
                    $query->where('last_name', 'like', "%{$request->last_name}%");
                }

                if ($request->has('email')) {
                    $query->where('email', 'like', "%{$request->email}%");
                }

                if ($request->has('phone')) {
                    $query->where('phone', 'like', "%{$request->phone}%");
                }

                if ($request->has('status')) {
                    $query->where('status', $request->status);
                }

                if ($request->has('income_min')) {
                    $query->where('income', '>=', $request->income_min);
                }

                if ($request->has('income_max')) {
                    $query->where('income', '<=', $request->income_max);
                }

                if ($request->has('credit_score_min')) {
                    $query->where('credit_score', '>=', $request->credit_score_min);
                }

                if ($request->has('credit_score_max')) {
                    $query->where('credit_score', '<=', $request->credit_score_max);
                }

                if ($request->has('date_of_birth_from')) {
                    $query->whereDate('date_of_birth', '>=', $request->date_of_birth_from);
                }

                if ($request->has('date_of_birth_to')) {
                    $query->whereDate('date_of_birth', '<=', $request->date_of_birth_to);
                }

                // Search by name, email, phone, or occupation
                if ($request->has('search')) {
                    $search = $request->search;
                    $query->where(function (Builder $query) use ($search) {
                        $query->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('occupation', 'like', "%{$search}%")
                            ->orWhere('emergency_contact_name', 'like', "%{$search}%");
                    });
                }

                // Sorting
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

                // Pagination
                $perPage = (int) $request->input('per_page', 15);
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
            return response()->json([
                'message' => 'An error occurred while fetching tenants',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created tenant in storage.
     *
     * @OA\Post(
     *     path="/tenants",
     *     operationId="storeTenant",
     *     tags={"Tenants"},
     *     summary="Store new tenant",
     *     description="Creates a new tenant and returns it",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email", "phone", "status"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="occupation", type="string", example="Software Engineer"),
     *             @OA\Property(property="income", type="number", example=75000),
     *             @OA\Property(property="credit_score", type="integer", example=720),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "pending", "evicted"}, example="active"),
     *             @OA\Property(property="emergency_contact_name", type="string", example="Jane Doe"),
     *             @OA\Property(property="emergency_contact_phone", type="string", example="+1987654321"),
     *             @OA\Property(property="emergency_contact_relationship", type="string", example="Spouse")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tenant created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="date_of_birth", type="string", format="date"),
     *             @OA\Property(property="occupation", type="string"),
     *             @OA\Property(property="income", type="number"),
     *             @OA\Property(property="credit_score", type="integer"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="emergency_contact_name", type="string"),
     *             @OA\Property(property="emergency_contact_phone", type="string"),
     *             @OA\Property(property="emergency_contact_relationship", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
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
     * @param  \App\Http\Requests\TenantRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TenantRequest $request)
    {
        // Use database transaction for data integrity
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();
            $tenant = Tenant::create($validated);
        
            // More targeted cache invalidation - only clear index cache
            Cache::forget('tenants_index');
            // Clear any pattern-based cache that might include this tenant
            Cache::flush('tenants_*');
        
            return response()->json($tenant, 201);
        });
    }

    /**
     * Display the specified tenant.
     *
     * @OA\Get(
     *     path="/tenants/{id}",
     *     operationId="getTenantById",
     *     tags={"Tenants"},
     *     summary="Get tenant information",
     *     description="Returns tenant details with associated leases",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Tenant ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="date_of_birth", type="string", format="date"),
     *             @OA\Property(property="occupation", type="string"),
     *             @OA\Property(property="income", type="number"),
     *             @OA\Property(property="credit_score", type="integer"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="emergency_contact_name", type="string"),
     *             @OA\Property(property="emergency_contact_phone", type="string"),
     *             @OA\Property(property="emergency_contact_relationship", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="leases",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="property_id", type="integer"),
     *                     @OA\Property(property="unit_id", type="integer"),
     *                     @OA\Property(property="start_date", type="string", format="date"),
     *                     @OA\Property(property="end_date", type="string", format="date"),
     *                     @OA\Property(property="rent_amount", type="number"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tenant not found"
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
        // Cache individual tenant records for 30 minutes
        return Cache::remember('tenant_'.$id, now()->addMinutes(30), function () use ($id) {
            // Use eager loading to load related data in a single query
            $tenant = Tenant::with(['leases', 'leases.unit', 'leases.unit.property'])->findOrFail($id);
            return response()->json($tenant);
        });
    }

    /**
     * Update the specified tenant in storage.
     *
     * @OA\Put(
     *     path="/tenants/{id}",
     *     operationId="updateTenant",
     *     tags={"Tenants"},
     *     summary="Update tenant",
     *     description="Updates an existing tenant and returns it",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Tenant ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="phone", type="string", example="+1234567890"),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="occupation", type="string", example="Software Engineer"),
     *             @OA\Property(property="income", type="number", example=75000),
     *             @OA\Property(property="credit_score", type="integer", example=720),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive", "pending", "evicted"}, example="active"),
     *             @OA\Property(property="emergency_contact_name", type="string", example="Jane Doe"),
     *             @OA\Property(property="emergency_contact_phone", type="string", example="+1987654321"),
     *             @OA\Property(property="emergency_contact_relationship", type="string", example="Spouse")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tenant updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="date_of_birth", type="string", format="date"),
     *             @OA\Property(property="occupation", type="string"),
     *             @OA\Property(property="income", type="number"),
     *             @OA\Property(property="credit_score", type="integer"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="emergency_contact_name", type="string"),
     *             @OA\Property(property="emergency_contact_phone", type="string"),
     *             @OA\Property(property="emergency_contact_relationship", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tenant not found"
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
     * @param  \App\Http\Requests\TenantRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(TenantRequest $request, Tenant $tenant)
    {
        // Use database transaction for data integrity
        return DB::transaction(function () use ($request, $tenant) {
            $validated = $request->validated();
            $tenant->update($validated);
    
            // Clear specific tenant cache
            Cache::forget('tenant_'.$tenant->id);
            // Clear any pattern-based cache that might include this tenant
            Cache::flush('tenants_*');
    
            return response()->json($tenant);
        });
    }

    /**
     * Remove the specified tenant from storage.
     *
     * @OA\Delete(
     *     path="/tenants/{id}",
     *     operationId="deleteTenant",
     *     tags={"Tenants"},
     *     summary="Delete tenant",
     *     description="Deletes a tenant if they have no active or pending leases",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Tenant ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Tenant deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tenant not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete tenant with active or pending leases"
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
    public function destroy(Tenant $tenant)
    {
        // Use database transaction for data integrity
        return DB::transaction(function () use ($tenant) {
            // Check if tenant has active leases
            $activeLeases = $tenant->leases()->whereIn('status', ['active', 'pending'])->count();
            if ($activeLeases > 0) {
                return response()->json([
                    'message' => 'Cannot delete tenant with active or pending leases. Change lease status first or update tenant status to inactive.'
                ], 422);
            }
            
            $tenant->delete();
    
            // Clear specific tenant cache
            Cache::forget('tenant_'.$tenant->id);
            // Clear any pattern-based cache that might include this tenant
            Cache::flush('tenants_*');
    
            return response()->json(null, 204);
        });
    }

    /**
     * Add a statistics method to provide tenant insights
     * 
     * @OA\Get(
     *     path="/tenants/statistics",
     *     operationId="getTenantStatistics",
     *     tags={"Tenants"},
     *     summary="Get tenant statistics",
     *     description="Returns statistics about tenants including counts by status, average income, etc.",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total_count", type="integer"),
     *             @OA\Property(property="status_counts", type="object"),
     *             @OA\Property(property="avg_income", type="number"),
     *             @OA\Property(property="avg_credit_score", type="number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - User does not have required role"
     *     )
     * )
     *
     * @return \Illuminate\Http\Response
     */
    public function statistics()
    {
        // Cache statistics for 1 hour
        return Cache::remember('tenant_statistics', now()->addHour(), function () {
            $totalCount = Tenant::count();
            
            // Get counts by status
            $statusCounts = Tenant::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();
            
            // Calculate averages
            $avgIncome = Tenant::avg('income');
            $avgCreditScore = Tenant::avg('credit_score');
            
            return response()->json([
                'total_count' => $totalCount,
                'status_counts' => $statusCounts,
                'avg_income' => round($avgIncome, 2),
                'avg_credit_score' => round($avgCreditScore, 0)
            ]);
        });
    }
}