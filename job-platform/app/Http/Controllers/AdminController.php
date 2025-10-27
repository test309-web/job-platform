<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Job;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(name="Admin")
 */
class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::user() || !Auth::user()->isAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            return $next($request);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/admin/dashboard",
     *     summary="Get admin dashboard stats",
     *     tags={"Admin"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard stats retrieved successfully"
     *     )
     * )
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_employers' => User::where('role', 'employer')->count(),
            'total_jobs' => Job::count(),
            'total_applications' => Application::count(),
            'active_jobs' => Job::active()->count(),
            'pending_applications' => Application::where('status', 'pending')->count()
        ];

        return response()->json($stats);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/users",
     *     summary="Get all users",
     *     tags={"Admin"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully"
     *     )
     * )
     */
    public function users()
    {
        $users = User::latest()->get();
        return response()->json($users);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/users",
     *     summary="Create a new user",
     *     tags={"Admin"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","role"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="role", type="string", enum={"admin","employer","user"}),
     *             @OA\Property(property="company_name", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully"
     *     )
     * )
     */
    public function createUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,employer,user',
            'company_name' => 'required_if:role,employer|string|max:255'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'company_name' => $request->company_name
        ]);

        return response()->json($user, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/jobs",
     *     summary="Get all jobs",
     *     tags={"Admin"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Jobs retrieved successfully"
     *     )
     * )
     */
    public function jobs()
    {
        $jobs = Job::with('employer')->latest()->get();
        return response()->json($jobs);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/applications",
     *     summary="Get all applications",
     *     tags={"Admin"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Applications retrieved successfully"
     *     )
     * )
     */
    public function applications()
    {
        $applications = Application::with(['user', 'job.employer'])->latest()->get();
        return response()->json($applications);
    }
}