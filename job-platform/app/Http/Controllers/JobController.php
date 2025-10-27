<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(name="Jobs")
 */
class JobController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/jobs",
     *     summary="Get all jobs",
     *     tags={"Jobs"},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Jobs retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $jobs = Job::with('employer')
            ->active()
            ->when($request->search, function($query) use ($request) {
                return $query->search($request->search);
            })
            ->latest()
            ->paginate(10);

        return response()->json($jobs);
    }

    /**
     * @OA\Post(
     *     path="/api/jobs",
     *     summary="Create a new job",
     *     tags={"Jobs"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","company","location","description"},
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="company", type="string"),
     *             @OA\Property(property="location", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="requirements", type="string"),
     *             @OA\Property(property="salary", type="string"),
     *             @OA\Property(property="employment_type", type="string"),
     *             @OA\Property(property="category", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Job created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        if (!Auth::user()->isEmployer()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'salary' => 'nullable|string|max:100',
            'employment_type' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100'
        ]);

        $job = Job::create([
            'title' => $request->title,
            'company' => $request->company,
            'location' => $request->location,
            'description' => $request->description,
            'requirements' => $request->requirements,
            'salary' => $request->salary,
            'employment_type' => $request->employment_type,
            'category' => $request->category,
            'employer_id' => Auth::id(),
            'is_active' => true
        ]);

        return response()->json($job, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/jobs/{id}",
     *     summary="Get a specific job",
     *     tags={"Jobs"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job retrieved successfully"
     *     )
     * )
     */
    public function show(Job $job)
    {
        $job->load('employer');
        return response()->json($job);
    }

    /**
     * @OA\Put(
     *     path="/api/jobs/{id}",
     *     summary="Update a job",
     *     tags={"Jobs"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","company","location","description"},
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="company", type="string"),
     *             @OA\Property(property="location", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="requirements", type="string"),
     *             @OA\Property(property="salary", type="string"),
     *             @OA\Property(property="employment_type", type="string"),
     *             @OA\Property(property="category", type="string"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job updated successfully"
     *     )
     * )
     */
    public function update(Request $request, Job $job)
    {
        if (Auth::id() !== $job->employer_id && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'salary' => 'nullable|string|max:100',
            'employment_type' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean'
        ]);

        $job->update($request->all());

        return response()->json($job);
    }

    /**
     * @OA\Delete(
     *     path="/api/jobs/{id}",
     *     summary="Delete a job",
     *     tags={"Jobs"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Job deleted successfully"
     *     )
     * )
     */
    public function destroy(Job $job)
    {
        if (Auth::id() !== $job->employer_id && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $job->delete();

        return response()->json(['message' => 'Job deleted successfully']);
    }

    /**
     * @OA\Get(
     *     path="/api/employer/jobs",
     *     summary="Get employer's jobs",
     *     tags={"Jobs"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Jobs retrieved successfully"
     *     )
     * )
     */
    public function employerJobs()
    {
        if (!Auth::user()->isEmployer()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $jobs = Job::where('employer_id', Auth::id())
            ->withCount('applications')
            ->latest()
            ->get();

        return response()->json($jobs);
    }
}