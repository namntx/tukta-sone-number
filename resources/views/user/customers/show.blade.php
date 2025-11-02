@extends('layouts.app')

@section('title', 'Chi tiết khách hàng - Keki SaaS')

@section('content')
<div class="pb-4">
    <!-- Sticky Header -->
    <div class="sticky top-0 z-10 bg-white shadow-sm border-b border-gray-200 mb-3">
        <div class="px-3 py-2.5">
            <div class="flex items-center justify-between mb-2">
                <div class="flex-1 min-w-0">
                    <h1 class="text-lg font-bold text-gray-900 truncate">{{ $customer->name }}</h1>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $customer->phone }}</p>
                </div>
                <div class="flex-shrink-0 flex items-center gap-2 ml-2">
                    <a href="{{ route('user.customers.edit', $customer) }}" 
                       class="inline-flex items-center justify-center p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </a>
                    <a href="{{ route('user.customers.index') }}" 
                       class="inline-flex items-center justify-center p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                </div>
            </div>
            
            <!-- Status Badge -->
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $customer->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $customer->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                </span>
                <span class="text-xs text-gray-500">
                    Tạo: {{ $customer->created_at->format('d/m/Y') }}
                </span>
            </div>
        </div>
    </div>

    <!-- TODAY'S FINANCIAL HIGHLIGHT -->
    <div class="px-3 mb-3">
        <div class="bg-gradient-to-r {{ $customer->daily_net_profit >= 0 ? 'from-green-500 to-green-600' : 'from-red-500 to-red-600' }} rounded-xl p-4 text-white shadow-lg">
            <div class="text-xs font-medium opacity-90 mb-1">Lãi/Lỗ hôm nay</div>
            <div class="text-3xl font-bold mb-3">
                {{ $customer->daily_net_profit >= 0 ? '+' : '' }}{{ number_format($customer->daily_net_profit / 1000, 1) }}k
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <div class="text-xs opacity-80 mb-0.5">Tiền ăn</div>
                    <div class="font-semibold">{{ number_format($customer->daily_win_amount / 1000, 1) }}k</div>
                </div>
                <div>
                    <div class="text-xs opacity-80 mb-0.5">Tiền thua</div>
                    <div class="font-semibold">{{ number_format($customer->daily_lose_amount / 1000, 1) }}k</div>
                </div>
            </div>
        </div>
    </div>

    <!-- STATISTICS GRID -->
    <div class="px-3 mb-3">
        <div class="bg-white rounded-lg border border-gray-200 p-3">
            <h3 class="text-xs font-semibold text-gray-700 mb-3 uppercase tracking-wide">Thống kê tổng quan</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="text-center py-2 bg-gray-50 rounded-lg">
                    <div class="text-xs text-gray-500 mb-1">Tổng ăn</div>
                    <div class="text-base font-bold text-green-600">{{ number_format($customer->total_win_amount / 1000, 0) }}k</div>
                </div>
                <div class="text-center py-2 bg-gray-50 rounded-lg">
                    <div class="text-xs text-gray-500 mb-1">Tổng thua</div>
                    <div class="text-base font-bold text-red-600">{{ number_format($customer->total_lose_amount / 1000, 0) }}k</div>
                </div>
                <div class="text-center py-2 bg-gray-50 rounded-lg">
                    <div class="text-xs text-gray-500 mb-1">Lãi/Lỗ tổng</div>
                    <div class="text-base font-bold {{ $customer->net_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($customer->net_profit / 1000, 0) }}k
                    </div>
                </div>
                <div class="text-center py-2 bg-gray-50 rounded-lg">
                    <div class="text-xs text-gray-500 mb-1">Tổng phiếu</div>
                    <div class="text-base font-bold text-gray-900">{{ $customer->bettingTickets->count() }}</div>
                </div>
            </div>
            
            <div class="mt-3 pt-3 border-t border-gray-200 grid grid-cols-2 gap-3">
                <div>
                    <div class="text-xs text-gray-500 mb-1">Tháng này</div>
                    <div class="text-sm font-semibold {{ $customer->monthly_net_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($customer->monthly_net_profit / 1000, 1) }}k
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 mb-1">Năm nay</div>
                    <div class="text-sm font-semibold {{ $customer->yearly_net_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($customer->yearly_net_profit / 1000, 1) }}k
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BETTING RATES -->
    @if($customer->bettingRates->count() > 0)
    <div class="px-3 mb-3">
        <div class="bg-white rounded-lg border border-gray-200 p-3">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Hệ số cược</h3>
                <span class="text-xs text-gray-500">{{ $customer->bettingRates->count() }} loại</span>
            </div>
            
            <div class="space-y-2 max-h-64 overflow-y-auto">
                @foreach($customer->bettingRates as $rate)
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900">{{ $rate->bettingType->name }}</div>
                        <div class="text-xs text-gray-500">{{ $rate->bettingType->code }}</div>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        <div class="text-right">
                            <div class="text-gray-500">Thu</div>
                            <div class="font-semibold text-gray-900">{{ number_format($rate->win_rate * 100, 1) }}%</div>
                        </div>
                        <div class="w-px h-6 bg-gray-300"></div>
                        <div class="text-right">
                            <div class="text-gray-500">Trả</div>
                            <div class="font-semibold text-gray-900">{{ number_format($rate->lose_rate * 100, 1) }}%</div>
                        </div>
                        <div class="ml-1">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs {{ $rate->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $rate->is_active ? '✓' : '✗' }}
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- RECENT TICKETS -->
    <div class="px-3 mb-3">
        <div class="bg-white rounded-lg border border-gray-200 p-3">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Phiếu cược gần đây</h3>
                <a href="{{ route('user.betting-tickets.index', ['customer' => $customer->id]) }}" 
                   class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                    Xem tất cả →
                </a>
            </div>
            
            @if($recentTickets->count() > 0)
            <div class="space-y-2">
                @foreach($recentTickets as $ticket)
                <a href="{{ route('user.betting-tickets.show', $ticket) }}" 
                   class="block p-2.5 bg-gray-50 rounded-lg hover:bg-gray-100 transition border border-transparent hover:border-indigo-300">
                    <div class="flex items-start justify-between mb-1">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-medium text-gray-900">{{ $ticket->bettingType->name }}</span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $ticket->result_badge_class }}">
                                    {{ ucfirst($ticket->result) }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ $ticket->betting_date->format('d/m/Y') }} · {{ $ticket->region }} - {{ $ticket->station }}
                            </div>
                        </div>
                        <div class="flex-shrink-0 ml-2 text-right">
                            <div class="text-xs text-gray-500 mb-0.5">Cược</div>
                            <div class="text-sm font-semibold text-gray-900">{{ number_format($ticket->bet_amount / 1000, 1) }}k</div>
                        </div>
                    </div>
                    @if($ticket->win_amount > 0)
                    <div class="mt-1.5 pt-1.5 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Tiền trúng</span>
                            <span class="text-sm font-bold text-green-600">{{ number_format($ticket->win_amount / 1000, 1) }}k</span>
                        </div>
                    </div>
                    @endif
                </a>
                @endforeach
            </div>
            @else
            <div class="text-center py-6">
                <svg class="mx-auto h-10 w-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-xs text-gray-500">Chưa có phiếu cược</p>
            </div>
            @endif
        </div>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="px-3 mb-3">
        <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('user.betting-tickets.create', ['customer' => $customer->id]) }}" 
               class="inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tạo phiếu cược
            </a>
            <a href="{{ route('user.customers.rates.edit', $customer) }}" 
               class="inline-flex items-center justify-center px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition border border-gray-300">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                </svg>
                Cấu hình giá
            </a>
        </div>
    </div>
</div>
@endsection
