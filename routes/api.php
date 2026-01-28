<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TikTokController;
use App\Http\Controllers\VideoSimilarityController;

Route::post('/tiktok/download', [TikTokController::class, 'download']);
Route::post('/tiktok/download-file', [TikTokController::class, 'downloadFile']);
Route::get('/tiktok/download-file', [TikTokController::class, 'downloadFile']);

// Video Similarity AI Routes
Route::post('/ai/embed', [VideoSimilarityController::class, 'embedVideo']);
Route::post('/ai/similar', [VideoSimilarityController::class, 'findSimilar']);
Route::get('/ai/health', [VideoSimilarityController::class, 'healthCheck']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');