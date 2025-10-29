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

    <!-- Customers List -->
    <div class="bg-white shadow rounded-lg">
        @if($customers->count() > 0)
            <div class="px-3 md:px-6 py-3 md:py-4 border-b border-gray-200">
                <h2 class="text-base md:text-lg font-semibold text-gray-900">
                    Danh sách ({{ $customers->total() }})
                </h2>
            </div>
            <div class="divide-y divide-gray-200">
                @foreach($customers as $customer)
                <div class="px-3 md:px-6 py-3 md:py-4">
                    <!-- Mobile Layout -->
                    <div class="md:hidden">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="text-base font-semibold text-gray-900 truncate">
                                        {{ $customer->name }}
                                    </h3>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium flex-shrink-0 {{ $customer->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $customer->is_active ? '✓' : '✕' }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500">{{ $customer->phone }}</p>
                            </div>
                        </div>
                        
                        <!-- Quick Stats Grid -->
                        <div class="grid grid-cols-2 gap-2 mb-3">
                            <div class="bg-green-50 rounded-lg p-2">
                                <div class="text-xs text-gray-600">Tổng ăn</div>
                                <div class="text-sm font-bold text-green-600">{{ number_format($customer->total_win_amount / 1000, 0) }}k</div>
                            </div>
                            <div class="bg-red-50 rounded-lg p-2">
                                <div class="text-xs text-gray-600">Tổng thua</div>
                                <div class="text-sm font-bold text-red-600">{{ number_format($customer->total_lose_amount / 1000, 0) }}k</div>
                            </div>
                            <div class="bg-blue-50 rounded-lg p-2">
                                <div class="text-xs text-gray-600">Lãi/Lỗ</div>
                                <div class="text-sm font-bold {{ $customer->net_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format(abs($customer->net_profit) / 1000, 0) }}k
                                </div>
                            </div>
                            <div class="bg-purple-50 rounded-lg p-2">
                                <div class="text-xs text-gray-600">Hôm nay</div>
                                <div class="text-sm font-bold {{ $customer->daily_net_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format(abs($customer->daily_net_profit) / 1000, 0) }}k
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2">
                            <a href="{{ route('user.customers.show', $customer) }}" 
                               class="flex-1 text-center px-3 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 text-xs font-medium rounded-md transition">
                                Xem
                            </a>
                            <a href="{{ route('user.customers.edit', $customer) }}" 
                               class="flex-1 text-center px-3 py-2 bg-gray-50 text-gray-600 hover:bg-gray-100 text-xs font-medium rounded-md transition">
                                Sửa
                            </a>
                            <form method="POST" action="{{ route('user.customers.destroy', $customer) }}" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="w-full px-3 py-2 bg-red-50 text-red-600 hover:bg-red-100 text-xs font-medium rounded-md transition"
                                        onclick="return confirm('Vô hiệu hóa khách hàng này?')">
                                    Xóa
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Desktop Layout -->
                    <div class="hidden md:flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <h3 class="text-lg font-medium text-gray-900">
                                    {{ $customer->name }}
                                </h3>
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $customer->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $customer->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500">
                                SĐT: {{ $customer->phone }}
                            </p>
                            <div class="mt-2 grid grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Tổng ăn:</span>
                                    <span class="font-medium text-green-600">{{ number_format($customer->total_win_amount, 0, ',', '.') }} VNĐ</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Tổng thua:</span>
                                    <span class="font-medium text-red-600">{{ number_format($customer->total_lose_amount, 0, ',', '.') }} VNĐ</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Lãi/Lỗ:</span>
                                    <span class="font-medium {{ $customer->net_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($customer->net_profit, 0, ',', '.') }} VNĐ
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Hôm nay:</span>
                                    <span class="font-medium {{ $customer->daily_net_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($customer->daily_net_profit, 0, ',', '.') }} VNĐ
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('user.customers.show', $customer) }}" 
                               class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                Xem
                            </a>
                            <a href="{{ route('user.customers.edit', $customer) }}" 
                               class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                                Sửa
                            </a>
                            <form method="POST" action="{{ route('user.customers.destroy', $customer) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="text-red-600 hover:text-red-900 text-sm font-medium"
                                        onclick="return confirm('Bạn có chắc chắn muốn vô hiệu hóa khách hàng này?')">
                                    Vô hiệu hóa
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            @if($customers->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
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
