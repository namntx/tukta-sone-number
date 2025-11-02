@extends('layouts.app')

@section('title', 'Quản lý phiếu cược - Keki SaaS')

@section('content')
<div class="pb-4">
    <!-- Sticky Header -->
    <div class="sticky top-0 z-10 bg-white shadow-sm border-b border-gray-200 mb-3">
        <div class="px-3 py-2.5">
            <div class="flex items-center justify-between mb-2">
                <div class="flex-1 min-w-0">
                    <h1 class="text-lg font-bold text-gray-900">Phiếu cược</h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ \App\Support\Region::label($filterRegion ?? $globalRegion) }} · {{ \Carbon\Carbon::parse($filterDate ?? $globalDate)->format('d/m/Y') }}
                    </p>
                </div>
                <a href="{{ route('user.betting-tickets.create') }}" 
                   class="inline-flex items-center justify-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition ml-2">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Thêm
                </a>
            </div>
            
            <!-- Quick Filters - Compact -->
            <form method="GET" action="{{ route('user.betting-tickets.index') }}" class="space-y-2">
                <div class="grid grid-cols-2 gap-2">
                    <input type="date" name="date" value="{{ request('date', $filterDate ?? $globalDate) }}" 
                           class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <select name="region" class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="bac" {{ ($filterRegion ?? $globalRegion) == 'bac' ? 'selected' : '' }}>Bắc</option>
                        <option value="trung" {{ ($filterRegion ?? $globalRegion) == 'trung' ? 'selected' : '' }}>Trung</option>
                        <option value="nam" {{ ($filterRegion ?? $globalRegion) == 'nam' ? 'selected' : '' }}>Nam</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <select name="customer_id" class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">Tất cả KH</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                    <select name="result" class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <option value="">Tất cả KQ</option>
                        <option value="pending" {{ request('result') == 'pending' ? 'selected' : '' }}>Chờ</option>
                        <option value="win" {{ request('result') == 'win' ? 'selected' : '' }}>Ăn</option>
                        <option value="lose" {{ request('result') == 'lose' ? 'selected' : '' }}>Thua</option>
                    </select>
                </div>
                <button type="submit" class="w-full px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition">
                    Áp dụng bộ lọc
                </button>
            </form>
        </div>
        
        <!-- Quick Stats Bar -->
        <div class="px-3 pb-2 grid grid-cols-4 gap-2 text-center border-t border-gray-100 pt-2">
            <div>
                <div class="text-xs text-gray-500 mb-0.5">Hôm nay</div>
                <div class="text-sm font-bold text-gray-900">{{ $todayStats['total_tickets'] }}</div>
                <div class="text-xs {{ ($todayStats['total_win'] - $todayStats['total_lose']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format(abs($todayStats['total_win'] - $todayStats['total_lose']) / 1000, 0) }}k
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-500 mb-0.5">Tháng</div>
                <div class="text-sm font-bold text-gray-900">{{ $monthlyStats['total_tickets'] }}</div>
                <div class="text-xs {{ ($monthlyStats['total_win'] - $monthlyStats['total_lose']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format(abs($monthlyStats['total_win'] - $monthlyStats['total_lose']) / 1000, 0) }}k
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-500 mb-0.5">Năm</div>
                <div class="text-sm font-bold text-gray-900">{{ $yearlyStats['total_tickets'] }}</div>
                <div class="text-xs {{ ($yearlyStats['total_win'] - $yearlyStats['total_lose']) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format(abs($yearlyStats['total_win'] - $yearlyStats['total_lose']) / 1000, 0) }}k
                </div>
            </div>
            <div>
                <div class="text-xs text-gray-500 mb-0.5">Tổng cược</div>
                <div class="text-sm font-bold text-gray-900">{{ number_format($todayStats['total_bet'] / 1000, 0) }}k</div>
            </div>
        </div>
    </div>

    <!-- Tickets List -->
    <div class="space-y-1.5 px-3">
        @if($tickets->count() > 0)
            @foreach($tickets as $ticket)
            <a href="{{ route('user.betting-tickets.show', $ticket) }}" 
               class="block bg-white rounded-lg border border-gray-200 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all active:bg-gray-50">
                <div class="px-3 py-2.5">
                    <!-- Header: Customer & Result -->
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $ticket->customer->name }}</h3>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $ticket->result_badge_class }}">
                                {{ ucfirst($ticket->result) }}
                            </span>
                        </div>
                        <div class="flex-shrink-0 text-xs text-gray-500">
                            {{ $ticket->betting_date->format('d/m') }}
                        </div>
                    </div>
                    
                    <!-- Bet Info -->
                    <div class="mb-1.5">
                        <div class="text-xs text-gray-600 mb-0.5">
                            <span class="font-medium">{{ $ticket->bettingType->name }}</span>
                            <span class="mx-1">·</span>
                            <span>{{ $ticket->region }}</span>
                            <span class="mx-1">·</span>
                            <span class="truncate">{{ $ticket->station }}</span>
                        </div>
                        @if($ticket->parsed_message)
                        <div class="text-xs text-gray-500 truncate mt-0.5">{{ $ticket->parsed_message }}</div>
                        @endif
                    </div>
                    
                    <!-- Financial Info -->
                    <div class="flex items-center gap-3 text-xs pt-1.5 border-t border-gray-100">
                        <div class="flex items-center gap-1">
                            <span class="text-gray-500">Cược:</span>
                            <span class="font-semibold text-gray-900">{{ number_format($ticket->bet_amount / 1000, 1) }}k</span>
                        </div>
                        @if($ticket->result === 'win' && $ticket->win_amount > 0)
                        <div class="w-px h-3 bg-gray-300"></div>
                        <div class="flex items-center gap-1">
                            <span class="text-gray-500">Trúng:</span>
                            <span class="font-bold text-green-600">{{ number_format($ticket->win_amount / 1000, 1) }}k</span>
                        </div>
                        @endif
                        @if($ticket->payout_amount > 0)
                        <div class="w-px h-3 bg-gray-300"></div>
                        <div class="flex items-center gap-1">
                            <span class="text-gray-500">Trả:</span>
                            <span class="font-semibold text-red-600">{{ number_format($ticket->payout_amount / 1000, 1) }}k</span>
                        </div>
                        @endif
                    </div>
                </div>
            </a>
            @endforeach
            
            <!-- Pagination -->
            @if($tickets->hasPages())
            <div class="py-4 flex justify-center">
                {{ $tickets->links() }}
            </div>
            @endif
        @else
            <div class="py-16 text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gray-100 mb-4">
                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-medium text-gray-900 mb-1">Chưa có phiếu cược</h3>
                <p class="text-sm text-gray-500 mb-4">Bắt đầu bằng cách thêm phiếu cược đầu tiên</p>
                <a href="{{ route('user.betting-tickets.create') }}" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Thêm phiếu cược
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
