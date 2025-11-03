@extends('layouts.app')

@section('title', 'Quản lý khách hàng - Keki SaaS')

@section('content')
<div class="pb-4">
    <!-- Sticky Header -->
    <div class="sticky top-0 z-10 bg-white shadow-sm border-b border-gray-200 mb-3">
        <div class="px-3 py-2.5">
            <div class="flex items-center justify-between mb-2">
                <h1 class="text-lg font-bold text-gray-900">Khách hàng</h1>
                <a href="{{ route('user.customers.create') }}" 
                   class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Thêm
                </a>
            </div>
            
            <!-- Quick Search -->
            <form method="GET" action="{{ route('user.customers.index') }}" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Tìm tên/SĐT..." 
                       class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <button type="submit" class="inline-flex items-center justify-center px-3 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Compact Customer List -->
    <div class="space-y-1.5">
        @if($customers->count() > 0)
            @foreach($customers as $customer)
            <a href="{{ route('user.customers.show', $customer) }}" 
               class="block bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all active:bg-gray-50">
                <div class="px-3 py-2.5 flex items-center gap-3">
                    <!-- Status Indicator -->
                    <div class="flex-shrink-0 w-1 h-12 rounded-full {{ $customer->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></div>
                    
                    <!-- Customer Info & Financial -->
                    <div class="flex-1 min-w-0">
                        <!-- Name & Phone -->
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $customer->name }}</h3>
                            <span class="text-xs text-gray-500 whitespace-nowrap">{{ $customer->phone }}</span>
                        </div>
                        
                        <!-- TODAY'S FINANCIAL - Compact -->
                        <div class="flex items-center gap-3 text-xs">
                            <div class="flex items-center gap-1">
                                <span class="text-gray-500">Ăn:</span>
                                <span class="font-bold text-green-600">{{ number_format(($customer->daily_win_for_date ?? 0) / 1000, 1) }}k</span>
                            </div>
                            <div class="w-px h-3 bg-gray-300"></div>
                            <div class="flex items-center gap-1">
                                <span class="text-gray-500">Thua:</span>
                                <span class="font-bold text-red-600">{{ number_format(($customer->daily_lose_for_date ?? 0) / 1000, 1) }}k</span>
                            </div>
                            <div class="w-px h-3 bg-gray-300"></div>
                            @php
                                $dailyNetProfit = ($customer->daily_win_for_date ?? 0) - ($customer->daily_lose_for_date ?? 0);
                            @endphp
                            <div class="flex items-center gap-1 px-2 py-0.5 rounded {{ $dailyNetProfit >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                <span class="text-xs font-medium">Lãi/Lỗ:</span>
                                <span class="font-bold">
                                    {{ $dailyNetProfit >= 0 ? '+' : '' }}{{ number_format($dailyNetProfit / 1000, 1) }}k
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Arrow Icon -->
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
            </a>
            @endforeach
            
            <!-- Pagination -->
            @if($customers->hasPages())
            <div class="py-4 flex justify-center">
                {{ $customers->links() }}
            </div>
            @endif
        @else
            <div class="py-16 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gray-100 mb-4">
                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-medium text-gray-900 mb-1">Chưa có khách hàng</h3>
                <p class="text-sm text-gray-500 mb-4">Bắt đầu bằng cách thêm khách hàng đầu tiên</p>
                <a href="{{ route('user.customers.create') }}" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Thêm khách hàng
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

