<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $activeSubscription = $user->activeSubscription;
        $subscriptionStatus = $user->getSubscriptionStatus();
        
        // Lấy các gói subscription có sẵn (không bao gồm custom)
        $availablePlans = Plan::active()
            ->standard()
            ->orderBy('sort_order')
            ->get();

        // Lấy lịch sử subscription
        $subscriptionHistory = $user->subscriptions()
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('user.subscription', compact(
            'user',
            'activeSubscription',
            'subscriptionStatus',
            'availablePlans',
            'subscriptionHistory'
        ));
    }

    public function show(Plan $plan)
    {
        $user = Auth::user();
        
        return view('user.subscription.show', compact('plan', 'user'));
    }

    public function request(Plan $plan)
    {
        $user = Auth::user();
        
        // Tạo subscription request (pending)
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
            'starts_at' => now(),
            'expires_at' => now()->addDays($plan->duration_days),
            'amount_paid' => $plan->price,
            'notes' => 'Yêu cầu từ user - chờ admin xác nhận'
        ]);

        return redirect()->route('user.subscription')
            ->with('success', 'Yêu cầu subscription đã được gửi. Vui lòng chờ admin xác nhận.');
    }
}