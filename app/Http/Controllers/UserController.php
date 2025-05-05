<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;

class UserController extends Controller
{
    /**
     * Display a paginated, filtered, and sorted listing of users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $query = User::query();

            // Filtering
            if ($request->has('name')) {
                $query->where('name', 'like', "%{$request->name}%");
            }

            if ($request->has('email')) {
                $query->where('email', 'like', "%{$request->email}%");
            }

            if ($request->has('role')) {
                $query->where('role', $request->role);
            }

            if ($request->has('phone')) {
                $query->where('phone', 'like', "%{$request->phone}%");
            }

            if ($request->has('created_from')) {
                $query->whereDate('created_at', '>=', $request->created_from);
            }

            if ($request->has('created_to')) {
                $query->whereDate('created_at', '<=', $request->created_to);
            }

            // Search by name, email, or phone
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortField = $request->input('sort_by', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            
            // Validate sort field to prevent SQL injection
            $allowedSortFields = [
                'id', 'name', 'email', 'role', 'phone', 'created_at', 'updated_at'
            ];
            
            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = (int) $request->input('per_page', 15);
            $users = $query->paginate($perPage);

            return response()->json([
                'data' => $users->items(),
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'from' => $users->firstItem() ?? 0,
                    'to' => $users->lastItem() ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created user in storage.
     *
     * @param  \App\Http\Requests\UserRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        $validated = $request->validated();

        // Hash the password
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        // Remove password from response
        $user->makeHidden(['password']);

        return response()->json($user, 201);
    }

    /**
     * Display the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::with(['properties', 'property_managers'])->findOrFail($id);
        return response()->json($user);
    }

    /**
     * Update the specified user in storage.
     *
     * @param  \App\Http\Requests\UserRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validated();

        // Only hash the password if it's provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        // Remove password from response
        $user->makeHidden(['password']);

        return response()->json($user);
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Check if user has properties
        if ($user->properties()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete user with associated properties. Remove all properties first or transfer ownership.'
            ], 422);
        }
        
        // Check if user is a property manager
        if ($user->property_managers()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete user who is a property manager. Remove from property manager roles first.'
            ], 422);
        }
        
        $user->delete();

        return response()->json(null, 204);
    }

    /**
     * Get user statistics.
     *
     * @return \Illuminate\Http\Response
     */
    public function statistics()
    {
        $stats = [
            'total_users' => User::count(),
            'users_by_role' => [
                'admin' => User::where('role', 'admin')->count(),
                'property_manager' => User::where('role', 'property_manager')->count(),
                'tenant' => User::where('role', 'tenant')->count(),
                'owner' => User::where('role', 'owner')->count(),
            ],
            'new_users_this_month' => User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'top_property_owners' => User::withCount('properties')
                ->where('role', 'owner')
                ->orderByDesc('properties_count')
                ->limit(5)
                ->get()
                ->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'properties_count' => $user->properties_count
                    ];
                }),
        ];

        return response()->json($stats);
    }
}