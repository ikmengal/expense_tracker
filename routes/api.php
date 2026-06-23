<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\RecurringBillController;

// Public Routes (Bina login ke chalenge)
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// 🔓 Public Route (Har koi dekh sakta hy - Logo aur Name ke liye)
Route::get('/system/settings', [SettingController::class, 'getSettings']);
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
Route::post('/contact-support', [SupportController::class, 'storeSupportMessage']);
Route::get('/global-settings', [AdminController::class, 'getGlobalSettings']);

// 2. Protected Routes (Token ke sath chalenge)
Route::middleware('auth:sanctum')->group(function () {
    // User profile check karne ke liye (Optional)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });


    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::post('/user/update-profile', [UserController::class, 'updateProfile']); // Multipart form ke liye POST use hoga
    Route::put('/user/update-password', [UserController::class, 'updatePassword']);

    Route::post('/system/settings', [SettingController::class, 'updateSettings']);

    Route::post('/transactions/scan', [TransactionController::class, 'scanReceipt']);

    Route::get('/reports/export/{format}', [ReportController::class, 'export']);
    Route::get('/reports/budget-alerts', [ReportController::class, 'getBudgetAlerts']);

    Route::post('logout', [AuthController::class, 'logout']);

    // Goals Resources Setup
    Route::get('/goals', [GoalController::class, 'index']);
    Route::post('/goals', [GoalController::class, 'store']);
    Route::post('/goals/{id}/add-savings', [GoalController::class, 'addSavings']);
    Route::delete('/goals/{id}', [GoalController::class, 'destroy']);
    Route::post('/goals/{id}/allocate', [GoalController::class, 'allocateToGoal']);

    // Notifications Fetch Setup
    Route::get('/notifications', function (Request $request) {
        return response()->json($request->user()->unreadNotifications);
    });
    Route::post('/notifications/mark-read', function (Request $request) {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    });

    // Categories CRUD
    Route::apiResource('categories', CategoryController::class);

    // Transactions / Expenses CRUD
    Route::apiResource('transactions', TransactionController::class);

    // Dashboard Stats for Frontend Charts
    Route::get('dashboard-stats', [TransactionController::class, 'dashboardStats']);
    Route::post('/user/update-currency', [TransactionController::class, 'updateCurrency']);
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'getDashboardData']);
    Route::post('/recurring-bills/{id}/pay', [RecurringBillController::class, 'markAsPaid']);

    // User routes for support tickets
    Route::get('/user/tickets', [SupportController::class, 'index']);
    Route::post('/user/tickets', [SupportController::class, 'store']);
});

// Temporary 'role:Admin' ko hata diya taaki aap testing kar sakein
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    Route::get('/analytics', [AdminController::class, 'getAnalytics']);
    Route::get('/users', [AdminController::class, 'getUsers']);
    Route::get('/tickets', [AdminController::class, 'getTickets']);
    Route::get('/goals', [AdminController::class, 'getGoals']);
    Route::post('/tickets/{id}/reply', [AdminController::class, 'replyToTicket']);
    Route::get('/users/{id}/analytics', [AdminController::class, 'getUserAnalytics']);
    Route::get('/users/{id}/workspace-analytics', [AdminController::class, 'getUserSaaSAnalytics']);

    // Core Upgrades
    Route::post('/profile/update', [AdminController::class, 'updateProfile']);
    Route::post('/profile/change-password', [AdminController::class, 'changePassword']);
    Route::get('/settings', [AdminController::class, 'getSettings']);
    Route::post('/settings/update', [AdminController::class, 'updateSettings']);
    Route::post('/users/{user}/impersonate', [AdminController::class, 'impersonatingUser']);
});
