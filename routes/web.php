<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\SubscriptionController as UserSubscriptionController;
use App\Http\Controllers\User\CustomerController as UserCustomerController;
use App\Http\Controllers\User\BettingTicketController as UserBettingTicketController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\User\LotteryResultController as UserLotteryResultController;
use App\Http\Controllers\User\CustomerRateController as UserCustomerRateController;

// Trang chủ
Route::get('/', function () {
    return view('welcome');
});

// Authentication routes (Laravel Breeze hoặc tự tạo)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();
        
        // Cập nhật last_login_at
        auth()->user()->update(['last_login_at' => now()]);
        
        return redirect()->intended(route('home'));
    }

    return back()->withErrors([
        'email' => 'Thông tin đăng nhập không chính xác.',
    ])->onlyInput('email');
})->name('login.post');

Route::post('/logout', function () {
    auth()->logout();
    return redirect('/');
})->name('logout');

// User routes (cần đăng nhập)
Route::middleware(['auth'])->group(function () {
    // Global filters update
    Route::post('/global-filters/update', function (Illuminate\Http\Request $request) {
        $request->validate([
            'global_date' => 'required|date',
            'global_region' => 'required|in:bac,trung,nam',
        ]);

        session([
            'global_date' => $request->global_date,
            'global_region' => $request->global_region,
        ]);

        return back();
    })->name('global-filters.update');
    
    // User Dashboard
    Route::prefix('user')->name('user.')->group(function () {
        Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [UserDashboardController::class, 'profile'])->name('profile');
        Route::post('/profile', [UserDashboardController::class, 'updateProfile'])->name('profile.update');

        // Get lottery results
        Route::get('/kqxs', [UserLotteryResultController::class, 'index'])->name('kqxs');
        Route::get('/kqxs/session', [UserLotteryResultController::class, 'bySession'])->name('kqxs.bySession');
        Route::get('/kqxs/{id}', [UserLotteryResultController::class, 'show'])->name('kqxs.show');
        
        // Subscription routes
        Route::get('/subscription', [UserSubscriptionController::class, 'index'])->name('subscription');
        Route::get('/subscription/{plan}', [UserSubscriptionController::class, 'show'])->name('subscription.show');
        Route::post('/subscription/{plan}/request', [UserSubscriptionController::class, 'request'])->name('subscription.request');
        
        // Customer routes
        Route::resource('customers', UserCustomerController::class);
        // Route::get('/customers/{customer}/rates', [UserCustomerController::class, 'getRates'])->name('customers.rates');

        Route::prefix('customers/{customer}')->group(function () {
            Route::get('rates', [\App\Http\Controllers\User\CustomerRateController::class, 'edit'])->name('customers.rates.edit');
            Route::post('rates',[\App\Http\Controllers\User\CustomerRateController::class, 'update'])->name('customers.rates.update');
            Route::delete('rates/{rate}',[\App\Http\Controllers\User\CustomerRateController::class, 'destroy'])->name('customers.rates.destroy');
        });
        
        // Betting ticket routes
        Route::resource('betting-tickets', UserBettingTicketController::class);
        Route::post('/betting-tickets/parse-message', [UserBettingTicketController::class, 'parseMessage'])->name('betting-tickets.parse-message');
        Route::post('/betting-tickets/{bettingTicket}/settle', [UserBettingTicketController::class, 'settle'])->name('betting-tickets.settle');
        Route::post('/betting-tickets/settle-batch', [UserBettingTicketController::class, 'settleBatch'])->name('betting-tickets.settle-batch');
    });
    
    // Protected routes (cần subscription active)
    Route::middleware(['subscription'])->group(function () {
        // Thêm các route cần subscription ở đây
        // Ví dụ:
        Route::get('/protected-feature', function () {
            return view('user.protected-feature');
        })->name('protected.feature');
        
        // Có thể thêm nhiều route khác như:
        // Route::get('/premium-content', [ContentController::class, 'premium'])->name('premium.content');
        // Route::get('/advanced-tools', [ToolController::class, 'advanced'])->name('advanced.tools');
    });
    
    // Admin routes
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        
        // User management
        Route::resource('users', AdminUserController::class)->only(['index', 'show']);
        Route::post('/users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');
        Route::post('/users/{user}/upgrade', [AdminUserController::class, 'upgradeSubscription'])->name('users.upgrade');
        Route::post('/users/{user}/extend', [AdminUserController::class, 'extendSubscription'])->name('users.extend');
        Route::post('/users/{user}/cancel', [AdminUserController::class, 'cancelSubscription'])->name('users.cancel');
        
        // Plan management
        Route::resource('plans', AdminPlanController::class);
        Route::post('/plans/{plan}/toggle-status', [AdminPlanController::class, 'toggleStatus'])->name('plans.toggle-status');
    });
});

// Redirect mặc định sau khi đăng nhập
Route::get('/home', function () {
    $user = auth()->user();
    
    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    
    return redirect()->route('user.dashboard');
})->middleware('auth')->name('home');