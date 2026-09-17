<?php

    use App\Http\Controllers\Api\AuthController;
    use App\Http\Controllers\Api\CategoryController;
    use App\Http\Controllers\Api\ProductController;
    use App\Http\Controllers\Api\CartController;
    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\Api\CheckoutController;

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

    Route::prefix('v1/products')->group(function () {
        Route::middleware('throttle:60,1')->group(function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::get('/{product}', [ProductController::class, 'show']);
        });
        Route::middleware(['auth:sanctum', 'admin','throttle:30,1'])->group(function () {
            Route::post('/', [ProductController::class, 'store']);
            Route::put('/{product}',[ProductController::class, 'update']);
            Route::delete('/{product}',[ProductController::class, 'destroy']);
        });
    });

    Route::prefix('v1/cart')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'storeItem']);
        Route::patch('/items/{item}', [CartController::class, 'updateItem']);
        Route::delete('/items/{item}', [CartController::class, 'destroyItem']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    Route::prefix('v1')
        ->middleware(['auth:sanctum', 'throttle:30,1'])
        ->group(function () {
            Route::post('/checkout', [CheckoutController::class, 'store']);
        });
