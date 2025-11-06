<?php

use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\Auth\StaffLoginController;
use App\Http\Controllers\Auth\StaffPasswordResetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Magic link authentication routes (for clients)
Route::prefix('auth/magic-link')->name('auth.magic-link.')->group(function () {
    Route::post('/request', [MagicLinkController::class, 'request'])->name('request');
    Route::get('/verify/{token}', [MagicLinkController::class, 'verify'])->name('verify');
});

// Placeholder login route (for clients)
Route::get('/login', function () {
    return response()->json(['message' => 'Login page - to be implemented'], 200);
})->name('login');

// Client gallery routes
Route::prefix('gallery')->name('client.gallery.')->group(function () {
    Route::get('/{slug}', [\App\Http\Controllers\Client\GalleryViewController::class, 'show'])->name('show');
    Route::post('/{slug}/verify-password', [\App\Http\Controllers\Client\GalleryViewController::class, 'verifyPassword'])->name('verify-password');
});

// Client photo selection API routes
Route::prefix('api')->name('client.')->group(function () {
    Route::post('/photos/{photo}/toggle-selection', [\App\Http\Controllers\Client\PhotoSelectionController::class, 'toggle'])->name('photos.toggle');
    Route::get('/galleries/{gallery}/selections', [\App\Http\Controllers\Client\PhotoSelectionController::class, 'list'])->name('gallery.selections');
    Route::delete('/galleries/{gallery}/selections', [\App\Http\Controllers\Client\PhotoSelectionController::class, 'clear'])->name('gallery.clear-selections');
});

// Shopping cart routes
Route::get('/cart', [\App\Http\Controllers\CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [\App\Http\Controllers\CartController::class, 'add'])->name('cart.add');
Route::post('/cart/add-bulk', [\App\Http\Controllers\CartController::class, 'addBulk'])->name('cart.add-bulk');
Route::put('/cart/{item}', [\App\Http\Controllers\CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{item}', [\App\Http\Controllers\CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart', [\App\Http\Controllers\CartController::class, 'clear'])->name('cart.clear');
Route::post('/cart/apply-discount', [\App\Http\Controllers\CartController::class, 'applyDiscount'])->name('cart.apply-discount');
Route::post('/cart/remove-discount', [\App\Http\Controllers\CartController::class, 'removeDiscount'])->name('cart.remove-discount');

// Order routes
Route::get('/checkout', [\App\Http\Controllers\OrderController::class, 'checkout'])->name('orders.checkout');
Route::post('/orders', [\App\Http\Controllers\OrderController::class, 'store'])->name('orders.store');
Route::middleware('auth:client')->group(function () {
    Route::get('/orders/{order}', [\App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/payment', [\App\Http\Controllers\PaymentController::class, 'show'])->name('payments.show');
    Route::post('/orders/{order}/payment', [\App\Http\Controllers\PaymentController::class, 'process'])->name('payments.process');
});

// Payment webhook (must be outside auth middleware)
Route::post('/webhooks/stripe', [\App\Http\Controllers\PaymentController::class, 'webhook'])->name('payments.webhook');

// Unsubscribe route (public)
Route::get('/unsubscribe/{token}', [\App\Http\Controllers\UnsubscribeController::class, 'unsubscribe'])->name('unsubscribe');

// Download routes
Route::middleware('auth:client')->group(function () {
    Route::get('/downloads', [\App\Http\Controllers\DownloadController::class, 'index'])->name('downloads.index');
});

// Public download routes (via secure tokens)
Route::get('/download/{token}', [\App\Http\Controllers\DownloadController::class, 'download'])->name('downloads.single');
Route::get('/download/batch/{token}', [\App\Http\Controllers\DownloadController::class, 'batch'])->name('downloads.batch');

// Refund routes (photographer only)
Route::middleware('staff')->prefix('staff')->name('staff.')->group(function () {
    Route::post('/orders/{order}/refund', [\App\Http\Controllers\PaymentController::class, 'refund'])->name('orders.refund');
});

// Public package browsing
Route::get('/packages', [\App\Http\Controllers\PreOrderController::class, 'browsePackages'])->name('packages.browse');
Route::get('/packages/photographer/{photographerId}', [\App\Http\Controllers\PreOrderController::class, 'browsePackages'])->name('packages.browse.photographer');

// Pre-order routes
Route::middleware('auth:client')->group(function () {
    Route::get('/pre-orders/create/{package}', [\App\Http\Controllers\PreOrderController::class, 'create'])->name('pre-orders.create');
    Route::post('/pre-orders/{package}', [\App\Http\Controllers\PreOrderController::class, 'store'])->name('pre-orders.store');
    Route::get('/pre-orders/{order}', [\App\Http\Controllers\PreOrderController::class, 'show'])->name('pre-orders.show');
    Route::get('/pre-orders/{order}/finalize', [\App\Http\Controllers\PreOrderController::class, 'finalize'])->name('pre-orders.finalize');
    Route::post('/pre-orders/{order}/finalize', [\App\Http\Controllers\PreOrderController::class, 'completeFinalization'])->name('pre-orders.complete-finalization');
});

// Staff pre-order management
Route::middleware('staff')->prefix('staff')->name('staff.')->group(function () {
    Route::get('/pre-orders/{order}', [\App\Http\Controllers\PreOrderController::class, 'show'])->name('pre-orders.show');
    Route::post('/pre-orders/{order}/start-finalization', [\App\Http\Controllers\PreOrderController::class, 'startFinalization'])->name('pre-orders.start-finalization');
    Route::get('/pre-orders/{order}/finalize', [\App\Http\Controllers\PreOrderController::class, 'finalize'])->name('pre-orders.finalize');
    Route::post('/pre-orders/{order}/finalize', [\App\Http\Controllers\PreOrderController::class, 'completeFinalization'])->name('pre-orders.complete-finalization');
});

// Staff authentication routes
Route::prefix('staff')->name('staff.')->group(function () {
    // Login routes
    Route::get('/login', [StaffLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [StaffLoginController::class, 'login']);
    Route::post('/logout', [StaffLoginController::class, 'logout'])->name('logout');

    // Password reset routes
    Route::get('/password/reset', [StaffPasswordResetController::class, 'showResetRequestForm'])->name('password.request');
    Route::post('/password/email', [StaffPasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/password/reset/{token}', [StaffPasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [StaffPasswordResetController::class, 'reset'])->name('password.update');

    // Placeholder dashboard route (to be implemented)
    Route::get('/dashboard', function () {
        return response()->json(['message' => 'Staff dashboard - to be implemented'], 200);
    })->middleware('staff')->name('dashboard');

    // Gallery routes (photographers only)
    Route::resource('galleries', \App\Http\Controllers\GalleryController::class)->middleware('photographer');

    // Photo upload routes
    Route::prefix('galleries/{gallery}/photos')->name('galleries.photos.')->middleware('photographer')->group(function () {
        Route::get('/upload', [\App\Http\Controllers\PhotoController::class, 'create'])->name('upload');
        Route::post('/', [\App\Http\Controllers\PhotoController::class, 'store'])->name('store');
    });

    // Upload progress route
    Route::get('/photos/upload/progress/{batchId}', [\App\Http\Controllers\PhotoController::class, 'progress'])->middleware('photographer')->name('photos.progress');

    // Package management routes (photographers only)
    Route::resource('packages', \App\Http\Controllers\PackageController::class)->middleware('photographer');
});
