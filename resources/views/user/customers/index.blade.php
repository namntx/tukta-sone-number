@extends('layouts.app')

@section('title', 'Quản lý khách hàng - Keki SaaS')

@section('content')
<div class="pb-4">
    <!-- Header -->
    <div class="sticky top-14 z-10 bg-gray-50 border-b border-gray-200 -mx-3 px-3 py-2 mb-3">
        <div class="flex items-center justify-between mb-2">
            <h1 class="text-base font-semibold text-gray-900">Khách hàng</h1>
            <a href="{{ route('user.customers.create') }}" class="btn btn-primary btn-sm">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Thêm
            </a>
        </div>

        <!-- Search -->
        <form method="GET" action="{{ route('user.customers.index') }}" class="flex gap-1.5">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Tìm tên/SĐT..."
                   class="flex-1 input-sm">
            <button type="submit" class="btn btn-secondary btn-sm btn-icon">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </button>
        </form>
    </div>

    <!-- Customer List -->
    <div class="space-y-1.5">
        @if($customers->count() > 0)
            @foreach($customers as $customer)
            <a href="{{ route('user.customers.show', $customer) }}"
               class="block bg-white rounded-lg border border-gray-200 hover:border-primary active:bg-gray-50">
                <div class="px-3 py-2 flex items-center gap-2.5">
                    <!-- Status Indicator -->
                    <div class="flex-shrink-0 w-1 h-10 rounded-full {{ $customer->is_active ? 'bg-green-500' : 'bg-gray-300' }}"></div>

                    <!-- Customer Info -->
                    <div class="flex-1 min-w-0">
                        <!-- Name & Phone -->
                        <div class="flex items-center gap-2 mb-0.5">
                            <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $customer->name }}</h3>
                            <span class="text-xs text-gray-500 whitespace-nowrap">{{ $customer->phone }}</span>
                        </div>

                        <!-- Financial Info -->
                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-gray-500">Ăn: <span class="font-medium text-green-600">{{ number_format(($customer->daily_win_for_date ?? 0) / 1000, 1) }}k</span></span>
                            <span class="text-gray-300">|</span>
                            <span class="text-gray-500">Thua: <span class="font-medium text-red-600">{{ number_format(($customer->daily_lose_for_date ?? 0) / 1000, 1) }}k</span></span>
                            @php
                                $dailyNetProfit = ($customer->daily_win_for_date ?? 0) - ($customer->daily_lose_for_date ?? 0);
                            @endphp
                            <span class="text-gray-300">|</span>
                            <span class="font-medium {{ $dailyNetProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $dailyNetProfit >= 0 ? '+' : '' }}{{ number_format($dailyNetProfit / 1000, 1) }}k
                            </span>
                        </div>
                    </div>

                    <!-- Arrow -->
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </a>
            @endforeach
            
            <!-- Pagination -->
            @if($customers->hasPages())
            <div class="py-3 flex justify-center">
                {{ $customers->links() }}
            </div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="empty-state-title">Chưa có khách hàng</h3>
                <p class="empty-state-description mb-3">Bắt đầu bằng cách thêm khách hàng đầu tiên</p>
                <a href="{{ route('user.customers.create') }}" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Thêm khách hàng
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

