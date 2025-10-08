@extends('layouts.app')

@section('title', 'Subscription - Keki SaaS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Quản lý Subscription
                </h1>
                <p class="text-gray-600 mt-1">
                    Xem và quản lý gói subscription của bạn
                </p>
            </div>
        </div>
    </div>

    <!-- Current Subscription -->
    @if($activeSubscription)
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">
                Subscription hiện tại
            </h2>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                Active
            </span>
        </div>
        
        <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <div class="text-sm font-medium text-gray-500">Gói</div>
                    <div class="text-lg font-semibold text-gray-900">{{ $activeSubscription->plan->name }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Giá</div>
                    <div class="text-lg font-semibold text-gray-900">{{ $activeSubscription->formatted_amount_paid }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Hết hạn</div>
                    <div class="text-lg font-semibold text-gray-900">{{ $activeSubscription->formatted_expiry_date }}</div>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-500">Còn lại</div>
                    <div class="text-lg font-semibold text-gray-900">{{ $activeSubscription->days_remaining }} ngày</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Available Plans -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                Các gói có sẵn
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Liên hệ admin để đăng ký gói mới
            </p>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($availablePlans as $plan)
                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow duration-200">
                    <div class="text-center">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            {{ $plan->name }}
                        </h3>
                        <div class="text-3xl font-bold text-indigo-600 mb-2">
                            {{ $plan->formatted_price }}
                        </div>
                        <div class="text-sm text-gray-500 mb-4">
                            {{ $plan->formatted_duration }}
                        </div>
                        
                        @if($plan->description)
                        <p class="text-sm text-gray-600 mb-4">
                            {{ $plan->description }}
                        </p>
                        @endif
                        
                        @if($plan->features)
                        <ul class="text-sm text-gray-600 mb-6 space-y-1">
                            @foreach($plan->features as $feature)
                            <li class="flex items-center">
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $feature }}
                            </li>
                            @endforeach
                        </ul>
                        @endif
                        
                        <a href="{{ route('user.subscription.show', $plan) }}" 
                           class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                            Xem chi tiết
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Subscription History -->
    @if($subscriptionHistory->count() > 0)
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                Lịch sử subscription
            </h2>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($subscriptionHistory as $subscription)
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center">
                            <h3 class="text-sm font-medium text-gray-900">
                                {{ $subscription->plan->name }}
                            </h3>
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($subscription->status === 'active') bg-green-100 text-green-800
                                @elseif($subscription->status === 'expired') bg-red-100 text-red-800
                                @elseif($subscription->status === 'cancelled') bg-gray-100 text-gray-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                {{ ucfirst($subscription->status) }}
                            </span>
                        </div>
                        <div class="mt-1 text-sm text-gray-500">
                            <p>{{ $subscription->formatted_amount_paid }} • {{ $subscription->formatted_expiry_date }}</p>
                            @if($subscription->notes)
                            <p class="mt-1">{{ $subscription->notes }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $subscription->created_at->format('d/m/Y') }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
