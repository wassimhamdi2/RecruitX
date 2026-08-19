<?php

use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CandidateProfileController;
use App\Http\Controllers\Api\JobOfferController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
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

        Route::middleware('role:candidate')->group(function () {
            Route::get('/me/profile', [CandidateProfileController::class, 'show']);
            Route::put('/me/profile', [CandidateProfileController::class, 'update']);
            Route::post('/me/cv', [CandidateProfileController::class, 'uploadCv']);
            Route::get('/me/cv', [CandidateProfileController::class, 'downloadOwnCv']);
        });
    });
});