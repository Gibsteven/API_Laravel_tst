<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Carbon\Carbon;

class AuthenticationController extends Controller
{
    /**
     * Register a new account.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|min:4',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        try {
            $user = new User();
            $user->name      = $request->name;
            $user->email     = $request->email;
            $user->password  = Hash::make($request->password);
            $user->role      = 'peuple'; // Rôle par défaut
            $user->save();

            return response()->json([
                'response_code' => 201,
                'status'        => 'success',
                'message'       => 'Successfully registered',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Registration failed',
            ], 500);
        }
    }

    /**
     * Login request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                $user = Auth::user();
                if ($user->is_banned) { // Vérifie si l'utilisateur est banni
                    Auth::logout();
                    return response()->json([
                        'response_code' => 403,
                        'status'        => 'error',
                        'message'       => 'Your account has been banned',
                    ], 403);
                }

                $accessToken = $user->createToken('authToken')->accessToken;

                return response()->json([
                    'response_code' => 200,
                    'status'        => 'success',
                    'message'       => 'Login successful',
                    'user_info'     => [
                        'id'    => $user->id,
                        'name'  => $user->name,
                        'email' => $user->email,
                        'role'  => $user->role,
                        'status' => $user->status,
                    ],
                    'token'         => $accessToken,
                ]);
            }

            return response()->json([
                'response_code' => 401,
                'status'        => 'error',
                'message'       => 'Unauthorized',
            ], 401);

        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Login failed',
            ], 500);
        }
    }

    /**
     * Get paginated user list (authenticated).
     */
    public function getUsers()
    {
        try {
            $users = User::latest()->paginate(10);

            return response()->json([
                'response_code'  => 200,
                'status'         => 'success',
                'message'        => 'Fetched user list successfully',
                'data_user_list' => $users,
            ]);
        } catch (\Exception $e) {
            Log::error('User List Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to fetch user list',
            ], 500);
        }
    }
    
    /**
     * Get authenticated user info.
     */
    public function userInfo()
    {
        try {
            $user = Auth::user();
            return response()->json([
                'response_code' => 200,
                'status'        => 'success',
                'message'       => 'Fetched user info successfully',
                'user_info'     => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                    'status' => $user->status,
                    'is_banned' => $user->is_banned,
                    'rewards' => $user->rewards,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('User Info Error: ' . $e->getMessage());
            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to fetch user info',
            ], 500);
        }
    }

    /**
     * Assign or update user role and status (Admin and SuperAdmin only).
     */
    public function assignRole(Request $request, User $user)
    {
        // Rôles disponibles pour l'assignation par les administrateurs.
        // Note : le SuperAdmin peut s'auto-attribuer le rôle de SuperAdmin, mais les Admins ne peuvent pas.
        $allowedRoles = [
            'peuple',
            'constellation',
            'tornades',
            'tour',
            'batview',
            'admin',
            'superadmin',
        ];
    
        $request->validate([
            'role' => 'required|in:' . implode(',', $allowedRoles),
        ]);
    
        try {
            // L'utilisateur qui exécute l'action
            $actor = Auth::user();
    
            // 1. Un admin ne peut pas attribuer le rôle de 'superadmin'
            if ($actor->isAdmin() && $request->input('role') === 'superadmin') {
                return response()->json([
                    'response_code' => 403,
                    'status'        => 'error',
                    'message'       => 'You cannot assign the superadmin role.',
                ], 403);
            }
            
            // 2. Un admin ne peut pas modifier un utilisateur qui est 'superadmin'.
            // Seul un 'superadmin' peut modifier un autre 'superadmin'.
            if ($actor->isAdmin() && $user->isSuperAdmin()) {
                return response()->json([
                    'response_code' => 403,
                    'status'        => 'error',
                    'message'       => 'You do not have permission to modify a superadmin.',
                ], 403);
            }
    
            // Met à jour le rôle de l'utilisateur
            $user->role = $request->role;
            $user->save();
    
            return response()->json([
                'response_code' => 200,
                'status'        => 'success',
                'message'       => 'Role updated successfully',
                'user'          => $user,
            ]);
        } catch (\Exception $e) {
            Log::error('Role Assignment Error: ' . $e->getMessage());
    
            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to update role.',
            ], 500);
        }
    }

    /**
     * Ban a user (Admin and SuperAdmin only).
     */
    public function banUser(User $user)
    {
        try {
            if ($user->isSuperAdmin() && !Auth::user()->isSuperAdmin()) {
                return response()->json([
                    'response_code' => 403,
                    'status'        => 'error',
                    'message'       => 'You cannot ban a SuperAdmin',
                ], 403);
            }
            
            $user->is_banned = true;
            $user->save();

            return response()->json([
                'response_code' => 200,
                'status'        => 'success',
                'message'       => 'User banned successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Ban User Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to ban user',
            ], 500);
        }
    }

    /**
     * Reward a user (Admin and SuperAdmin only).
     */
    public function rewardUser(Request $request, User $user)
    {
        $request->validate([
            'reward_details' => 'required|string',
        ]);

        try {
            $rewards = json_decode($user->rewards, true) ?? [];
            $rewards[] = [
                'reward' => $request->reward_details,
                'awarded_at' => Carbon::now(),
                'awarded_by' => Auth::user()->name,
            ];
            $user->rewards = json_encode($rewards);
            $user->save();

            return response()->json([
                'response_code' => 200,
                'status'        => 'success',
                'message'       => 'User rewarded successfully',
                'user'          => $user,
            ]);
        } catch (\Exception $e) {
            Log::error('Reward User Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to reward user',
            ], 500);
        }
    }

    /**
     * Logout the user and revoke token.
     */
    public function logOut(Request $request)
    {
        try {
            if (Auth::check()) {
                Auth::user()->tokens()->delete();

                return response()->json([
                    'response_code' => 200,
                    'status'        => 'success',
                    'message'       => 'Successfully logged out',
                ]);
            }

            return response()->json([
                'response_code' => 401,
                'status'        => 'error',
                'message'       => 'User not authenticated',
            ], 401);
        } catch (\Exception $e) {
            Log::error('Logout Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'An error occurred during logout',
            ], 500);
        }
    }
}