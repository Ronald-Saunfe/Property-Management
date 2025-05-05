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

    /**
     * Display the specified lease tenant.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $leaseTenant = LeaseTenant::with(['lease', 'tenant'])->findOrFail($id);
        return response()->json($leaseTenant);
    }

    /**
     * Update the specified lease tenant in storage.
     *
     * @param  \App\Http\Requests\LeaseTenantRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
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

    /**
     * Remove the specified lease tenant from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
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
     * Get statistics about lease tenants.
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