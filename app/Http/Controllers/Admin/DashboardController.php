<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\PaymentHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Thống kê tổng quan
        $totalUsers = User::where('role', 'user')->count();
        $activeUsers = User::where('role', 'user')->where('is_active', true)->count();
        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::active()->count();
        $expiredSubscriptions = Subscription::expired()->count();
        $pendingSubscriptions = Subscription::where('status', 'pending')->count();
        
        // Doanh thu tháng này
        $monthlyRevenue = PaymentHistory::completed()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        // Doanh thu tháng trước
        $lastMonthRevenue = PaymentHistory::completed()
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount');

        // Top plans
        $topPlans = Plan::withCount('subscriptions')
            ->orderBy('subscriptions_count', 'desc')
            ->limit(5)
            ->get();

        // Subscription gần đây
        $recentSubscriptions = Subscription::with(['user', 'plan'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Payment gần đây
        $recentPayments = PaymentHistory::with(['user', 'plan'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Biểu đồ doanh thu 12 tháng gần đây
        $revenueChart = PaymentHistory::completed()
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(amount) as total')
            )
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'activeUsers',
            'totalSubscriptions',
            'activeSubscriptions',
            'expiredSubscriptions',
            'pendingSubscriptions',
            'monthlyRevenue',
            'lastMonthRevenue',
            'topPlans',
            'recentSubscriptions',
            'recentPayments',
            'revenueChart'
        ));
    }
}