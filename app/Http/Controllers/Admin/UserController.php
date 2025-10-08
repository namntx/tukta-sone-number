<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\PaymentHistory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('role', 'user')
            ->with(['activeSubscription.plan', 'latestSubscription.plan'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['subscriptions.plan', 'paymentHistory.plan']);
        
        $activeSubscription = $user->activeSubscription;
        $subscriptionHistory = $user->subscriptions()->with('plan')->orderBy('created_at', 'desc')->get();
        $paymentHistory = $user->paymentHistory()->with('plan')->orderBy('created_at', 'desc')->get();
        
        $availablePlans = Plan::active()->orderBy('sort_order')->get();

        return view('admin.users.show', compact(
            'user',
            'activeSubscription',
            'subscriptionHistory',
            'paymentHistory',
            'availablePlans'
        ));
    }

    public function updateStatus(User $user, Request $request)
    {
        $request->validate([
            'is_active' => 'required|boolean'
        ]);

        $user->update([
            'is_active' => $request->is_active
        ]);

        $status = $request->is_active ? 'kích hoạt' : 'vô hiệu hóa';
        
        return redirect()->back()
            ->with('success', "Tài khoản đã được {$status} thành công.");
    }

    public function upgradeSubscription(User $user, Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'notes' => 'nullable|string|max:1000'
        ]);

        $plan = Plan::findOrFail($request->plan_id);
        
        // Hủy subscription hiện tại nếu có
        if ($user->activeSubscription) {
            $user->activeSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);
        }

        // Tạo subscription mới với thông tin từ plan
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addDays($plan->duration_days),
            'amount_paid' => $plan->price,
            'notes' => $request->notes
        ]);

        // Tạo payment history
        PaymentHistory::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'payment_method' => 'cash',
            'status' => 'completed',
            'notes' => 'Admin upgrade: ' . ($request->notes ?? ''),
            'paid_at' => now()
        ]);

        return redirect()->back()
            ->with('success', 'Subscription đã được tạo thành công với gói ' . $plan->name . '.');
    }

    public function extendSubscription(User $user, Request $request)
    {
        $request->validate([
            'extend_plan_id' => 'required|exists:plans,id',
            'notes' => 'nullable|string|max:1000'
        ]);

        $activeSubscription = $user->activeSubscription;
        
        if (!$activeSubscription) {
            return redirect()->back()
                ->with('error', 'User không có subscription active để gia hạn.');
        }

        $plan = Plan::findOrFail($request->extend_plan_id);

        // Gia hạn subscription bằng thời gian của gói
        $newExpiryDate = $activeSubscription->expires_at->addDays($plan->duration_days);
        $activeSubscription->update([
            'expires_at' => $newExpiryDate,
            'notes' => $activeSubscription->notes . "\nGia hạn thêm {$plan->formatted_duration} với gói {$plan->name}: " . ($request->notes ?? '')
        ]);

        // Tạo payment history cho việc gia hạn
        PaymentHistory::create([
            'user_id' => $user->id,
            'subscription_id' => $activeSubscription->id,
            'plan_id' => $plan->id,
            'amount' => $plan->price,
            'payment_method' => 'cash',
            'status' => 'completed',
            'notes' => 'Admin extend: ' . ($request->notes ?? ''),
            'paid_at' => now()
        ]);

        return redirect()->back()
            ->with('success', "Subscription đã được gia hạn thêm {$plan->formatted_duration} với gói {$plan->name}.");
    }

    public function cancelSubscription(User $user)
    {
        $activeSubscription = $user->activeSubscription;
        
        if (!$activeSubscription) {
            return redirect()->back()
                ->with('error', 'User không có subscription active.');
        }

        $activeSubscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now()
        ]);

        return redirect()->back()
            ->with('success', 'Subscription đã được hủy thành công.');
    }
}