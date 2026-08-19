<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'RecruitX API',
        'version' => 'v1',
    ]);
});