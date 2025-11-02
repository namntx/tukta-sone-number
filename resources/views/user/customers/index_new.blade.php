@extends('layouts.app')

@section('title', 'Quản lý khách hàng - Keki SaaS')

@section('content')
<div class="space-y-3 md:space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-3 md:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-900">
                    Quản lý khách hàng
                </h1>
                <p class="text-gray-600 mt-1 text-sm md:text-base">
                    Quản lý danh sách khách hàng và hệ số cược
                </p>
            </div>
            <div class="mt-3 sm:mt-0">
                <a href="{{ route('user.customers.create') }}" 
                   class="inline-flex items-center justify-center w-full sm:w-auto px-4 py-2.5 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Thêm khách hàng
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 md:gap-6">
        <div class="bg-white shadow rounded-lg p-3 md:p-6">
            <div class="flex flex-col md:flex-row md:items-center">
                <div class="flex-shrink-0 mb-2 md:mb-0">
                    <div class="w-7 h-7 md:w-8 md:h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="md:ml-4">
                    <p class="text-xs md:text-sm font-medium text-gray-500">Khách hàng</p>
                    <p class="text-lg md:text-2xl font-semibold text-gray-900">{{ $customers->total() }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white shadow rounded-lg p-3 md:p-6">
            <div class="flex flex-col md:flex-row md:items-center">
                <div class="flex-shrink-0 mb-2 md:mb-0">
                    <div class="w-7 h-7 md:w-8 md:h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 md:w-5 md:h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
                <div class="md:ml-4">
                    <p class="text-xs md:text-sm font-medium text-gray-500">Hôm nay</p>
                    <p class="text-sm md:text-2xl font-semibold {{ ($todayStats['total_win'] - $todayStats['total_lose']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format(abs($todayStats['total_win'] - $todayStats['total_lose']) / 1000, 0) }}k
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-white shadow rounded-lg p-3 md:p-6">
            <div class="flex flex-col md:flex-row md:items-center">
                <div class="flex-shrink-0 mb-2 md:mb-0">
                    <div class="w-7 h-7 md:w-8 md:h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 md:w-5 md:h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="md:ml-4">
                    <p class="text-xs md:text-sm font-medium text-gray-500">Tháng này</p>
                    <p class="text-sm md:text-2xl font-semibold {{ ($monthlyStats['total_win'] - $monthlyStats['total_lose']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format(abs($monthlyStats['total_win'] - $monthlyStats['total_lose']) / 1000, 0) }}k
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-white shadow rounded-lg p-3 md:p-6">
            <div class="flex flex-col md:flex-row md:items-center">
                <div class="flex-shrink-0 mb-2 md:mb-0">
                    <div class="w-7 h-7 md:w-8 md:h-8 bg-purple-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 md:w-5 md:h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
                <div class="md:ml-4">
                    <p class="text-xs md:text-sm font-medium text-gray-500">Năm nay</p>
                    <p class="text-sm md:text-2xl font-semibold {{ ($yearlyStats['total_win'] - $yearlyStats['total_lose']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format(abs($yearlyStats['total_win'] - $yearlyStats['total_lose']) / 1000, 0) }}k
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-3 md:p-6">
        <form method="GET" action="{{ route('user.customers.index') }}" class="space-y-3 md:space-y-0 md:grid md:grid-cols-4 md:gap-4">
            <div>
                <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1 md:mb-2">Tìm kiếm</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Tên hoặc SĐT" 
                       class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            
            <div>
                <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1 md:mb-2">Trạng thái</label>
                <select name="status" class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Tất cả</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Ngừng hoạt động</option>
                </select>
            </div>
            
            <div>
                <label class="block text-xs md:text-sm font-medium text-gray-700 mb-1 md:mb-2">Sắp xếp</label>
                <select name="sort" class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Tên A-Z</option>
                    <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>Tên Z-A</option>
                    <option value="created_at" {{ request('sort') == 'created_at' ? 'selected' : '' }}>Mới nhất</option>
                    <option value="created_at_desc" {{ request('sort') == 'created_at_desc' ? 'selected' : '' }}>Cũ nhất</option>
                    <option value="net_profit" {{ request('sort') == 'net_profit' ? 'selected' : '' }}>Lãi cao</option>
                    <option value="net_profit_desc" {{ request('sort') == 'net_profit_desc' ? 'selected' : '' }}>Lỗ cao</option>
                </select>
            </div>
            
            <div class="md:flex md:items-end">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-md transition duration-200 text-sm">
                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Lọc
                </button>
            </div>
        </form>
    </div>

    <!-- Customers List - Mobile-First Design -->
    <div class="space-y-3 md:space-y-4">
        @if($customers->count() > 0)
            <div class="px-3 md:px-6 py-2 md:py-3">
                <h2 class="text-base md:text-lg font-semibold text-gray-900">
                    Danh sách khách hàng ({{ $customers->total() }})
                </h2>
            </div>
            <div class="space-y-3 md:space-y-4">
                @foreach($customers as $customer)
                <!-- Mobile-First Customer Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                    <!-- Header with Name -->
                    <div class="px-4 py-3 bg-gradient-to-r from-indigo-50 to-blue-50 border-b border-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                <h3 class="text-base font-bold text-gray-900 truncate">
                                    {{ $customer->name }}
                                </h3>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold flex-shrink-0 {{ $customer->is_active ? 'bg-green-500 text-white' : 'bg-gray-400 text-white' }}">
                                    {{ $customer->is_active ? '✓' : '✕' }}
                                </span>
                            </div>
                            <a href="{{ route('user.customers.show', $customer) }}" 
                               class="text-indigo-600 hover:text-indigo-700 flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">{{ $customer->phone }}</p>
                    </div>

                    <!-- TODAY'S FINANCIAL HIGHLIGHT - Mobile First -->
                    <div class="px-4 py-4 bg-gradient-to-br {{ $customer->daily_net_profit >= 0 ? 'from-green-50 to-emerald-50' : 'from-red-50 to-orange-50' }}">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold text-gray-700 uppercase tracking-wide">💰 Hôm nay</span>
                            <span class="text-xs text-gray-500">{{ now()->format('d/m/Y') }}</span>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-3 mb-3">
                            <!-- Tiền ăn -->
                            <div class="bg-white rounded-lg p-3 text-center border border-green-200">
                                <div class="text-xs text-gray-600 mb-1">Tiền ăn</div>
                                <div class="text-lg font-bold text-green-600 leading-tight">
                                    {{ number_format($customer->daily_win_amount / 1000, 1) }}k
                                </div>
                            </div>
                            
                            <!-- Tiền thua -->
                            <div class="bg-white rounded-lg p-3 text-center border border-red-200">
                                <div class="text-xs text-gray-600 mb-1">Tiền thua</div>
                                <div class="text-lg font-bold text-red-600 leading-tight">
                                    {{ number_format($customer->daily_lose_amount / 1000, 1) }}k
                                </div>
                            </div>
                            
                            <!-- Lãi/Lỗ -->
                            <div class="bg-white rounded-lg p-3 text-center border-2 {{ $customer->daily_net_profit >= 0 ? 'border-green-400' : 'border-red-400' }}">
                                <div class="text-xs text-gray-600 mb-1">Lãi/Lỗ</div>
                                <div class="text-lg font-bold {{ $customer->daily_net_profit >= 0 ? 'text-green-600' : 'text-red-600' }} leading-tight">
                                    {{ $customer->daily_net_profit >= 0 ? '+' : '' }}{{ number_format($customer->daily_net_profit / 1000, 1) }}k
                                </div>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        @php
                            $totalDaily = $customer->daily_win_amount + $customer->daily_lose_amount;
                            $winPercent = $totalDaily > 0 ? ($customer->daily_win_amount / $totalDaily * 100) : 0;
                            $losePercent = $totalDaily > 0 ? ($customer->daily_lose_amount / $totalDaily * 100) : 0;
                        @endphp
                        @if($totalDaily > 0)
                        <div class="flex items-center gap-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-green-500 transition-all" style="width: {{ $winPercent }}%"></div>
                            <div class="h-full bg-red-500 transition-all" style="width: {{ $losePercent }}%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>Ăn: {{ number_format($winPercent, 0) }}%</span>
                            <span>Thua: {{ number_format($losePercent, 0) }}%</span>
                        </div>
                        @endif
                    </div>

                    <!-- Total Stats Row -->
                    <div class="px-4 py-3 bg-gray-50 grid grid-cols-3 gap-2 border-t border-gray-100">
                        <div class="text-center">
                            <div class="text-xs text-gray-500 mb-0.5">Tổng ăn</div>
                            <div class="text-sm font-semibold text-green-600">{{ number_format($customer->total_win_amount / 1000, 0) }}k</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-gray-500 mb-0.5">Tổng thua</div>
                            <div class="text-sm font-semibold text-red-600">{{ number_format($customer->total_lose_amount / 1000, 0) }}k</div>
                        </div>
                        <div class="text-center">
                            <div class="text-xs text-gray-500 mb-0.5">Lãi/Lỗ</div>
                            <div class="text-sm font-semibold {{ $customer->net_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format(abs($customer->net_profit) / 1000, 0) }}k
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="px-4 py-3 bg-white border-t border-gray-100 flex gap-2">
                        <a href="{{ route('user.customers.show', $customer) }}" 
                           class="flex-1 text-center px-3 py-2 bg-indigo-600 text-white hover:bg-indigo-700 text-sm font-medium rounded-lg transition">
                            Chi tiết
                        </a>
                        <a href="{{ route('user.customers.edit', $customer) }}" 
                           class="flex-1 text-center px-3 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 text-sm font-medium rounded-lg transition">
                            Sửa
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            @if($customers->hasPages())
            <div class="px-3 md:px-6 py-4 border-t border-gray-200">
                {{ $customers->links() }}
            </div>
            @endif
        @else
            <div class="px-6 py-12 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Chưa có khách hàng</h3>
                <p class="mt-1 text-sm text-gray-500">Bắt đầu bằng cách thêm khách hàng đầu tiên.</p>
                <div class="mt-6">
                    <a href="{{ route('user.customers.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Thêm khách hàng
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

