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
                <div class="flex items-center gap-2">
                    <a href="{{ route('user.betting-tickets.report') }}" 
                       class="inline-flex items-center justify-center px-3 py-1.5 bg-purple-600 text-white text-xs font-medium rounded-lg hover:bg-purple-700 transition">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Báo cáo
                    </a>
                    <button type="button" id="settle-tickets-btn" 
                            class="inline-flex items-center justify-center px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-5m-3 5h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        Tính tiền
                    </button>
                </div>
            </div>
            
            <!-- Quick Filters - Compact -->
            <!-- <form method="GET" action="{{ route('user.betting-tickets.index') }}" class="space-y-2">
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
            </form> -->
        </div>
    </div>

    <!-- Tickets Grid -->
    <div class="pb-4">
        @if($tickets->count() > 0)
            @php
                // Group tickets by customer
                $groupedTickets = $tickets->groupBy('customer_id');
            @endphp
            @foreach($groupedTickets as $customerId => $customerTickets)
                @php
                    $customer = $customerTickets->first()->customer;
                    // Tính tổng tiền xác customer trả và tổng tiền customer thắng
                    $customerXac = 0;
                    $customerThang = 0;
                    $pendingCount = 0;
                    foreach ($customerTickets as $t) {
                        $customerXac += $t->betting_data['total_cost_xac'] ?? 0;
                        if ($t->result === 'win') {
                            $customerThang += $t->payout_amount;
                        }
                        if ($t->result === 'pending') {
                            $pendingCount++;
                        }
                    }
                    $customerProfit = $customerThang - $customerXac;
                @endphp
                
                <!-- Customer Card -->
                <div class="mb-3 bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <!-- Customer Header - Click để mở/đóng -->
                    <button type="button" onclick="toggleCustomer('{{ $customerId }}')" 
                            class="w-full px-4 py-2.5 bg-white hover:bg-gray-50 transition-colors flex items-center justify-between gap-3 border-b border-gray-100">
                        <div class="flex items-center gap-2.5 flex-1 min-w-0">
                            <!-- Arrow Icon -->
                            <svg id="icon-{{ $customerId }}" class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                            
                            <!-- Customer Name và Thông tin -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <h3 class="text-sm font-semibold text-gray-900">{{ $customer->name }}</h3>
                                    <span class="text-xs text-gray-500">•</span>
                                    <!-- <span class="text-xs text-gray-600">{{ $customerTickets->count() }} phiếu</span> -->
                            @if($pendingCount > 0)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium text-orange-700 bg-orange-50 border border-orange-200">
                                            ⚠️ {{ $pendingCount }} chưa tính
                            </span>
                            @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium text-green-700 bg-green-50 border border-green-200">
                                            ✓ Đã tính xong
                            </span>
                            @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Financial Summary - Compact -->
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <div class="text-right">
                                <div class="text-xs text-gray-500">Xác</div>
                                <div class="text-xs font-semibold text-gray-700">{{ number_format($customerXac / 1000, 0) }}k</div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500">Thắng</div>
                                <div class="text-xs font-semibold text-green-600">{{ number_format($customerThang / 1000, 0) }}k</div>
                            </div>
                            <div class="text-right border-l border-gray-200 pl-3">
                                <div class="text-xs text-gray-500">Lời/Lỗ</div>
                                <div class="text-xs font-bold {{ $customerProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $customerProfit >= 0 ? '+' : '' }}{{ number_format($customerProfit / 1000, 0) }}k
                                </div>
                            </div>
                        </div>
                    </button>
                    
                    <!-- Messages List - Ẩn mặc định -->
                    <div id="customer-{{ $customerId }}" class="hidden" style="display: none;">
                        @php
                            $ticketsByMessage = $customerTickets->groupBy('original_message');
                        @endphp
                        
                        @foreach($ticketsByMessage as $originalMessage => $messageTickets)
                            @php
                                $messageId = md5($customerId . '_' . $originalMessage);
                                $messageCount = $messageTickets->count();
                            @endphp
                            
                            <!-- Message Card -->
                            <div class="border-b border-gray-200 last:border-b-0">
                                <!-- Message Header -->
                                <div class="w-full px-3 sm:px-4 py-2.5 bg-white hover:bg-gray-50 transition-colors flex items-center justify-between gap-2">
                                    <button type="button" onclick="toggleMessage('{{ $messageId }}')" 
                                            class="flex items-center gap-2.5 flex-1 min-w-0 text-left">
                                        <!-- Arrow Icon -->
                                        <svg id="icon-msg-{{ $messageId }}" class="w-4 h-4 text-gray-500 transition-transform duration-200 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                        
                                        <!-- Message Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs sm:text-sm font-medium text-gray-900 break-words">
                                                {{ Str::limit($originalMessage, 100) }}
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                {{ $messageCount }} phiếu
                                            </div>
                                        </div>
                                    </button>
                                    
                                    <!-- Delete Button -->
                                    <form method="POST" 
                                          action="{{ route('user.betting-tickets.destroy-by-message', $messageTickets->first()) }}" 
                                          onsubmit="return confirm('⚠️ Bạn có chắc muốn xóa TẤT CẢ {{ $messageCount }} phiếu cược này?\n\nHành động này KHÔNG THỂ hoàn tác!')"
                                          class="flex-shrink-0"
                                          onclick="event.stopPropagation();">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="px-2 py-1.5 text-xs font-medium text-red-600 bg-red-0 rounded-md hover:bg-red-100 hover:text-red-700 transition-colors border border-red-200">
                                            Xoá
                                        </button>
                                    </form>
                                </div>
                                
                                <!-- Statistics Table - Ẩn mặc định -->
                                <div id="message-{{ $messageId }}" class="hidden bg-gray-50 border-t border-gray-200">
                                    @php
                                        // Group tickets: tách riêng bao lô 2, 3, 4 số
                                        $ticketsByType = $messageTickets->groupBy(function($ticket) {
                                            $bettingType = $ticket->bettingType;
                                            $typeId = $ticket->betting_type_id;
                                            
                                            // Nếu là bao lô, thêm digits vào key để tách riêng
                                            if ($bettingType && $bettingType->code === 'bao_lo') {
                                                $bettingData = $ticket->betting_data ?? [];
                                                $digits = null;
                                                
                                                // Tìm digits trong betting_data
                                                if (is_array($bettingData)) {
                                                    // Kiểm tra trong mảng đầu tiên nếu là mảng của bets
                                                    if (isset($bettingData[0]) && is_array($bettingData[0])) {
                                                        $digits = $bettingData[0]['meta']['digits'] ?? null;
                                                    } elseif (isset($bettingData['meta']['digits'])) {
                                                        $digits = $bettingData['meta']['digits'];
                                                    }
                                                }
                                                
                                                // Nếu không tìm thấy digits, mặc định là 2
                                                $digits = $digits ?? 2;
                                                
                                                return $typeId . '_bao_lo_' . $digits;
                                            }
                                            
                                            return $typeId;
                                        });
                                        
                                        $totalBetAmount = 0;
                                        $totalWinAmount = 0;
                                        $totalXacAmount = 0;
                                        $totalEatThua = 0;
                                    @endphp
                                    
                                    <div class="px-2 sm:px-3 py-2">
                                        <div class="overflow-x-auto -mx-2 sm:mx-0">
                                            <table class="w-full bg-white rounded-lg border border-gray-200 shadow-sm">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="px-2 py-2 text-left text-xs font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap">Loại cược</th>
                                                        <th class="px-2 py-2 text-right text-xs font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap">Cược</th>
                                                        <th class="px-2 py-2 text-right text-xs font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap">Ăn</th>
                                                        <th class="px-2 py-2 text-right text-xs font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap">Xác</th>
                                                        <th class="px-2 py-2 text-right text-xs font-semibold text-gray-700 border-b border-gray-200 whitespace-nowrap">Ăn/Thua</th>
                                                    </tr>
                                                </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @foreach($ticketsByType as $groupKey => $typeTickets)
                                                    @php
                                                        $bettingType = $typeTickets->first()->bettingType;
                                                        $rawBetAmount = $typeTickets->sum('bet_amount');
                                                        
                                                        // Tính tiền cược đã nhân cho đá xiên (2 đài: * 1, 3 đài: * 3, 4 đài: * 6)
                                                        $typeBetAmount = $rawBetAmount;
                                                        if ($bettingType && $bettingType->code === 'da_xien') {
                                                            // Tính tổng tiền cược đã nhân cho từng ticket
                                                            $typeBetAmount = $typeTickets->sum(function($ticket) {
                                                                $bettingData = $ticket->betting_data ?? [];
                                                                $meta = [];
                                                                
                                                                // Lấy meta từ betting_data
                                                                if (is_array($bettingData) && isset($bettingData[0]) && is_array($bettingData[0])) {
                                                                    $meta = $bettingData[0]['meta'] ?? [];
                                                                } elseif (isset($bettingData['meta'])) {
                                                                    $meta = $bettingData['meta'];
                                                                }
                                                                
                                                                $stationCount = (int)($meta['dai_count'] ?? 0);
                                                                if (!$stationCount && !empty($meta['station_pairs']) && is_array($meta['station_pairs'])) {
                                                                    $names = [];
                                                                    foreach ($meta['station_pairs'] as $p) {
                                                                        if (is_array($p) && count($p) === 2) {
                                                                            $names[$p[0]] = true; $names[$p[1]] = true;
                                                                        }
                                                                    }
                                                                    $stationCount = count($names);
                                                                }
                                                                
                                                                // Hệ số nhân: 2 đài = 1, 3 đài = 3, 4 đài = 6
                                                                $betMultiplier = match($stationCount) {
                                                                    2 => 1,
                                                                    3 => 3,
                                                                    4 => 6,
                                                                    default => 1,
                                                                };
                                                                
                                                                return $ticket->bet_amount * $betMultiplier;
                                                            });
                                                        }
                                                        
                                                        // Cột "Ăn" hiển thị win_amount (tiền cược * số lô trúng), không phải bet_amount
                                                        $typeWinAmount = $typeTickets->where('result', 'win')->sum('win_amount');
                                                        $typePayoutAmount = $typeTickets->where('result', 'win')->sum('payout_amount');
                                                        $typeXacAmount = $typeTickets->sum(function($t) { return $t->betting_data['total_cost_xac'] ?? 0; });
                                                        
                                                        // Xác định tên hiển thị: nếu là bao lô thì thêm số digits
                                                        $displayName = $bettingType->name;
                                                        if ($bettingType && $bettingType->code === 'bao_lo') {
                                                            // Lấy digits từ group key hoặc từ ticket đầu tiên
                                                            if (preg_match('/_bao_lo_(\d+)$/', $groupKey, $matches)) {
                                                                $digits = (int)$matches[1];
                                                                $displayName = 'Bao lô ' . $digits . ' số';
                                                            } else {
                                                                // Fallback: lấy từ betting_data
                                                                $firstTicket = $typeTickets->first();
                                                                $bettingData = $firstTicket->betting_data ?? [];
                                                                $digits = null;
                                                                if (is_array($bettingData)) {
                                                                    if (isset($bettingData[0]) && is_array($bettingData[0])) {
                                                                        $digits = $bettingData[0]['meta']['digits'] ?? 2;
                                                                    } elseif (isset($bettingData['meta']['digits'])) {
                                                                        $digits = $bettingData['meta']['digits'];
                                                                    }
                                                                }
                                                                $digits = $digits ?? 2;
                                                                $displayName = 'Bao lô ' . $digits . ' số';
                                                            }
                                                        }
                                                        
                                                        if ($typePayoutAmount > 0) {
                                                            $typeEatThua = $typePayoutAmount - $typeXacAmount;
                                                            $typeEatThuaColor = $typeEatThua >= 0 ? 'text-green-700' : 'text-red-700';
                                                        } else {
                                                            $typeEatThua = -$typeXacAmount;
                                                            $typeEatThuaColor = 'text-red-700';
                                                        }
                                                        
                                                        $totalBetAmount += $typeBetAmount;
                                                        $totalWinAmount += $typeWinAmount;
                                                        $totalXacAmount += $typeXacAmount;
                                                        $totalEatThua += $typeEatThua;
                                                        
                                                        // Tạo unique ID cho toggle
                                                        $uniqueTypeId = str_replace(['_', '-'], '', $groupKey);
                                                    @endphp
                                                    
                                                    <!-- Betting Type Row -->
                                                    <tr class="hover:bg-blue-50 transition-colors cursor-pointer" 
                                                        onclick="toggleTypeTickets('{{ $messageId }}-{{ $uniqueTypeId }}')">
                                                        <td class="px-2 py-2">
                                                            <div class="flex items-center gap-1">
                                                                <span class="text-xs font-medium text-gray-900 truncate max-w-[120px] sm:max-w-none">{{ $displayName }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="px-2 py-2 text-right text-xs whitespace-nowrap">
                                                            @php
                                                                $betAmountInK = $typeBetAmount / 1000;
                                                                echo $typeBetAmount % 1000 == 0 ? (int)$betAmountInK : number_format($betAmountInK, 1, '.', '');
                                                            @endphp
                                                        </td>
                                                        <td class="px-2 py-2 text-right text-xs whitespace-nowrap {{ $typeWinAmount > 0 ? 'text-green-700 font-semibold' : 'text-gray-500' }}">
                                                            @php
                                                                $winAmountInK = $typeWinAmount / 1000;
                                                                echo $typeWinAmount % 1000 == 0 ? (int)$winAmountInK : number_format($winAmountInK, 1, '.', '');
                                                            @endphp
                                                        </td>
                                                        <td class="px-2 py-2 text-right text-xs text-blue-700 font-semibold whitespace-nowrap">{{ number_format($typeXacAmount / 1000, 0) }}k</td>
                                                        <td class="px-2 py-2 text-right text-xs font-semibold whitespace-nowrap {{ $typeEatThuaColor }}">
                                                            {{ $typeEatThua >= 0 ? '+' : '' }}{{ number_format($typeEatThua / 1000, 0) }}k
                                                        </td>
                                                    </tr>
                                                    
                                                    <!-- Tickets List Row - Ẩn mặc định -->
                                                    <tr id="tickets-{{ $messageId }}-{{ $uniqueTypeId }}" class="hidden">
                                                        <td colspan="5" class="px-0 py-0">
                                                            <div class="bg-gray-50 border-t-2 border-blue-200 px-3 py-2 space-y-2 max-h-80 overflow-y-auto">
                                                                @foreach($typeTickets as $ticket)
                        @php
                            $bettingData = $ticket->betting_data ?? [];
                            $displayNumbers = [];
                            
                            if (is_array($bettingData) && isset($bettingData[0]) && is_array($bettingData[0]) && isset($bettingData[0]['numbers'])) {
                                foreach ($bettingData as $bet) {
                                    $numbers = is_array($bet['numbers'] ?? []) ? $bet['numbers'] : [];
                                    if (!empty($numbers)) {
                                        $displayNumbers = array_merge($displayNumbers, $numbers);
                                    }
                                }
                                                                        } elseif (isset($bettingData['numbers'])) {
                                $numbers = is_array($bettingData['numbers']) ? $bettingData['numbers'] : [];
                                if (!empty($numbers)) {
                                    $displayNumbers = $numbers;
                                }
                            }
                            
                            if ($ticket->result === 'win') {
                                $ticketProfit = $ticket->payout_amount;
                            } elseif ($ticket->result === 'lose') {
                                $costXac = $ticket->betting_data['total_cost_xac'] ?? 0;
                                $ticketProfit = -$costXac;
                            } else {
                                $ticketProfit = null;
                            }
                            
                            $profitColor = $ticket->result === 'pending' ? 'text-gray-400' : ($ticketProfit >= 0 ? 'text-green-700' : 'text-red-700');
                            
                            // Tính số lần trúng (số lô) cho bao lô
                            $winCount = null;
                            if ($ticket->result === 'win' && $ticket->win_amount > 0 && $ticket->bet_amount > 0) {
                                // win_amount = bet_amount * win_count
                                // win_count = win_amount / bet_amount
                                $winCount = round($ticket->win_amount / $ticket->bet_amount, 0);
                            }
                        @endphp
                                                                    
                                                                    <!-- Individual Ticket Card -->
                                                                    <div class="bg-white border border-gray-200 rounded-md p-2.5 hover:shadow-sm transition-shadow">
                                                                        <div class="flex items-start justify-between gap-2.5">
                                                                            <!-- Ticket Info -->
                                <div class="flex-1 min-w-0">
                                                                                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold {{ $ticket->result === 'win' ? 'bg-green-100 text-green-700' : ($ticket->result === 'lose' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                                                                                        @if($ticket->result === 'win') ✓
                                                                                        @elseif($ticket->result === 'lose') ✗
                                                                                        @else ⏳
                                                                                        @endif
                                        </span>
                                                                                    <span class="text-xs font-medium text-gray-900">{{ Str::limit($ticket->station, 200) }}</span>
                                                                                    <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($ticket->created_at)->format('H:i') }}</span>
                                    </div>
                                                                                
                                    @if(!empty($displayNumbers))
                                                                                <div class="text-xs font-medium {{ $ticket->result === 'win' ? 'text-green-700' : ($ticket->result === 'lose' ? 'text-red-700' : 'text-gray-600') }} mb-1.5">
                                                                                    {{ Str::limit(implode(' ', array_unique($displayNumbers)), 40) }}
                                    </div>
                                    @endif
                                                                                
                                                                                <div class="flex items-center gap-2.5 text-xs flex-wrap">
                                                                                    <span class="text-gray-600">
                                                                                        Cược: <span class="font-semibold text-gray-900">{{ number_format($ticket->bet_amount / 1000, 1) }}k</span>
                                                                                    </span>
                                                                                    @if($winCount !== null && $winCount > 0)
                                                                                        <span class="text-green-700 font-semibold">
                                                                                            Trúng {{ $winCount }} lô
                                                                                        </span>
                                                                                    @endif
                                        @if($ticketProfit !== null)
                                                                                        <span class="font-semibold {{ $profitColor }}">
                                        {{ $ticketProfit >= 0 ? '+' : '' }}{{ number_format($ticketProfit / 1000, 1) }}k
                                                                                        </span>
                                        @endif
                                    </div>
                                                                            </div>
                                                                            
                                                                            <!-- Action Buttons -->
                                                                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                                                                <a href="{{ route('user.betting-tickets.edit', $ticket) }}" 
                                                                                   class="px-2 py-1 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-md hover:bg-indigo-100 transition-colors border border-indigo-200">
                                                                                    ✏️
                                                                                </a>
                                                                                <form method="POST" action="{{ route('user.betting-tickets.destroy', $ticket) }}" 
                                                                                      onsubmit="return confirm('⚠️ Bạn có chắc muốn xóa phiếu cược này?')" 
                                                                                      class="inline">
                                                                                    @csrf
                                                                                    @method('DELETE')
                                                                                    <button type="submit" 
                                                                                            class="px-2 py-1 text-xs font-medium text-red-600 bg-red-50 rounded-md hover:bg-red-100 transition-colors border border-red-200">
                                                                                        🗑️
                                                                                    </button>
                                                                                </form>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                                <tfoot class="bg-gray-200 border-t-2 border-gray-300">
                                                    <tr>
                                                        <td class="px-2 py-2 font-semibold text-xs text-gray-900 whitespace-nowrap">📊 Tổng:</td>
                                                        <td class="px-2 py-2 text-right font-semibold text-xs text-gray-900 whitespace-nowrap">
                                                            @php
                                                                $totalBetAmountInK = $totalBetAmount / 1000;
                                                                echo $totalBetAmount % 1000 == 0 ? (int)$totalBetAmountInK : number_format($totalBetAmountInK, 1, '.', '');
                                                            @endphp
                                                        </td>
                                                        <td class="px-2 py-2 text-right font-semibold text-xs whitespace-nowrap {{ $totalWinAmount > 0 ? 'text-green-700' : 'text-gray-900' }}">
                                                            @php
                                                                $totalWinAmountInK = $totalWinAmount / 1000;
                                                                echo $totalWinAmount % 1000 == 0 ? (int)$totalWinAmountInK : number_format($totalWinAmountInK, 1, '.', '');
                                                            @endphp
                                                        </td>
                                                        <td class="px-2 py-2 text-right font-semibold text-xs text-blue-700 whitespace-nowrap">{{ number_format($totalXacAmount / 1000, 0) }}k</td>
                                                        <td class="px-2 py-2 text-right font-semibold text-xs whitespace-nowrap {{ $totalEatThua >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                                            {{ $totalEatThua >= 0 ? '+' : '' }}{{ number_format($totalEatThua / 1000, 0) }}k
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
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

<!-- Settlement Result Modal -->
<div id="settlement-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Kết quả tính tiền</h3>
            <button type="button" id="close-modal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="px-4 py-4">
            <div id="settlement-result-content"></div>
        </div>
        <div class="px-4 py-3 border-t border-gray-200 flex justify-end gap-2">
            <button type="button" id="close-modal-btn" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                Đóng
            </button>
            <button type="button" id="reload-page-btn" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Tải lại trang
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const settleBtn = document.getElementById('settle-tickets-btn');
    const modal = document.getElementById('settlement-modal');
    const closeModal = document.getElementById('close-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const reloadBtn = document.getElementById('reload-page-btn');
    const resultContent = document.getElementById('settlement-result-content');

    if (settleBtn) {
        settleBtn.addEventListener('click', function() {
            if (settleBtn.disabled) return;
            
            settleBtn.disabled = true;
            settleBtn.innerHTML = '<svg class="w-4 h-4 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Đang tính...';
            
            fetch('{{ route("user.betting-tickets.settle-by-global") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                settleBtn.disabled = false;
                settleBtn.innerHTML = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-5m-3 5h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg> Tính tiền';
                
                if (data.success) {
                    let html = '<div class="space-y-3">';
                    
                    if (data.message) {
                        html += `<div class="p-3 bg-green-50 border border-green-200 rounded-lg"><p class="text-sm text-green-800">${data.message}</p></div>`;
                    }
                    
                    if (data.result) {
                        html += '<div class="space-y-2">';
                        html += `<div class="flex justify-between text-sm"><span class="text-gray-600">Tổng phiếu:</span><span class="font-semibold">${data.result.total}</span></div>`;
                        html += `<div class="flex justify-between text-sm"><span class="text-gray-600">Đã quyết toán:</span><span class="font-semibold text-green-600">${data.result.settled}</span></div>`;
                        
                        if (data.result.failed > 0) {
                            html += `<div class="flex justify-between text-sm"><span class="text-gray-600">Thất bại:</span><span class="font-semibold text-red-600">${data.result.failed}</span></div>`;
                        }
                        
                        if (data.result.total_win !== undefined) {
                            html += `<div class="pt-2 border-t border-gray-200 flex justify-between text-sm"><span class="text-gray-600">Tổng tiền thắng:</span><span class="font-bold text-green-600">${formatCurrency(data.result.total_win)}</span></div>`;
                        }
                        
                        if (data.result.total_payout !== undefined) {
                            html += `<div class="flex justify-between text-sm"><span class="text-gray-600">Tổng tiền trả:</span><span class="font-bold text-red-600">${formatCurrency(data.result.total_payout)}</span></div>`;
                        }
                        
                        html += '</div>';
                    }
                    
                    html += '</div>';
                    resultContent.innerHTML = html;
                } else {
                    let html = '<div class="space-y-3">';
                    html += `<div class="p-3 bg-red-50 border border-red-200 rounded-lg"><p class="text-sm text-red-800">${data.message || 'Có lỗi xảy ra'}</p></div>`;
                    
                    if (data.errors && data.errors.length > 0) {
                        html += '<ul class="list-disc list-inside text-sm text-red-700 space-y-1">';
                        data.errors.forEach(error => {
                            html += `<li>${error}</li>`;
                        });
                        html += '</ul>';
                    }
                    
                    html += '</div>';
                    resultContent.innerHTML = html;
                }
                
                modal.classList.remove('hidden');
            })
            .catch(error => {
                settleBtn.disabled = false;
                settleBtn.innerHTML = '<svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-5m-3 5h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg> Tính tiền';
                
                resultContent.innerHTML = `<div class="p-3 bg-red-50 border border-red-200 rounded-lg"><p class="text-sm text-red-800">Lỗi: ${error.message}</p></div>`;
                modal.classList.remove('hidden');
            });
        });
    }
    
    function closeModalHandler() {
        modal.classList.add('hidden');
    }
    
    if (closeModal) closeModal.addEventListener('click', closeModalHandler);
    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModalHandler);
    
    if (reloadBtn) {
        reloadBtn.addEventListener('click', function() {
            window.location.reload();
        });
    }
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModalHandler();
        }
    });
    
    function formatCurrency(amount) {
        if (typeof amount !== 'number') {
            amount = parseFloat(amount) || 0;
        }
        if (amount >= 1000000) {
            return (amount / 1000000).toFixed(1) + 'M';
        } else if (amount >= 1000) {
            return (amount / 1000).toFixed(1) + 'k';
        }
        return Math.round(amount).toLocaleString('vi-VN');
    }
    
    // Toggle customer tickets
    window.toggleCustomer = function(customerId) {
        const customerDiv = document.getElementById('customer-' + customerId);
        const icon = document.getElementById('icon-' + customerId);
        
        if (!customerDiv) {
            console.error('Cannot find customer div:', 'customer-' + customerId);
            return;
        }
        
        // Kiểm tra trạng thái hiện tại
        const isHidden = customerDiv.style.display === 'none' || 
                        customerDiv.classList.contains('hidden') ||
                        window.getComputedStyle(customerDiv).display === 'none';
        
        if (isHidden) {
            // Hiện: xóa cả class và style
                customerDiv.classList.remove('hidden');
            customerDiv.style.display = 'block';
            if (icon) icon.style.transform = 'rotate(180deg)';
        } else {
            // Ẩn: thêm cả class và style
            customerDiv.classList.add('hidden');
            customerDiv.style.display = 'none';
            if (icon) icon.style.transform = 'rotate(0deg)';
        }
    };
    
    // Toggle message tickets
    window.toggleMessage = function(messageId) {
        const messageDiv = document.getElementById('message-' + messageId);
        const icon = document.getElementById('icon-msg-' + messageId);
        
        if (messageDiv) {
            const isHidden = messageDiv.classList.contains('hidden');
            
            if (isHidden) {
                messageDiv.classList.remove('hidden');
                if (icon) icon.style.transform = 'rotate(180deg)';
            } else {
                messageDiv.classList.add('hidden');
                if (icon) icon.style.transform = 'rotate(0deg)';
            }
        }
    };
    
    // Toggle type tickets list
    window.toggleTypeTickets = function(typeId) {
        const ticketsRow = document.getElementById('tickets-' + typeId);
        const icon = document.getElementById('icon-type-' + typeId);
        
        if (!ticketsRow) {
            console.error('Cannot find tickets row:', 'tickets-' + typeId);
            return;
        }
        
        if (!icon) {
            console.error('Cannot find icon:', 'icon-type-' + typeId);
        }
        
        const isHidden = ticketsRow.classList.contains('hidden');
        
        if (isHidden) {
            ticketsRow.classList.remove('hidden');
            if (icon) icon.style.transform = 'rotate(180deg)';
        } else {
            ticketsRow.classList.add('hidden');
            if (icon) icon.style.transform = 'rotate(0deg)';
        }
    };
});
</script>
@endpush
@endsection
