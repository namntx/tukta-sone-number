@extends('layouts.app')

@section('title', 'Chi tiết phiếu cược - Keki SaaS')

@section('content')
<div class="pb-4">
    <!-- Sticky Header -->
    <div class="sticky top-0 z-10 bg-white shadow-sm border-b border-gray-200 mb-3">
        <div class="px-3 py-2.5">
            <div class="flex items-center justify-between mb-2">
                <div class="flex-1 min-w-0">
                    <h1 class="text-lg font-bold text-gray-900 truncate">Phiếu #{{ $bettingTicket->id }}</h1>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $bettingTicket->customer->name }}</p>
                </div>
                <div class="flex-shrink-0 flex items-center gap-2 ml-2">
                    <a href="{{ route('user.betting-tickets.edit', $bettingTicket) }}" 
                       class="inline-flex items-center justify-center p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </a>
                    <a href="{{ route('user.betting-tickets.index') }}" 
                       class="inline-flex items-center justify-center p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                </div>
            </div>
            
            <!-- Result Badge -->
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $bettingTicket->result_badge_class }}">
                    {{ ucfirst($bettingTicket->result) }}
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $bettingTicket->status_badge_class }}">
                    {{ ucfirst($bettingTicket->status) }}
                </span>
                <span class="text-xs text-gray-500">
                    {{ $bettingTicket->betting_date->format('d/m/Y') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Financial Highlight -->
    @php
        $profit = $bettingTicket->result === 'win' ? $bettingTicket->win_amount - $bettingTicket->bet_amount : 
                  ($bettingTicket->result === 'lose' ? -$bettingTicket->bet_amount : 0);
    @endphp
    <div class="px-3 mb-3">
        <div class="bg-gradient-to-r {{ $profit >= 0 ? 'from-green-500 to-green-600' : 'from-red-500 to-red-600' }} rounded-xl p-4 text-white shadow-lg">
            <div class="text-xs font-medium opacity-90 mb-1">Lãi/Lỗ</div>
            <div class="text-3xl font-bold mb-3">
                {{ $profit >= 0 ? '+' : '' }}{{ number_format($profit / 1000, 1) }}k
            </div>
            <div class="grid grid-cols-3 gap-3 text-sm">
                <div>
                    <div class="text-xs opacity-80 mb-0.5">Cược</div>
                    <div class="font-semibold">{{ number_format($bettingTicket->bet_amount / 1000, 1) }}k</div>
                </div>
                @if($bettingTicket->result === 'win' && $bettingTicket->win_amount > 0)
                <div>
                    <div class="text-xs opacity-80 mb-0.5">Trúng</div>
                    <div class="font-semibold">{{ number_format($bettingTicket->win_amount / 1000, 1) }}k</div>
                </div>
                @endif
                @if($bettingTicket->payout_amount > 0)
                <div>
                    <div class="text-xs opacity-80 mb-0.5">Trả</div>
                    <div class="font-semibold">{{ number_format($bettingTicket->payout_amount / 1000, 1) }}k</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Customer Info -->
    <div class="px-3 mb-3">
        <div class="bg-white rounded-lg border border-gray-200 p-3">
            <h3 class="text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Thông tin khách hàng</h3>
            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Tên:</span>
                    <span class="font-medium text-gray-900">{{ $bettingTicket->customer->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">SĐT:</span>
                    <span class="font-medium text-gray-900">{{ $bettingTicket->customer->phone }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bet Details -->
    <div class="px-3 mb-3">
        <div class="bg-white rounded-lg border border-gray-200 p-3">
            <h3 class="text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Chi tiết cược</h3>
            
            <!-- Betting Type -->
            <div class="mb-3 p-2.5 bg-gray-50 rounded-lg">
                <div class="text-xs text-gray-500 mb-0.5">Loại cược</div>
                <div class="text-sm font-semibold text-gray-900">{{ $bettingTicket->bettingType->name }}</div>
                <div class="text-xs text-gray-500">{{ $bettingTicket->bettingType->code }}</div>
            </div>
            
            <!-- Region & Station -->
            <div class="grid grid-cols-2 gap-2 mb-3">
                <div class="p-2.5 bg-gray-50 rounded-lg">
                    <div class="text-xs text-gray-500 mb-0.5">Miền</div>
                    <div class="text-sm font-medium text-gray-900">{{ \App\Support\Region::label($bettingTicket->region) }}</div>
                </div>
                <div class="p-2.5 bg-gray-50 rounded-lg">
                    <div class="text-xs text-gray-500 mb-0.5">Đài</div>
                    <div class="text-sm font-medium text-gray-900 truncate">{{ $bettingTicket->station }}</div>
                </div>
            </div>
            
            <!-- Numbers -->
            @php
                $bettingData = $bettingTicket->betting_data ?? [];
                $numbers = [];
                // Handle both legacy and new format
                if (isset($bettingData['numbers'])) {
                    $numbers = is_array($bettingData['numbers']) ? $bettingData['numbers'] : [];
                } elseif (is_array($bettingData) && isset($bettingData[0]['numbers'])) {
                    // New format: array of bets
                    foreach ($bettingData as $bet) {
                        if (isset($bet['numbers']) && is_array($bet['numbers'])) {
                            $numbers = array_merge($numbers, $bet['numbers']);
                        }
                    }
                }
            @endphp
            
            @if(!empty($numbers))
            <div class="mb-3">
                <div class="text-xs text-gray-500 mb-1.5">Số cược</div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach(array_unique($numbers) as $number)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $number }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Messages -->
            <div class="space-y-2">
                <div>
                    <div class="text-xs text-gray-500 mb-1">Tin nhắn gốc</div>
                    <div class="text-xs text-gray-900 bg-gray-50 rounded p-2 break-words">{{ $bettingTicket->original_message }}</div>
                </div>
                @if($bettingTicket->parsed_message)
                <div>
                    <div class="text-xs text-gray-500 mb-1">Tin nhắn đã phân tích</div>
                    <div class="text-xs text-gray-900 bg-gray-50 rounded p-2 break-words">{{ $bettingTicket->parsed_message }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Financial Breakdown -->
    @if($bettingTicket->result !== 'pending')
    <div class="px-3 mb-3">
        <div class="bg-white rounded-lg border border-gray-200 p-3">
            <h3 class="text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Phân tích tài chính</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
                    <span class="text-gray-500">Tiền cược</span>
                    <span class="font-semibold text-gray-900">{{ number_format($bettingTicket->bet_amount / 1000, 1) }}k</span>
                </div>
                @if($bettingTicket->result === 'win' && $bettingTicket->win_amount > 0)
                <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
                    <span class="text-gray-500">Tiền trúng</span>
                    <span class="font-semibold text-green-600">{{ number_format($bettingTicket->win_amount / 1000, 1) }}k</span>
                </div>
                @endif
                @if($bettingTicket->payout_amount > 0)
                <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
                    <span class="text-gray-500">Tiền trả</span>
                    <span class="font-semibold text-red-600">{{ number_format($bettingTicket->payout_amount / 1000, 1) }}k</span>
                </div>
                @endif
                <div class="flex justify-between items-center py-1.5">
                    <span class="text-gray-500 font-medium">Lãi/Lỗ</span>
                    <span class="text-base font-bold {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $profit >= 0 ? '+' : '' }}{{ number_format($profit / 1000, 1) }}k
                    </span>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Action Buttons -->
    <div class="px-3 mb-3">
        <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('user.betting-tickets.edit', $bettingTicket) }}" 
               class="inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Chỉnh sửa
            </a>
            <form method="POST" action="{{ route('user.betting-tickets.destroy', $bettingTicket) }}" 
                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa phiếu cược này?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Xóa
                </button>
            </form>
        </div>
    </div>

    <!-- Metadata -->
    <div class="px-3 mb-3">
        <div class="bg-gray-50 rounded-lg p-3">
            <div class="text-xs text-gray-500">
                <div class="mb-1">Ngày tạo: {{ $bettingTicket->created_at->format('d/m/Y H:i') }}</div>
                @if($bettingTicket->updated_at != $bettingTicket->created_at)
                <div>Cập nhật: {{ $bettingTicket->updated_at->format('d/m/Y H:i') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
