<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TikTokController;

Route::post('/tiktok/download', [TikTokController::class, 'download']);
Route::post('/tiktok/download-file', [TikTokController::class, 'downloadFile']);
Route::get('/tiktok/download-file', [TikTokController::class, 'downloadFile']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');