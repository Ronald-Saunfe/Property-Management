<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Http\Request;
use App\Http\Requests\LeaseRequest;
use Illuminate\Database\Eloquent\Builder;

class LeaseController extends Controller
{
    /**
     * Display a paginated, filtered, and sorted listing of leases.
     * 
     * @OA\Get(
     *     path="/leases",
     *     operationId="getLeasesList",
     *     tags={"Leases"},
     *     summary="Get list of leases",
     *     description="Returns paginated list of leases with filtering and sorting options",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by lease status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "pending", "expired", "terminated"})
     *     ),
     *     @OA\Parameter(
     *         name="lease_type",
     *         in="query",
     *         description="Filter by lease type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="unit_id",
     *         in="query",
     *         description="Filter by unit ID",
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
     *         name="start_date_from",
     *         in="query",
     *         description="Filter by start date (from)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="start_date_to",
     *         in="query",
     *         description="Filter by start date (to)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date_from",
     *         in="query",
     *         description="Filter by end date (from)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date_to",
     *         in="query",
     *         description="Filter by end date (to)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="rent_min",
     *         in="query",
     *         description="Filter by minimum monthly rent",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="rent_max",
     *         in="query",
     *         description="Filter by maximum monthly rent",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search across notes, tenant information, and unit number",
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
     *                 @OA\Property(property="unit_id", type="integer"),
     *                 @OA\Property(property="tenant_id", type="integer"),
     *                 @OA\Property(property="start_date", type="string", format="date"),
     *                 @OA\Property(property="end_date", type="string", format="date"),
     *                 @OA\Property(property="monthly_rent", type="number", format="float"),
     *                 @OA\Property(property="security_deposit", type="number", format="float"),
     *                 @OA\Property(property="lease_type", type="string"),
     *                 @OA\Property(property="payment_day", type="integer"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="notes", type="string", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
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
            $query = Lease::with(['unit', 'tenant', 'tenants', 'payments']);

            // Filtering
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('lease_type')) {
                $query->where('lease_type', $request->lease_type);
            }

            if ($request->has('unit_id')) {
                $query->where('unit_id', $request->unit_id);
            }

            if ($request->has('tenant_id')) {
                $query->where('tenant_id', $request->tenant_id);
            }

            if ($request->has('start_date_from')) {
                $query->where('start_date', '>=', $request->start_date_from);
            }

            if ($request->has('start_date_to')) {
                $query->where('start_date', '<=', $request->start_date_to);
            }

            if ($request->has('end_date_from')) {
                $query->where('end_date', '>=', $request->end_date_from);
            }

            if ($request->has('end_date_to')) {
                $query->where('end_date', '<=', $request->end_date_to);
            }

            if ($request->has('rent_min')) {
                $query->where('monthly_rent', '>=', $request->rent_min);
            }

            if ($request->has('rent_max')) {
                $query->where('monthly_rent', '<=', $request->rent_max);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function (Builder $query) use ($search) {
                    $query->where('notes', 'like', "%{$search}%")
                        ->orWhereHas('tenant', function (Builder $query) use ($search) {
                            $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('unit', function (Builder $query) use ($search) {
                            $query->where('unit_number', 'like', "%{$search}%");
                        });
                });
            }

            // Sorting
            $sortField = $request->input('sort_by', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            
            // Validate sort field to prevent SQL injection
            $allowedSortFields = [
                'id', 'unit_id', 'tenant_id', 'start_date', 'end_date', 
                'monthly_rent', 'security_deposit', 'lease_type', 'payment_day', 
                'status', 'created_at', 'updated_at'
            ];
            
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = (int) $request->input('per_page', 15);
            $leases = $query->paginate($perPage);

            return response()->json([
                'data' => $leases->items(),
                'pagination' => [
                    'total' => $leases->total(),
                    'per_page' => $leases->perPage(),
                    'current_page' => $leases->currentPage(),
                    'last_page' => $leases->lastPage(),
                    'from' => $leases->firstItem() ?? 0,
                    'to' => $leases->lastItem() ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching leases',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created lease in storage.
     *
     * @OA\Post(
     *     path="/leases",
     *     operationId="storeLease",
     *     tags={"Leases"},
     *     summary="Store new lease",
     *     description="Creates a new lease and returns it",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"unit_id", "tenant_id", "start_date", "end_date", "monthly_rent", "security_deposit", "lease_type", "payment_day", "status"},
     *             @OA\Property(property="unit_id", type="integer", description="ID of the unit being leased"),
     *             @OA\Property(property="tenant_id", type="integer", description="ID of the primary tenant"),
     *             @OA\Property(property="start_date", type="string", format="date", description="Lease start date"),
     *             @OA\Property(property="end_date", type="string", format="date", description="Lease end date"),
     *             @OA\Property(property="monthly_rent", type="number", format="float", description="Monthly rent amount"),
     *             @OA\Property(property="security_deposit", type="number", format="float", description="Security deposit amount"),
     *             @OA\Property(property="lease_type", type="string", description="Type of lease"),
     *             @OA\Property(property="payment_day", type="integer", description="Day of month when payment is due"),
     *             @OA\Property(property="status", type="string", description="Lease status (active, pending, expired, terminated)"),
     *             @OA\Property(property="notes", type="string", description="Additional notes about the lease", nullable=true),
     *             @OA\Property(property="tenants", type="array", description="Additional tenants on the lease", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="tenant_id", type="integer", description="ID of the tenant"),
     *                 @OA\Property(property="is_primary", type="boolean", description="Whether this tenant is the primary tenant")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lease created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="unit_id", type="integer"),
     *             @OA\Property(property="tenant_id", type="integer"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="monthly_rent", type="number", format="float"),
     *             @OA\Property(property="security_deposit", type="number", format="float"),
     *             @OA\Property(property="lease_type", type="string"),
     *             @OA\Property(property="payment_day", type="integer"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="notes", type="string", nullable=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="unit", type="object"),
     *             @OA\Property(property="tenant", type="object"),
     *             @OA\Property(property="tenants", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="payments", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or unit not available",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The unit is not available for the selected date range.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * @param  \App\Http\Requests\LeaseRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(LeaseRequest $request)
    {
        // Validation is handled by LeaseRequest
        $validated = $request->validated();

        // Check if unit is available
        $existingActiveLeases = Lease::where('unit_id', $validated['unit_id'])
            ->where('status', 'active')
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhere(function ($query) use ($validated) {
                        $query->where('start_date', '<=', $validated['start_date'])
                            ->where('end_date', '>=', $validated['end_date']);
                    });
            })
            ->count();

        if ($existingActiveLeases > 0) {
            return response()->json([
                'message' => 'The unit is not available for the selected date range.'
            ], 422);
        }

        $lease = Lease::create($validated);

        // Handle additional tenants if provided
        if (isset($validated['tenants']) && is_array($validated['tenants'])) {
            foreach ($validated['tenants'] as $tenantData) {
                $lease->tenants()->attach($tenantData['tenant_id'], [
                    'is_primary' => $tenantData['is_primary'],
                ]);
            }
        } else {
            // Add the primary tenant from tenant_id field
            $lease->tenants()->attach($validated['tenant_id'], [
                'is_primary' => true,
            ]);
        }

        // Load relationships for the response
        $lease->load(['unit', 'tenant', 'tenants', 'payments']);

        return response()->json($lease, 201);
    }

    /**
     * Display the specified lease.
     *
     * @OA\Get(
     *     path="/leases/{id}",
     *     operationId="getLeaseById",
     *     tags={"Leases"},
     *     summary="Get lease information",
     *     description="Returns lease details by ID",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Lease ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="unit_id", type="integer"),
     *             @OA\Property(property="tenant_id", type="integer"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="monthly_rent", type="number", format="float"),
     *             @OA\Property(property="security_deposit", type="number", format="float"),
     *             @OA\Property(property="lease_type", type="string"),
     *             @OA\Property(property="payment_day", type="integer"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="notes", type="string", nullable=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="unit", type="object"),
     *             @OA\Property(property="tenant", type="object"),
     *             @OA\Property(property="tenants", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="payments", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lease not found"
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
        $lease = Lease::with(['unit', 'tenant', 'tenants', 'payments'])->findOrFail($id);
        return response()->json($lease);
    }

    /**
     * Update the specified lease in storage.
     *
     * @OA\Put(
     *     path="/leases/{id}",
     *     operationId="updateLease",
     *     tags={"Leases"},
     *     summary="Update lease",
     *     description="Updates an existing lease and returns it",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Lease ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="unit_id", type="integer", description="ID of the unit being leased"),
     *             @OA\Property(property="tenant_id", type="integer", description="ID of the primary tenant"),
     *             @OA\Property(property="start_date", type="string", format="date", description="Lease start date"),
     *             @OA\Property(property="end_date", type="string", format="date", description="Lease end date"),
     *             @OA\Property(property="monthly_rent", type="number", format="float", description="Monthly rent amount"),
     *             @OA\Property(property="security_deposit", type="number", format="float", description="Security deposit amount"),
     *             @OA\Property(property="lease_type", type="string", description="Type of lease"),
     *             @OA\Property(property="payment_day", type="integer", description="Day of month when payment is due"),
     *             @OA\Property(property="status", type="string", description="Lease status (active, pending, expired, terminated)"),
     *             @OA\Property(property="notes", type="string", description="Additional notes about the lease", nullable=true),
     *             @OA\Property(property="tenants", type="array", description="Additional tenants on the lease", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="tenant_id", type="integer", description="ID of the tenant"),
     *                 @OA\Property(property="is_primary", type="boolean", description="Whether this tenant is the primary tenant")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lease updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="unit_id", type="integer"),
     *             @OA\Property(property="tenant_id", type="integer"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="monthly_rent", type="number", format="float"),
     *             @OA\Property(property="security_deposit", type="number", format="float"),
     *             @OA\Property(property="lease_type", type="string"),
     *             @OA\Property(property="payment_day", type="integer"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="notes", type="string", nullable=true),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="unit", type="object"),
     *             @OA\Property(property="tenant", type="object"),
     *             @OA\Property(property="tenants", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="payments", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lease not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or unit not available",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The unit is not available for the selected date range.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     *
     * @param  \App\Http\Requests\LeaseRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(LeaseRequest $request, $id)
    {
        $lease = Lease::findOrFail($id);
        $validated = $request->validated();

        // Check if unit is available (only if unit_id or dates are changing)
        if (isset($validated['unit_id']) || isset($validated['start_date']) || isset($validated['end_date'])) {
            $unitId = $validated['unit_id'] ?? $lease->unit_id;
            $startDate = $validated['start_date'] ?? $lease->start_date;
            $endDate = $validated['end_date'] ?? $lease->end_date;

            $existingActiveLeases = Lease::where('unit_id', $unitId)
                ->where('status', 'active')
                ->where('id', '!=', $id)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($query) use ($startDate, $endDate) {
                            $query->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })
                ->count();

            if ($existingActiveLeases > 0) {
                return response()->json([
                    'message' => 'The unit is not available for the selected date range.'
                ], 422);
            }
        }

        $lease->update($validated);

        // Update tenants if provided
        if (isset($validated['tenants']) && is_array($validated['tenants'])) {
            // Detach all existing tenants
            $lease->tenants()->detach();
            
            // Attach new tenants
            foreach ($validated['tenants'] as $tenantData) {
                $lease->tenants()->attach($tenantData['tenant_id'], [
                    'is_primary' => $tenantData['is_primary'],
                ]);
            }
        }

        // Load relationships for the response
        $lease->load(['unit', 'tenant', 'tenants', 'payments']);

        return response()->json($lease);
    }

    /**
     * Remove the specified lease from storage.
     *
     * @OA\Delete(
     *     path="/leases/{id}",
     *     operationId="deleteLease",
     *     tags={"Leases"},
     *     summary="Delete lease",
     *     description="Deletes a lease if it has no associated payments",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Lease ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Lease deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Lease not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Cannot delete lease with dependencies",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot delete lease with associated payments. Consider marking it as terminated instead.")
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
        $lease = Lease::findOrFail($id);
        
        // Check if there are any payments associated with this lease
        if ($lease->payments()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete lease with associated payments. Consider marking it as terminated instead.'
            ], 422);
        }
        
        // Detach all tenants before deleting
        $lease->tenants()->detach();
        
        $lease->delete();

        return response()->json(null, 204);
    }

    /**
     * Get lease statistics.
     *
     * @OA\Get(
     *     path="/leases/statistics",
     *     operationId="getLeaseStatistics",
     *     tags={"Leases"},
     *     summary="Get lease statistics",
     *     description="Returns statistics about leases including counts by status and average rent",
     *     security={{
     *       "bearerAuth": {}
     *     }},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="active", type="integer"),
     *             @OA\Property(property="pending", type="integer"),
     *             @OA\Property(property="expired", type="integer"),
     *             @OA\Property(property="terminated", type="integer"),
     *             @OA\Property(property="avg_rent", type="number", format="float"),
     *             @OA\Property(property="expiring_soon", type="integer")
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
            'total' => Lease::count(),
            'active' => Lease::where('status', 'active')->count(),
            'pending' => Lease::where('status', 'pending')->count(),
            'expired' => Lease::where('status', 'expired')->count(),
            'terminated' => Lease::where('status', 'terminated')->count(),
            'avg_rent' => Lease::where('status', 'active')->avg('monthly_rent') ?? 0,
            'expiring_soon' => Lease::where('status', 'active')
                ->where('end_date', '<=', now()->addMonths(1))
                ->count(),
        ];

        return response()->json($stats);
    }
}