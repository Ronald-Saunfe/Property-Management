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