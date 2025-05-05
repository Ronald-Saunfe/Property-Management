<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use App\Http\Requests\TenantRequest;
use Illuminate\Database\Eloquent\Builder;

class TenantController extends Controller
{
    /**
     * Display a paginated, filtered, and sorted listing of tenants.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = Tenant::query();

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
     * @param  \App\Http\Requests\TenantRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TenantRequest $request)
    {
        $validated = $request->validated();

        $tenant = Tenant::create($validated);

        return response()->json($tenant, 201);
    }

    /**
     * Display the specified tenant.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $tenant = Tenant::with('leases')->findOrFail($id);
        return response()->json($tenant);
    }

    /**
     * Update the specified tenant in storage.
     *
     * @param  \App\Http\Requests\TenantRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(TenantRequest $request, $id)
    {
        $tenant = Tenant::findOrFail($id);
        $validated = $request->validated();

        $tenant->update($validated);

        return response()->json($tenant);
    }

    /**
     * Remove the specified tenant from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $tenant = Tenant::findOrFail($id);
        
        // Check if tenant has active leases
        $activeLeases = $tenant->leases()->whereIn('status', ['active', 'pending'])->count();
        if ($activeLeases > 0) {
            return response()->json([
                'message' => 'Cannot delete tenant with active or pending leases. Change lease status first or update tenant status to inactive.'
            ], 422);
        }
        
        $tenant->delete();

        return response()->json(null, 204);
    }

    /**
     * Get tenant statistics.
     *
     * @return \Illuminate\Http\Response
     */
    public function statistics()
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'inactive_tenants' => Tenant::where('status', 'inactive')->count(),
            'pending_tenants' => Tenant::where('status', 'pending')->count(),
            'evicted_tenants' => Tenant::where('status', 'evicted')->count(),
            'average_income' => Tenant::whereNotNull('income')->avg('income') ?? 0,
            'average_credit_score' => Tenant::whereNotNull('credit_score')->avg('credit_score') ?? 0,
            'tenants_with_leases' => Tenant::has('leases')->count(),
            'tenants_without_leases' => Tenant::doesntHave('leases')->count(),
        ];

        return response()->json($stats);
    }
}