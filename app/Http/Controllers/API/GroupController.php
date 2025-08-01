<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Group;
use App\Models\User;

class GroupController extends Controller
{
    /**
     * Create a new group (Admin and SuperAdmin only).
     */
    public function createGroup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:groups,name',
        ]);

        try {
            $group = Group::create([
                'name'      => $request->name,
                'created_by' => Auth::id(),
            ]);

            // Add creator as a member
            $group->members()->attach(Auth::id());

            return response()->json([
                'response_code' => 201,
                'status'        => 'success',
                'message'       => 'Group created successfully',
                'group'         => $group,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Group Creation Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to create group',
            ], 500);
        }
    }

    /**
     * Add a member to a group (Admin and SuperAdmin only).
     */
    public function addMember(Request $request, Group $group)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $group->members()->syncWithoutDetaching([$user->id]);

            return response()->json([
                'response_code' => 200,
                'status'        => 'success',
                'message'       => 'User added to group successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Add Member Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to add member to group',
            ], 500);
        }
    }
}