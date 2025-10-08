@extends('layouts.app')

@section('title', 'Quản lý phiếu cược - Keki SaaS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Quản lý phiếu cược
                </h1>
                <p class="text-gray-600 mt-1">
                    Quản lý và theo dõi các phiếu cược - {{ $global_region }} - {{ \Carbon\Carbon::parse($global_date)->format('d/m/Y') }}
                </p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="{{ route('user.betting-tickets.create') }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Thêm phiếu cược
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Phiếu hôm nay</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $todayStats['total_tickets'] }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Lãi hôm nay</p>
                    <p class="text-2xl font-semibold {{ ($todayStats['total_win'] - $todayStats['total_lose']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($todayStats['total_win'] - $todayStats['total_lose'], 0, ',', '.') }} VNĐ
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Lãi tháng này</p>
                    <p class="text-2xl font-semibold {{ ($monthlyStats['total_win'] - $monthlyStats['total_lose']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($monthlyStats['total_win'] - $monthlyStats['total_lose'], 0, ',', '.') }} VNĐ
                    </p>
                </div>
            </div>
        </div>
        
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Lãi năm nay</p>
                    <p class="text-2xl font-semibold {{ ($yearlyStats['total_win'] - $yearlyStats['total_lose']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($yearlyStats['total_win'] - $yearlyStats['total_lose'], 0, ',', '.') }} VNĐ
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6">
        <form method="GET" action="{{ route('user.betting-tickets.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Ngày</label>
                <input type="date" name="date" id="date" value="{{ request('date', $global_date) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label for="region" class="block text-sm font-medium text-gray-700 mb-1">Miền</label>
                <select name="region" id="region" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Tất cả miền</option>
                    <option value="Bắc" {{ request('region', $global_region) == 'Bắc' ? 'selected' : '' }}>Bắc</option>
                    <option value="Trung" {{ request('region', $global_region) == 'Trung' ? 'selected' : '' }}>Trung</option>
                    <option value="Nam" {{ request('region', $global_region) == 'Nam' ? 'selected' : '' }}>Nam</option>
                </select>
            </div>
            <div>
                <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-1">Khách hàng</label>
                <select name="customer_id" id="customer_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Tất cả khách hàng</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="result" class="block text-sm font-medium text-gray-700 mb-1">Kết quả</label>
                <select name="result" id="result" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Tất cả</option>
                    <option value="pending" {{ request('result') == 'pending' ? 'selected' : '' }}>Chờ</option>
                    <option value="win" {{ request('result') == 'win' ? 'selected' : '' }}>Ăn</option>
                    <option value="lose" {{ request('result') == 'lose' ? 'selected' : '' }}>Thua</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Lọc
                </button>
            </div>
        </form>
    </div>

    <!-- Tickets List -->
    <div class="bg-white shadow rounded-lg">
        @if($tickets->count() > 0)
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    Danh sách phiếu cược ({{ $tickets->total() }})
                </h2>
            </div>
            <div class="divide-y divide-gray-200">
                @foreach($tickets as $ticket)
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <h3 class="text-sm font-medium text-gray-900">
                                    {{ $ticket->customer->name }}
                                </h3>
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->result_badge_class }}">
                                    {{ ucfirst($ticket->result) }}
                                </span>
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->status_badge_class }}">
                                    {{ ucfirst($ticket->status) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $ticket->bettingType->name }} • {{ $ticket->region }} • {{ $ticket->station }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ $ticket->parsed_message }}
                            </p>
                            <div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Tiền cược:</span>
                                    <span class="font-medium">{{ $ticket->formatted_bet_amount }}</span>
                                </div>
                                @if($ticket->result === 'win')
                                <div>
                                    <span class="text-gray-500">Tiền trúng:</span>
                                    <span class="font-medium text-green-600">{{ $ticket->formatted_win_amount }}</span>
                                </div>
                                @endif
                                @if($ticket->payout_amount > 0)
                                <div>
                                    <span class="text-gray-500">Tiền trả:</span>
                                    <span class="font-medium text-red-600">{{ $ticket->formatted_payout_amount }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">
                                {{ $ticket->betting_date->format('d/m/Y') }}
                            </span>
                            <a href="{{ route('user.betting-tickets.show', $ticket) }}" 
                               class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                Xem
                            </a>
                            <a href="{{ route('user.betting-tickets.edit', $ticket) }}" 
                               class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                                Sửa
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            @if($tickets->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $tickets->links() }}
            </div>
            @endif
        @else
            <div class="px-6 py-12 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Chưa có phiếu cược</h3>
                <p class="mt-1 text-sm text-gray-500">Bắt đầu bằng cách thêm phiếu cược đầu tiên.</p>
                <div class="mt-6">
                    <a href="{{ route('user.betting-tickets.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Thêm phiếu cược
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
