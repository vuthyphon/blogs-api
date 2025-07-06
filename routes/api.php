<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\TagController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('posts', PostController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('tags', TagController::class);
});

Route::get('/articles', [PostController::class, 'index']);
Route::get('/articles/{id}', [PostController::class, 'show']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/test-api',function () {
    return response()->json([
        'message' => 'Welcome to the Blog API',
        'status' => 'success'
    ]);

});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);

Route::apiResource('categories', CategoryController::class)->except(['create', 'edit']);
Route::apiResource('tags', TagController::class)->except(['create', 'edit']);
