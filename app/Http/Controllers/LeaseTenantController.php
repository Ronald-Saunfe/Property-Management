<?php

namespace App\Http\Controllers;

use App\Models\LeaseTenant;
use App\Models\Lease;
use App\Models\Tenant;
use Illuminate\Http\Request;
use App\Http\Requests\LeaseTenantRequest;
use Illuminate\Database\Eloquent\Builder;

class LeaseTenantController extends Controller
{
    /**
     * Display a paginated, filtered, and sorted listing of lease tenants.
     * 
     * @OA\Get(
     *     path="/lease-tenants",
     *     operationId="getLeaseTenantsList",
     *     tags={"Lease Tenants"},
     *     summary="Get list of lease tenants",
     *     description="Returns paginated list of lease tenants with filtering and sorting options",
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
     *         name="tenant_id",
     *         in="query",
     *         description="Filter by tenant ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="is_primary",
     *         in="query",
     *         description="Filter by primary tenant status",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by tenant name or lease information",
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
     *                 @OA\Property(property="tenant_id", type="integer"),
     *                 @OA\Property(property="is_primary", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="lease", type="object"),
     *                 @OA\Property(property="tenant", type="object")
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
            $query = LeaseTenant::with(['lease', 'tenant']);

            // Filtering
            if ($request->has('lease_id')) {
                $query->where('lease_id', $request->lease_id);
            }

            if ($request->has('tenant_id')) {
                $query->where('tenant_id', $request->tenant_id);
            }

            if ($request->has('is_primary')) {
                $query->where('is_primary', filter_var($request->is_primary, FILTER_VALIDATE_BOOLEAN));
            }

            // Search by tenant name or lease information
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function (Builder $query) use ($search) {
                    $query->whereHas('tenant', function (Builder $query) use ($search) {
                        $query->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('lease', function (Builder $query) use ($search) {
                        $query->whereHas('unit', function (Builder $query) use ($search) {
                            $query->where('unit_number', 'like', "%{$search}%");
                        });
                    });
                });
            }

            // Sorting
            $sortField = $request->input('sort_by', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            
            // Validate sort field to prevent SQL injection
            $allowedSortFields = [
                'id', 'lease_id', 'tenant_id', 'is_primary', 'created_at', 'updated_at'
            ];
            
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = (int) $request->input('per_page', 15);
            $leasetenants = $query->paginate($perPage);

            return response()->json([
                'data' => $leasetenants->items(),
                'pagination' => [
                    'total' => $leasetenants->total(),
                    'per_page' => $leasetenants->perPage(),
                    'current_page' => $leasetenants->currentPage(),
                    'last_page' => $leasetenants->lastPage(),
                    'from' => $leasetenants->firstItem() ?? 0,
                    'to' => $leasetenants->lastItem() ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching lease tenants',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created lease tenant in storage.
     *
     * @OA\Post(
     *     path="/lease-tenants",
     *     operationId="storeLeaseTenant",
     *     tags={"Lease Tenants"},
     *     summary="Store new lease tenant",
     *     description="Creates a new lease tenant relationship and returns it",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"lease_id", "tenant_id"},
     *             @OA\Property(property="lease_id", type="integer", description="ID of the lease"),
     *             @OA\Property(property="tenant_id", type="integer", description="ID of the tenant"),
     *             @OA\Property(property="is_primary", type="boolean", description="Whether this tenant is the primary tenant on the lease")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lease tenant created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="lease_id", type="integer"),
     *             @OA\Property(property="tenant_id", type="integer"),
     *             @OA\Property(property="is_primary", type="boolean"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="lease", type="object"),
     *             @OA\Property(property="tenant", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or tenant already assigned",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This tenant is already assigned to this lease.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * @param  \App\Http\Requests\LeaseTenantRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(LeaseTenantRequest $request)
    {
        $validated = $request->validated();

        // Check if this tenant is already assigned to this lease
        $existingAssignment = LeaseTenant::where('lease_id', $validated['lease_id'])
            ->where('tenant_id', $validated['tenant_id'])
            ->first();

        if ($existingAssignment) {
            return response()->json([
                'message' => 'This tenant is already assigned to this lease.'
            ], 422);
        }

        // If this is a primary tenant, update any existing primary tenants for this lease
        if (isset($validated['is_primary']) && $validated['is_primary']) {
            LeaseTenant::where('lease_id', $validated['lease_id'])
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $leaseTenant = LeaseTenant::create($validated);

        // Load relationships for the response
        $leaseTenant->load(['lease', 'tenant']);

        return response()->json($leaseTenant, 201);
    }

    public function show($id)
    {
        $leaseTenant = LeaseTenant::with(['lease', 'tenant'])->findOrFail($id);
        return response()->json($leaseTenant);
    }

    public function update(LeaseTenantRequest $request, $id)
    {
        $leaseTenant = LeaseTenant::findOrFail($id);
        $validated = $request->validated();

        // If changing lease_id or tenant_id, check for existing assignment
        if ((isset($validated['lease_id']) && $validated['lease_id'] != $leaseTenant->lease_id) ||
            (isset($validated['tenant_id']) && $validated['tenant_id'] != $leaseTenant->tenant_id)) {
            
            $existingAssignment = LeaseTenant::where('lease_id', $validated['lease_id'] ?? $leaseTenant->lease_id)
                ->where('tenant_id', $validated['tenant_id'] ?? $leaseTenant->tenant_id)
                ->where('id', '!=', $id)
                ->first();

            if ($existingAssignment) {
                return response()->json([
                    'message' => 'This tenant is already assigned to this lease.'
                ], 422);
            }
        }

        // If setting as primary, update any existing primary tenants for this lease
        if (isset($validated['is_primary']) && $validated['is_primary'] && !$leaseTenant->is_primary) {
            LeaseTenant::where('lease_id', $validated['lease_id'] ?? $leaseTenant->lease_id)
                ->where('id', '!=', $id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $leaseTenant->update($validated);

        // Load relationships for the response
        $leaseTenant->load(['lease', 'tenant']);

        return response()->json($leaseTenant);
    }

    public function destroy($id)
    {
        $leaseTenant = LeaseTenant::findOrFail($id);
        
        // Check if this is the only tenant for the lease
        $tenantCount = LeaseTenant::where('lease_id', $leaseTenant->lease_id)->count();
        
        if ($tenantCount <= 1) {
            return response()->json([
                'message' => 'Cannot remove the only tenant from a lease. Delete the lease instead.'
            ], 422);
        }
        
        // Check if this is the primary tenant
        if ($leaseTenant->is_primary) {
            return response()->json([
                'message' => 'Cannot remove the primary tenant. Assign another tenant as primary first.'
            ], 422);
        }
        
        $leaseTenant->delete();

        return response()->json(null, 204);
    }

    /**
     * Display the specified lease tenant.
     *
     * @OA\Get(
     *     path="/lease-tenants/{id}",
     *     operationId="getLeaseTenantById",
     *     tags={"Lease Tenants"},
     *     summary="Get lease tenant information",
     *     description="Returns lease tenant details by ID",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Lease Tenant ID",
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
     *             @OA\Property(property="tenant_id", type="integer"),
     *             @OA\Property(property="is_primary", type="boolean"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="lease", type="object"),
     *             @OA\Property(property="tenant", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lease tenant not found"
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

    /**
     * Update the specified lease tenant in storage.
     *
     * @OA\Put(
     *     path="/lease-tenants/{id}",
     *     operationId="updateLeaseTenant",
     *     tags={"Lease Tenants"},
     *     summary="Update lease tenant",
     *     description="Updates an existing lease tenant relationship and returns it",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Lease Tenant ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="lease_id", type="integer", description="ID of the lease"),
     *             @OA\Property(property="tenant_id", type="integer", description="ID of the tenant"),
     *             @OA\Property(property="is_primary", type="boolean", description="Whether this tenant is the primary tenant on the lease")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lease tenant updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="lease_id", type="integer"),
     *             @OA\Property(property="tenant_id", type="integer"),
     *             @OA\Property(property="is_primary", type="boolean"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="lease", type="object"),
     *             @OA\Property(property="tenant", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lease tenant not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or tenant already assigned",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="This tenant is already assigned to this lease.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * @param  \App\Http\Requests\LeaseTenantRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    /**
     * Remove the specified lease tenant from storage.
     *
     * @OA\Delete(
     *     path="/lease-tenants/{id}",
     *     operationId="deleteLeaseTenant",
     *     tags={"Lease Tenants"},
     *     summary="Delete lease tenant",
     *     description="Deletes a lease tenant relationship",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Lease Tenant ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Lease tenant deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lease tenant not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete lease tenant with dependencies",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot remove the only tenant from a lease. Delete the lease instead.")
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

    /**
     * Get statistics about lease tenants.
     *
     * @OA\Get(
     *     path="/lease-tenants/statistics",
     *     operationId="getLeaseTenantStatistics",
     *     tags={"Lease Tenants"},
     *     summary="Get lease tenant statistics",
     *     description="Returns statistics about lease tenants including counts by primary status",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="primary_tenants", type="integer"),
     *             @OA\Property(property="secondary_tenants", type="integer"),
     *             @OA\Property(property="leases_with_multiple_tenants", type="integer")
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
            'total' => LeaseTenant::count(),
            'primary_tenants' => LeaseTenant::where('is_primary', true)->count(),
            'secondary_tenants' => LeaseTenant::where('is_primary', false)->count(),
            'leases_with_multiple_tenants' => Lease::whereHas('tenants', function ($query) {
                $query->selectRaw('lease_id, count(*) as tenant_count')
                    ->groupBy('lease_id')
                    ->havingRaw('count(*) > 1');
            })->count(),
        ];

        return response()->json($stats);
    }
}