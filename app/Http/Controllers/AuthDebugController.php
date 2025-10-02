<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthDebugController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function debug(Request $request)
    {
        $debugInfo = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'environment' => config('app.env'),
            'authentication' => [
                'check' => Auth::check(),
                'id' => Auth::id(),
                'user_name' => Auth::user()->name ?? null,
                'user_email' => Auth::user()->email ?? null,
                'guards' => [],
            ],
            'session' => [
                'driver' => config('session.driver'),
                'id' => session()->getId(),
                'auth_session_key' => session()->get('login_web_'.sha1('web')),
                'current_business_unit_id' => session('current_business_unit_id'),
                'current_department_id' => session('current_department_id'),
                'all_session_keys' => array_keys(session()->all()),
            ],
            'user_details' => null,
            'department_info' => null,
        ];

        // Test different guards
        foreach (config('auth.guards', []) as $guardName => $guardConfig) {
            try {
                $guard = Auth::guard($guardName);
                $debugInfo['authentication']['guards'][$guardName] = [
                    'check' => $guard->check(),
                    'id' => $guard->id(),
                    'user_name' => $guard->user()->name ?? null,
                ];
            } catch (\Exception $e) {
                $debugInfo['authentication']['guards'][$guardName] = [
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Get user details if authenticated
        if (Auth::check()) {
            $user = Auth::user();
            $debugInfo['user_details'] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'primary_department_id' => $user->primary_department_id,
                'global_role' => $user->global_role,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            // Get department information
            if ($user->primaryDepartment) {
                $debugInfo['department_info'] = [
                    'id' => $user->primaryDepartment->id,
                    'name' => $user->primaryDepartment->name,
                    'code' => $user->primaryDepartment->code,
                    'business_unit_id' => $user->primaryDepartment->business_unit_id,
                    'business_unit_name' => $user->primaryDepartment->businessUnit->name ?? null,
                ];
            } else {
                // Try to find departments by primary_department_id
                $department = \App\Models\Department::find($user->primary_department_id);
                if ($department) {
                    $debugInfo['department_info'] = [
                        'found_by' => 'primary_department_id',
                        'id' => $department->id,
                        'name' => $department->name,
                        'code' => $department->code,
                        'business_unit_id' => $department->business_unit_id,
                    ];
                }
            }
        }

        if ($request->wantsJson() || $request->query('format') === 'json') {
            return response()->json($debugInfo, 200, [], JSON_PRETTY_PRINT);
        }

        return view('debug.auth', compact('debugInfo'));
    }
}
