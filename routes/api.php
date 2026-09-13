<?php

    use App\Http\Controllers\Api\AuthController;
    use App\Http\Controllers\Api\CategoryController;
    use Illuminate\Support\Facades\Route;

    Route::prefix('v1/auth')->group(function () {
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('register', [AuthController::class, 'register']);
            Route::post('login', [AuthController::class, 'login']);
        });

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::prefix('v1/categories')->group(function () {
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::get('/{category}', [CategoryController::class, 'show']);
        });



        Route::middleware(['auth:sanctum', 'admin','throttle:30,1'])->group(function () {
            Route::post('/', [CategoryController::class, 'store']);
            Route::put('/{category}',[CategoryController::class, 'update']);
            Route::delete('/{category}',[CategoryController::class, 'destroy']);
        });
    });
