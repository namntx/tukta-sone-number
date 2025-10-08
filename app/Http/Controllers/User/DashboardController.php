<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $activeSubscription = $user->activeSubscription;
        $subscriptionStatus = $user->getSubscriptionStatus();
        $daysRemaining = $user->getSubscriptionDaysRemaining();
        
        // Lấy lịch sử subscription gần đây
        $recentSubscriptions = $user->subscriptions()
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Lấy danh sách khách hàng
        $customers = $user->customers()->active()->get();
        
        // Get global date and region
        $globalDate = session('global_date', today());
        $globalRegion = session('global_region', 'Bắc');
        
        // Lấy phiếu cược theo global date và region
        $todayTickets = $user->bettingTickets()
            ->with(['customer', 'bettingType'])
            ->whereDate('betting_date', $globalDate)
            ->where('region', $globalRegion)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Thống kê theo global date và region
        $todayStats = [
            'total_tickets' => $user->bettingTickets()->whereDate('betting_date', $globalDate)->where('region', $globalRegion)->count(),
            'total_bet_amount' => $user->bettingTickets()->whereDate('betting_date', $globalDate)->where('region', $globalRegion)->sum('bet_amount'),
            'total_win_amount' => $user->bettingTickets()->whereDate('betting_date', $globalDate)->where('region', $globalRegion)->where('result', 'win')->sum('win_amount'),
            'total_lose_amount' => $user->bettingTickets()->whereDate('betting_date', $globalDate)->where('region', $globalRegion)->where('result', 'lose')->sum('bet_amount'),
        ];

        return view('user.dashboard', compact(
            'user',
            'activeSubscription',
            'subscriptionStatus',
            'daysRemaining',
            'recentSubscriptions',
            'customers',
            'todayTickets',
            'todayStats'
        ));
    }

    public function profile()
    {
        $user = Auth::user();
        return view('user.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->route('user.profile')
            ->with('success', 'Thông tin cá nhân đã được cập nhật thành công.');
    }
}