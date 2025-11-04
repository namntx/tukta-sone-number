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
                    $customerXac = 0; // Tổng tiền xác customer trả
                    $customerThang = 0; // Tổng tiền customer thắng
                    $pendingCount = 0; // Số phiếu chưa tính tiền
                    foreach ($customerTickets as $t) {
                        $customerXac += $t->betting_data['total_cost_xac'] ?? 0;
                        if ($t->result === 'win') {
                            $customerThang += $t->payout_amount;
                        }
                        if ($t->result === 'pending') {
                            $pendingCount++;
                        }
                    }
                    // Profit của Customer = Thắng - Xác
                    $customerProfit = $customerThang - $customerXac;
                @endphp
                <div class="mb-3">
                    <!-- Customer Header -->
                    <button type="button" onclick="toggleCustomer('{{ $customerId }}')" 
                            class="w-full grid grid-cols-12 gap-2 items-center px-3 py-2 bg-gray-100 rounded-lg border border-gray-200 hover:bg-gray-200 transition">
                        <!-- Icon + Tên -->
                        <div class="col-span-4 flex items-center gap-2 min-w-0">
                            <svg id="icon-{{ $customerId }}" class="w-4 h-4 text-gray-600 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                            <span class="text-xs font-semibold text-gray-900 truncate">{{ $customer->name }}</span>
                        </div>
                        
                        <!-- Status + Số phiếu -->
                        <div class="col-span-3 flex flex-col gap-1 items-center">
                            @if($pendingCount > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 whitespace-nowrap">
                                {{ $pendingCount }} chưa tính
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 whitespace-nowrap">
                                ✓ Tính xong
                            </span>
                            @endif
                            <span class="text-xs text-gray-500 whitespace-nowrap">({{ $customerTickets->count() }} phiếu)</span>
                        </div>
                        
                        <!-- Thông tin tài chính -->
                        <div class="col-span-5 flex flex-col items-end gap-0.5">
                            <div class="flex items-center gap-2 w-full justify-end">
                                <span class="text-[10px] text-gray-500">Xác:</span>
                                <span class="text-xs font-semibold text-blue-600 whitespace-nowrap">{{ number_format($customerXac / 1000, 1) }}k</span>
                            </div>
                            <div class="flex items-center gap-2 w-full justify-end">
                                <span class="text-[10px] text-gray-500">Thắng:</span>
                                <span class="text-xs font-semibold text-green-600 whitespace-nowrap">{{ number_format($customerThang / 1000, 1) }}k</span>
                            </div>
                            <div class="flex items-center gap-2 w-full justify-end pt-0.5 mt-0.5">
                                <span class="text-[10px] {{ $customerProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">Lời:</span>
                                <span class="text-xs font-bold {{ $customerProfit >= 0 ? 'text-green-600' : 'text-red-600' }} whitespace-nowrap">
                                    {{ $customerProfit >= 0 ? '+' : '' }}{{ number_format($customerProfit / 1000, 1) }}k
                                </span>
                            </div>
                        </div>
                    </button>
                    
                    <!-- Customer Tickets Grid -->
                    <div id="customer-{{ $customerId }}" class="hidden mt-2">
                        <div class="space-y-1">
                        @foreach($customerTickets as $ticket)
                        @php
                            $bettingData = $ticket->betting_data ?? [];
                            $displayNumbers = [];
                            
                            // Handle new format: array of bets
                            if (is_array($bettingData) && isset($bettingData[0]) && is_array($bettingData[0]) && isset($bettingData[0]['numbers'])) {
                                foreach ($bettingData as $bet) {
                                    $numbers = is_array($bet['numbers'] ?? []) ? $bet['numbers'] : [];
                                    if (!empty($numbers)) {
                                        $displayNumbers = array_merge($displayNumbers, $numbers);
                                    }
                                }
                            }
                            // Handle legacy format: single bet
                            elseif (isset($bettingData['numbers'])) {
                                $numbers = is_array($bettingData['numbers']) ? $bettingData['numbers'] : [];
                                if (!empty($numbers)) {
                                    $displayNumbers = $numbers;
                                }
                            }
                            
                            // Calculate profit
                            if ($ticket->result === 'win') {
                                $ticketProfit = $ticket->payout_amount;
                            } elseif ($ticket->result === 'lose') {
                                $costXac = $ticket->betting_data['total_cost_xac'] ?? 0;
                                $ticketProfit = -$costXac;
                            } else {
                                $ticketProfit = null;
                            }
                            
                            // Determine text color
                            $profitColor = $ticket->result === 'pending' ? 'text-gray-400' : ($ticketProfit >= 0 ? 'text-green-700' : 'text-red-700');
                        @endphp
                        <a href="{{ route('user.betting-tickets.show', $ticket) }}" 
                           class="block border-l-2 border {{ $ticket->result === 'win' ? 'border-l-green-500 border-gray-200' : ($ticket->result === 'lose' ? 'border-l-red-500 border-gray-200' : 'border-gray-300') }} hover:bg-gray-50 transition">
                            <div class="px-2 py-1.5 flex items-center justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-0.5">
                                        <span class="text-xs font-semibold {{ $ticket->result === 'win' ? 'text-green-900' : ($ticket->result === 'lose' ? 'text-red-900' : 'text-gray-900') }}">
                                            {{ Str::limit($ticket->bettingType->name, 12) }}
                                        </span>
                                        <span class="text-[10px] text-gray-500">·</span>
                                        <span class="text-[10px] text-gray-600">{{ Str::limit($ticket->station, 100) }}</span>
                                    </div>
                                    @if(!empty($displayNumbers))
                                    <div class="text-xs font-semibold {{ $ticket->result === 'win' ? 'text-green-700' : ($ticket->result === 'lose' ? 'text-red-700' : 'text-gray-600') }} truncate">
                                        {{ Str::limit(implode(' ', array_unique($displayNumbers)), 20) }}
                                    </div>
                                    @endif
                                </div>
                                <div class="flex-shrink-0 text-right">
                                    <div class="text-xs font-bold {{ $profitColor }}">
                                        @if($ticketProfit !== null)
                                        {{ $ticketProfit >= 0 ? '+' : '' }}{{ number_format($ticketProfit / 1000, 1) }}k
                                        @else
                                        -
                                        @endif
                                    </div>
                                    <div class="text-[10px] text-gray-500">
                                        Cược {{ number_format($ticket->bet_amount / 1000, 1) }}k
                                    </div>
                                </div>
                            </div>
                        </a>
                        @endforeach
                        </div>
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
        
        if (customerDiv) {
            if (customerDiv.classList.contains('hidden')) {
                customerDiv.classList.remove('hidden');
                if (icon) icon.style.transform = 'rotate(180deg)';
            } else {
                customerDiv.classList.add('hidden');
                if (icon) icon.style.transform = 'rotate(0deg)';
            }
        }
    };
});
</script>
@endpush
@endsection
