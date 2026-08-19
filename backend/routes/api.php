<?php

use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminCompanyController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\CandidateProfileController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\InterviewController;
use App\Http\Controllers\Api\JobOfferController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/login', fn () => response()->json(['message' => 'Unauthenticated.'], 401))->name('login');

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/jobs', [JobOfferController::class, 'index'])->middleware('permission:jobs.view');
        Route::get('/jobs/{job:slug}', [JobOfferController::class, 'show'])->middleware('permission:jobs.view');

        Route::post('/jobs/{job}/apply', [ApplicationController::class, 'store'])->middleware('permission:applications.create');
        Route::get('/applications', [ApplicationController::class, 'index'])->middleware('permission:applications.view-own');

        Route::get('/recruiter/applications', [ApplicationController::class, 'recruiterIndex'])->middleware('permission:applications.view');
        Route::patch('/applications/{application}/status', [ApplicationController::class, 'updateStatus'])->middleware('permission:applications.update');
        Route::get('/applications/{application}/cv', [ApplicationController::class, 'applicationCv'])->middleware('permission:applications.view');

        Route::post('/applications/{application}/interviews', [InterviewController::class, 'store'])->middleware('permission:interviews.create');
        Route::get('/recruiter/interviews', [InterviewController::class, 'index'])->middleware('permission:interviews.view');
        Route::get('/me/interviews', [InterviewController::class, 'mine'])->middleware('permission:interviews.view-own');
        Route::patch('/interviews/{interview}', [InterviewController::class, 'update'])->middleware('permission:interviews.update');

        Route::post('/applications/{application}/interviews/{interview}/evaluation', [EvaluationController::class, 'store'])->middleware('permission:evaluations.create');
        Route::get('/recruiter/evaluations', [EvaluationController::class, 'index'])->middleware('permission:evaluations.view');
        Route::get('/evaluations/{evaluation}', [EvaluationController::class, 'show'])->middleware('permission:evaluations.view');

        Route::get('/staff/dashboard', [DashboardController::class, 'index'])->middleware('permission:reports.view');

        Route::get('/admin/users', [AdminUserController::class, 'index'])->middleware('permission:users.view');
        Route::patch('/admin/users/{user}/role', [AdminUserController::class, 'updateRole'])->middleware('permission:users.update');
        Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])->middleware('permission:users.delete');

        Route::get('/admin/companies', [AdminCompanyController::class, 'index'])->middleware('permission:companies.view');
        Route::patch('/admin/companies/{company}', [AdminCompanyController::class, 'update'])->middleware('permission:companies.update');

        Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit.view');
        Route::get('/evaluation-criteria', [EvaluationController::class, 'criteria'])->middleware('permission:evaluations.view');

        Route::get('/me/notifications', [NotificationController::class, 'index']);
        Route::get('/me/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('/me/notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('/me/notifications/read-all', [NotificationController::class, 'markAllRead']);

        Route::middleware('role:candidate')->group(function () {
            Route::get('/me/profile', [CandidateProfileController::class, 'show']);
            Route::put('/me/profile', [CandidateProfileController::class, 'update']);
            Route::post('/me/cv', [CandidateProfileController::class, 'uploadCv']);
            Route::get('/me/cv', [CandidateProfileController::class, 'downloadOwnCv']);
        });
    });
});