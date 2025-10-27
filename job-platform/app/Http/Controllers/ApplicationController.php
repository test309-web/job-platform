<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(name="Applications")
 */
class ApplicationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/applications",
     *     summary="Apply for a job",
     *     tags={"Applications"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"job_id","cover_letter"},
     *             @OA\Property(property="job_id", type="integer"),
     *             @OA\Property(property="cover_letter", type="string"),
     *             @OA\Property(property="resume", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Application submitted successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        if (!Auth::user()->isUser()) {
            return response()->json(['message' => 'Only regular users can apply for jobs'], 403);
        }

        $request->validate([
            'job_id' => 'required|exists:jobs,id',
            'cover_letter' => 'required|string',
            'resume' => 'nullable|string'
        ]);

        $existingApplication = Application::where('user_id', Auth::id())
            ->where('job_id', $request->job_id)
            ->first();

        if ($existingApplication) {
            return response()->json(['message' => 'You have already applied for this job'], 422);
        }

        $application = Application::create([
            'user_id' => Auth::id(),
            'job_id' => $request->job_id,
            'cover_letter' => $request->cover_letter,
            'resume' => $request->resume,
            'status' => 'pending'
        ]);

        return response()->json($application, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/my-applications",
     *     summary="Get user's applications",
     *     tags={"Applications"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Applications retrieved successfully"
     *     )
     * )
     */
    public function myApplications()
    {
        $applications = Application::with('job.employer')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json($applications);
    }

    /**
     * @OA\Get(
     *     path="/api/jobs/{jobId}/applications",
     *     summary="Get applications for a job",
     *     tags={"Applications"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="jobId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Applications retrieved successfully"
     *     )
     * )
     */
    public function jobApplications($jobId)
    {
        $job = Job::findOrFail($jobId);

        if (Auth::id() !== $job->employer_id && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $applications = Application::with('user')
            ->where('job_id', $jobId)
            ->latest()
            ->get();

        return response()->json($applications);
    }

    /**
     * @OA\Put(
     *     path="/api/applications/{id}/status",
     *     summary="Update application status",
     *     tags={"Applications"},
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
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"accepted","rejected"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Application status updated successfully"
     *     )
     * )
     */
    public function updateStatus(Request $request, Application $application)
    {
        $job = $application->job;

        if (Auth::id() !== $job->employer_id && !Auth::user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:accepted,rejected'
        ]);

        $application->update(['status' => $request->status]);

        return response()->json($application);
    }
}