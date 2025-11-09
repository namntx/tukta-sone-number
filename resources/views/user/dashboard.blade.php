@extends('layouts.app')

@section('title', 'Dashboard - Keki SaaS')

@section('content')
<div class="space-y-4 md:space-y-6 max-w-7xl mx-auto">
    @if($subscriptionStatus === 'active')
    <!-- Betting Message Input Form - Premium Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg md:text-xl font-bold text-neutral-900 flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-blue flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                    </svg>
                </div>
                <span class="tracking-tight">Phân tích tin nhắn cược</span>
            </h2>
        </div>
        
        <div class="card-body">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Input Form -->
                <div class="space-y-5">
                    <form id="betting-form" action="{{ route('user.betting-tickets.store') }}" method="POST">
                        @csrf
                        <!-- Hidden fields for global date and region -->
                        <input type="hidden" name="betting_date" id="betting_date" value="{{ $globalDate }}">
                        <input type="hidden" name="region" id="region" value="{{ $globalRegion }}">
                        <input type="hidden" name="station" id="station" value="">

                        <div class="space-y-4">
                            <div class="form-group">
                                <label for="customer_id" class="form-label flex items-center gap-2">
                                    <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Khách hàng
                                </label>
                                <select name="customer_id" id="customer_id" class="w-full" required>
                                    <option value="">Chọn khách hàng</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->phone }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="original_message" class="form-label flex items-center gap-2">
                                    <svg class="w-4 h-4 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                                    </svg>
                                    Tin nhắn cược
                                </label>
                            
                                <!-- Syntax Guide - Compact -->
                                <details class="group">
                                    <summary class="ios-badge ios-badge-blue cursor-pointer flex items-center gap-2 w-fit mb-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Hướng dẫn cú pháp
                                        <svg class="w-3 h-3 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </summary>
                                    <div class="mt-3 space-y-2 text-xs">
                                        <div class="p-3 bg-neutral-50 rounded-lg border border-neutral-200">
                                            <p class="font-semibold text-neutral-700 mb-2">✅ Đúng:</p>
                                            <code class="block bg-white px-2 py-1.5 rounded text-green-600 font-mono">vt bt 22,29 đax 1.4n</code>
                                        </div>
                                        <div class="p-3 bg-neutral-50 rounded-lg border border-neutral-200">
                                            <p class="font-semibold text-neutral-700 mb-2">❌ Sai:</p>
                                            <code class="block bg-white px-2 py-1.5 rounded text-red-600 font-mono">22,29 đax 1.4n vt và bt</code>
                                        </div>
                                    </div>
                                </details>

                                <textarea name="original_message" id="original_message" rows="5" placeholder="Ví dụ: vt bt 22,29 đax 1.4n&#10;vt bl 79,29 đáx 0.7n&#10;hcm 12 34 56 lo 100000" class="w-full" required></textarea>
                            </div>

                            <div class="flex gap-3">
                                <button type="button" id="parse-btn" class="btn-primary flex-1">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Xử lý
                                </button>
                                <button type="button" id="clear-btn" class="btn-secondary">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                    </div>
                </form>
            </div>
            
                <!-- Preview Panel -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-neutral-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-neutral-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-neutral-700">Preview</h3>
                    </div>
                    
                    <div id="preview-panel" class="hidden">
                        <div class="bg-white">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-medium text-gray-900" id="preview-customer">-</h4>
                                    <p class="text-sm text-gray-500" id="preview-date-region">-</p>
                                </div>
                            </div>
                            
                            <!-- Highlighted Message Preview -->
                            <div class="border-t pt-3 mt-3 hidden" id="highlighted-message-preview">
                                <div class="mb-2">
                                    <span class="text-xs font-medium text-gray-600">
                                        <i class="fas fa-code mr-1"></i>Tin nhắn đã phân tích:
                                    </span>
                                </div>
                                <div class="bg-gray-50 border border-gray-200 rounded-md p-3 overflow-x-auto">
                                    <div id="highlighted-message-content" class="text-sm font-mono whitespace-pre-wrap break-words [&_span]:inline-block [&_span.highlight]:bg-yellow-200 [&_span.highlight]:px-1 [&_span.highlight]:rounded [&_span.highlight-text]:font-semibold [&_span.highlight-text]:text-blue-700"></div>
                                </div>
                            </div>
                            
                            <!-- Single Bet Preview (Legacy) -->
                            <div class="border-t pt-3" id="single-bet-preview">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <span class="text-gray-500">Loại cược:</span>
                                        <p class="font-medium" id="preview-betting-type">-</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Đài:</span>
                                        <p class="font-medium" id="preview-station">-</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Số cược:</span>
                                        <p class="font-medium" id="preview-numbers">-</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Tiền cược:</span>
                                        <p class="font-medium text-red-600" id="preview-amount">-</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Multiple Bets Preview -->
                            <div class="border-t pt-3 hidden" id="multiple-bets-preview">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-medium text-gray-900">Chi tiết các phiếu cược: <span id="bets-count" class="text-indigo-600">0</span></h4>
                                    <!-- Group Toggle (Mobile) -->
                                    <button type="button" id="toggle-group-btn" class="md:hidden text-xs text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                        </svg>
                                        <span id="toggle-group-text">Danh sách</span>
                                    </button>
                                </div>
                                
                                <!-- Mobile: Card Layout -->
                                <div class="md:hidden space-y-2" id="bets-cards-mobile">
                                    <!-- Cards will be populated by JavaScript -->
                                </div>
                                
                                <!-- Show More Button (Mobile) -->
                                <button type="button" id="show-more-btn" class="hidden md:hidden w-full mt-2 py-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors">
                                    Xem thêm (<span id="remaining-count">0</span>)
                                </button>
                                
                                <!-- Desktop: Table Layout -->
                                <div class="hidden md:block overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đài</th>
                                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số</th>
                                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                                                <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cược</th>
                                                <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Xác</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200" id="bets-table-body">
                                            <!-- Rows will be populated by JavaScript -->
                                        </tbody>
                                        <tfoot class="bg-gray-50">
                                            <tr class="font-bold">
                                                <td colspan="4" class="px-2 py-2 text-sm text-gray-900 text-right">Tổng:</td>
                                                <td class="px-2 py-2 text-sm text-red-600 text-right" id="total-amount">0</td>
                                                <td class="px-2 py-2 text-sm text-orange-600 text-right" id="total-cost-xac">0</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                <!-- Total Summary (Mobile) -->
                                <div class="md:hidden mt-2 p-2.5 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg border border-indigo-200">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-medium text-gray-700">Tổng cộng:</span>
                                        <div class="flex items-center gap-3">
                                            <div class="text-right">
                                                <div class="text-sm font-bold text-red-600" id="total-amount-mobile">0</div>
                                                <div class="text-xs text-gray-600">Cược</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-bold text-orange-600" id="total-cost-xac-mobile">0</div>
                                                <div class="text-xs text-gray-600">Xác</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit button hidden - tickets are created automatically when parsing succeeds -->
                        </div>
                    </div>
                    
                    <div id="empty-preview" class="p-8 text-center rounded-xl bg-neutral-50 border border-neutral-200">
                        <div class="w-16 h-16 mx-auto rounded-2xl bg-neutral-100 flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <p class="text-sm text-neutral-600 font-medium">Nhập tin nhắn và nhấn "Xử lý"</p>
                        <p class="text-xs text-neutral-500 mt-1">Kết quả sẽ hiển thị ở đây</p>
                    </div>
                    
                    <div id="error-preview" class="hidden">
                        <div class="text-center py-4">
                            <i class="fas fa-exclamation-triangle text-4xl text-red-300 mb-3"></i>
                            <p class="text-red-500 font-medium" id="error-message">Có lỗi xảy ra</p>
                        </div>
                        
                        <!-- Highlighted Message in Error View -->
                        <div class="border-t pt-3 mt-3 hidden" id="error-highlighted-message-preview">
                            <div class="mb-2">
                                <span class="text-xs font-medium text-gray-600">
                                    <i class="fas fa-code mr-1"></i>Tin nhắn đã phân tích:
                                </span>
                            </div>
                            <div class="bg-gray-50 border border-gray-200 rounded-md p-3 overflow-x-auto">
                                <div id="error-highlighted-message-content" class="text-sm font-mono whitespace-pre-wrap break-words [&_span]:inline-block [&_span.highlight]:bg-yellow-200 [&_span.highlight]:px-1 [&_span.highlight]:rounded [&_span.highlight-text]:font-semibold [&_span.highlight-text]:text-blue-700"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@if($subscriptionStatus === 'active')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const parseBtn = document.getElementById('parse-btn');
    const clearBtn = document.getElementById('clear-btn');
    const submitBtn = document.getElementById('submit-btn'); // No longer used - tickets created automatically
    const originalMessage = document.getElementById('original_message');
    const customerId = document.getElementById('customer_id');
    const bettingDate = document.getElementById('betting_date'); // Hidden input
    const region = document.getElementById('region'); // Hidden input
    const station = document.getElementById('station'); // Hidden input
    
    // Preview elements
    const previewPanel = document.getElementById('preview-panel');
    const emptyPreview = document.getElementById('empty-preview');
    const errorPreview = document.getElementById('error-preview');
    const errorMessage = document.getElementById('error-message');
    const copyJsonBtn = document.getElementById('copy-json-btn');
    
    let currentParseData = null;

                // Parse and create ticket button click handler
    if (parseBtn) {
        parseBtn.addEventListener('click', function() {
            const message = originalMessage.value.trim();
            const customer = customerId.value;

            if (!message) {
                showError('Vui lòng nhập tin nhắn cược');
                return;
            }

            if (!customer) {
                showError('Vui lòng chọn khách hàng');
                return;
            }

            // Disable button and show loading
            parseBtn.disabled = true;
            const originalBtnText = parseBtn.innerHTML;
            parseBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...';
            
            showLoading();

            // Parse message via AJAX
            fetch('{{ route("user.betting-tickets.parse-message") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    message: message,
                    customer_id: customer,
                    region: region.value,
                    date: bettingDate.value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.is_valid) {
                    // Auto-fill station if detected from first bet
                    if (data.multiple_bets && data.multiple_bets.length > 0) {
                        const firstBet = data.multiple_bets[0];
                        if (firstBet.station && !station.value) {
                            station.value = firstBet.station;
                        }
                    }
                    
                    // Store parsed data and submit form immediately
                    currentParseData = data;
                    
                    // Create form data from parsed result
                    const formData = new FormData(document.getElementById('betting-form'));
                    
                    // Submit form to create ticket
                    fetch('{{ route("user.betting-tickets.store") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    })
                    .then(response => {
                        if (response.redirected) {
                            return fetch(response.url)
                                .then(res => res.text())
                                .then(html => {
                                    const parser = new DOMParser();
                                    const doc = parser.parseFromString(html, 'text/html');
                                    const successMsg = doc.querySelector('.alert-success, [class*="success"]');
                                    const message = successMsg ? successMsg.textContent : 'Phiếu cược đã được tạo thành công!';
                                    return { success: true, message: message };
                                });
                        }
                        return response.json();
                    })
                    .then(result => {
                        if (result.success) {
                            // Show success and clear form
                            showSuccessAlert(result.message || 'Phiếu cược đã được tạo thành công!');
                            originalMessage.value = '';
                            station.value = '';
                            hidePreview();
                        } else {
                            showError('Có lỗi xảy ra khi tạo phiếu cược: ' + (result.message || 'Lỗi không xác định'), null);
                        }
                    })
                    .catch(error => {
                        console.error('Error creating ticket:', error);
                        showError('Có lỗi xảy ra khi tạo phiếu cược', null);
                    })
                    .finally(() => {
                        parseBtn.disabled = false;
                        parseBtn.innerHTML = originalBtnText;
                    });
                } else {
                    // Show error with highlighted message and stop
                    showError('Lỗi phân tích: ' + (data.errors ? data.errors.join(', ') : 'Không thể phân tích tin nhắn'), data);
                    parseBtn.disabled = false;
                    parseBtn.innerHTML = originalBtnText;
                }
            })
            .catch(error => {
                showError('Có lỗi xảy ra khi phân tích tin nhắn', null);
                console.error('Error:', error);
                parseBtn.disabled = false;
                parseBtn.innerHTML = originalBtnText;
            });
        });
    }

    // Clear button click handler
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            // Clear form
            originalMessage.value = '';
            station.value = ''; // Clear station (it's auto-detected)
            
            // Reset preview
            currentParseData = null;
            hidePreview();
            if (copyJsonBtn) copyJsonBtn.disabled = true;
        });
    }

    // Customer change handler - fetch rates
    if (customerId) {
        customerId.addEventListener('change', function() {
            if (currentParseData) {
                updatePreviewRates();
            }
        });
    }

    function showLoading() {
        emptyPreview.classList.add('hidden');
        errorPreview.classList.add('hidden');
        previewPanel.classList.add('hidden');
        
        // Show loading in empty preview
        emptyPreview.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-4xl text-green-500 mb-3"></i>
                <p class="text-green-600">Đang phân tích và tạo phiếu cược...</p>
            </div>
        `;
        emptyPreview.classList.remove('hidden');
    }

    function showPreview(data) {
        emptyPreview.classList.add('hidden');
        errorPreview.classList.add('hidden');
        
        // Get customer info
        const customerSelect = document.getElementById('customer_id');
        const customerText = customerSelect.options[customerSelect.selectedIndex].text;
        const dateValue = bettingDate.value;
        const regionValue = region.value;
        const regionLabels = {'bac': 'Miền Bắc', 'trung': 'Miền Trung', 'nam': 'Miền Nam'};
        const regionLabel = regionLabels[regionValue] || regionValue;
        
        // Format date for display
        const dateFormatted = new Date(dateValue).toLocaleDateString('vi-VN');
        
        // Update basic info
        document.getElementById('preview-customer').textContent = customerText;
        document.getElementById('preview-date-region').textContent = `${dateFormatted} - ${regionLabel}`;
        
        // Show highlighted message if available
        const highlightedMsgPreview = document.getElementById('highlighted-message-preview');
        const highlightedMsgContent = document.getElementById('highlighted-message-content');
        if (data.highlighted_message) {
            highlightedMsgContent.innerHTML = data.highlighted_message;
            highlightedMsgPreview.classList.remove('hidden');
        } else {
            highlightedMsgPreview.classList.add('hidden');
        }
        
        // Check if we have multiple bets from new parser
        if (data.multiple_bets && data.multiple_bets.length > 0) {
            // Show multiple bets table
            showMultipleBetsTable(data.multiple_bets, regionValue);
            document.getElementById('single-bet-preview').classList.add('hidden');
            document.getElementById('multiple-bets-preview').classList.remove('hidden');
        } else {
            // Show single bet preview (legacy for old format)
            document.getElementById('preview-betting-type').textContent = data.betting_type ? data.betting_type.name : 'Không xác định';
            document.getElementById('preview-station').textContent = data.stations ? data.stations.join(', ') : (data.station ? data.station.name : '-');
            document.getElementById('preview-numbers').textContent = data.numbers && data.numbers.length > 0 ? data.numbers.join(', ') : 'Theo mẫu';
            document.getElementById('preview-amount').textContent = data.amount ? data.amount.toLocaleString() + ' VNĐ' : '0 VNĐ';
            document.getElementById('single-bet-preview').classList.remove('hidden');
            document.getElementById('multiple-bets-preview').classList.add('hidden');
        }
        
        // Update rates and win amount
        updatePreviewRates();
        
        previewPanel.classList.remove('hidden');
        if (copyJsonBtn) copyJsonBtn.disabled = false;
    }
    
    // State for pagination
    let allBets = [];
    let currentRegion = '';
    let isGrouped = true;
    let visibleCount = 20; // Show first 20 cards
    
    function showMultipleBetsTable(bets, region) {
        allBets = bets;
        currentRegion = region;
        visibleCount = 20; // Reset
        
        const tableBody = document.getElementById('bets-table-body');
        const cardsContainer = document.getElementById('bets-cards-mobile');
        const betsCountEl = document.getElementById('bets-count');
        const showMoreBtn = document.getElementById('show-more-btn');
        const remainingCountEl = document.getElementById('remaining-count');
        const totalAmountEl = document.getElementById('total-amount');
        const totalCostXacEl = document.getElementById('total-cost-xac');
        const totalAmountMobileEl = document.getElementById('total-amount-mobile');
        const totalCostXacMobileEl = document.getElementById('total-cost-xac-mobile');
        
        // Update count
        betsCountEl.textContent = bets.length;
        
        // Clear existing content
        tableBody.innerHTML = '';
        cardsContainer.innerHTML = '';
        
        let totalAmount = 0;
        let totalCostXac = 0;
        
        // Format numbers - hiển thị đúng số thập phân, không làm tròn
        const formatNumber = (num) => {
            if (num >= 1000) {
                const value = num / 1000;
                // Nếu là số nguyên, hiển thị không có dấu phẩy
                if (value % 1 === 0) {
                    return value + 'k';
                }
                // Nếu có phần thập phân, hiển thị 1 chữ số và loại bỏ trailing zeros
                return parseFloat(value.toFixed(1)) + 'k';
            }
            return num.toLocaleString();
        };
        
        const formatTotal = (num) => {
            if (num >= 1000000) {
                const value = num / 1000000;
                if (value % 1 === 0) {
                    return value + 'M';
                }
                return parseFloat(value.toFixed(1)) + 'M';
            } else if (num >= 1000) {
                const value = num / 1000;
                if (value % 1 === 0) {
                    return value + 'k';
                }
                return parseFloat(value.toFixed(1)) + 'k';
            }
            return num.toLocaleString();
        };
        
        // Render mobile cards (with pagination or grouped)
        if (isGrouped) {
            renderGroupedBets(bets);
        } else {
            renderListBets(bets.slice(0, visibleCount));
        }
        
        bets.forEach((bet, index) => {
            const amount = bet.amount || 0;
            const costXac = bet.cost_xac || 0;
            
            totalAmount += amount;
            totalCostXac += costXac;
            
            // Create desktop table row (all rows)
            const stationName = bet.station || '-';
            let numbersDisplay = '-';
            if (Array.isArray(bet.numbers) && bet.numbers.length > 0) {
                const pretty = bet.numbers.map(n => Array.isArray(n) ? n.join('-') : n);
                numbersDisplay = pretty.length > 5 ? pretty.slice(0,5).join(', ') + `... (+${pretty.length - 5})` : pretty.join(', ');
            }
            const bettingTypeName = bet.type || 'Không xác định';
            
            const row = document.createElement('tr');
            row.className = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
            row.innerHTML = `
                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">${index + 1}</td>
                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">${stationName}</td>
                <td class="px-2 py-2 text-xs text-gray-900">
                    <div class="max-w-xs truncate" title="${bet.numbers ? bet.numbers.join(', ') : ''}">
                        ${numbersDisplay}
                    </div>
                </td>
                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">${bettingTypeName}</td>
                <td class="px-2 py-2 whitespace-nowrap text-xs text-red-600 text-right font-medium">${formatNumber(amount)}</td>
                <td class="px-2 py-2 whitespace-nowrap text-xs text-orange-600 text-right">${formatNumber(costXac)}</td>
            `;
            tableBody.appendChild(row);
        });
        
        // Show "Show More" button if needed
        if (bets.length > visibleCount && !isGrouped) {
            showMoreBtn.classList.remove('hidden');
            remainingCountEl.textContent = bets.length - visibleCount;
        } else {
            showMoreBtn.classList.add('hidden');
        }
        
        // Update totals (desktop)
        totalAmountEl.textContent = formatTotal(totalAmount);
        totalCostXacEl.textContent = formatTotal(totalCostXac);
        
        // Update totals (mobile)
        totalAmountMobileEl.textContent = formatTotal(totalAmount);
        totalCostXacMobileEl.textContent = formatTotal(totalCostXac);
    }
    
    // Render list of bets (normal view)
    function renderListBets(bets) {
        const cardsContainer = document.getElementById('bets-cards-mobile');
        
        bets.forEach((bet, index) => {
            const stationName = bet.station || '-';
            let numbersDisplay = '-';
            if (Array.isArray(bet.numbers) && bet.numbers.length > 0) {
                const pretty = bet.numbers.map(n => Array.isArray(n) ? n.join('-') : n);
                numbersDisplay = pretty.length > 5 ? pretty.slice(0,5).join(', ') + `... (+${pretty.length - 5})` : pretty.join(', ');
            }
            
            const bettingTypeName = bet.type || 'Không xác định';
            const amount = bet.amount || 0;
            const costXac = bet.cost_xac || 0;
            
            const formatNumber = (num) => {
                if (num >= 1000) {
                    const value = num / 1000;
                    if (value % 1 === 0) {
                        return value + 'k';
                    }
                    return parseFloat(value.toFixed(1)) + 'k';
                }
                return num.toLocaleString();
            };
            
            const card = document.createElement('div');
            card.className = 'bg-white border border-gray-200 rounded-lg p-2 hover:shadow transition-shadow';
            card.innerHTML = `
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-indigo-100 text-indigo-800 text-xs font-bold flex-shrink-0">
                            ${allBets.indexOf(bet) + 1}
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold text-gray-900 truncate">${bettingTypeName}</div>
                            <div class="text-xs text-red-600 truncate">${stationName}</div>
                            <div class="text-xs text-gray-500 truncate">${numbersDisplay}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <div class="text-right">
                            <div class="text-xs font-bold text-red-600">${formatNumber(amount)}</div>
                            <div class="text-xs text-gray-500">cược</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-bold text-orange-600">${formatNumber(costXac)}</div>
                            <div class="text-xs text-gray-500">xác</div>
                        </div>
                    </div>
                </div>
            `;
            cardsContainer.appendChild(card);
        });
    }
    
    // Render grouped bets (grouped by type)
    function renderGroupedBets(bets) {
        const cardsContainer = document.getElementById('bets-cards-mobile');
        
        // Group by betting type
        const groups = {};
        bets.forEach(bet => {
            const type = bet.type || 'Khác';
            if (!groups[type]) {
                groups[type] = {
                    bets: [],
                    totalAmount: 0,
                    totalCostXac: 0,
                    count: 0
                };
            }
            groups[type].bets.push(bet);
            groups[type].totalAmount += bet.amount || 0;
            groups[type].totalCostXac += bet.cost_xac || 0;
            groups[type].count++;
        });
        
        const formatNumber = (num) => {
            if (num >= 1000) {
                const value = num / 1000;
                if (value % 1 === 0) {
                    return value + 'k';
                }
                return parseFloat(value.toFixed(1)) + 'k';
            }
            return num.toLocaleString();
        };
        
        // Render each group
        Object.keys(groups).forEach(type => {
            const group = groups[type];
            const groupDiv = document.createElement('div');
            groupDiv.className = 'border border-gray-200 rounded-lg overflow-hidden';
            
            groupDiv.innerHTML = `
                <div class="bg-gray-50 p-2 flex items-center justify-between cursor-pointer hover:bg-gray-100" onclick="toggleGroup(this)">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-600 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <span class="text-sm font-semibold text-gray-900">${type}</span>
                        <span class="text-xs text-gray-600">(${group.count} phiếu)</span>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        <div class="text-right">
                            <div class="font-bold text-red-600">${formatNumber(group.totalAmount)}</div>
                            <div class="text-gray-500">cược</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-orange-600">${formatNumber(group.totalCostXac)}</div>
                            <div class="text-gray-500">xác</div>
                        </div>
                    </div>
                </div>
                <div class="hidden space-y-1 p-2 bg-gray-50" data-group-content>
                    ${group.bets.map((bet, idx) => {
                        const stationName = bet.station || '-';
                        let numbersDisplay = '-';
                        if (Array.isArray(bet.numbers) && bet.numbers.length > 0) {
                            const pretty = bet.numbers.map(n => Array.isArray(n) ? n.join('-') : n);
                            numbersDisplay = pretty.length > 3 ? pretty.slice(0,3).join(', ') + '...' : pretty.join(', ');
                        }
                        return `
                        <div class="bg-white border border-gray-100 rounded p-1.5 text-xs flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="text-blue-600 mb-0.5">${stationName}</div>
                                <span class="font-mono text-gray-900">${numbersDisplay}</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs">
                                <span class="font-bold text-red-600">${formatNumber(bet.amount || 0)}</span>
                                <span class="text-gray-400">•</span>
                                <span class="font-bold text-orange-600">${formatNumber(bet.cost_xac || 0)}</span>
                            </div>
                        </div>
                        `;
                    }).join('')}
                </div>
            `;
            cardsContainer.appendChild(groupDiv);
        });
    }
    
    // Toggle group expand/collapse
    window.toggleGroup = function(element) {
        const content = element.nextElementSibling;
        const icon = element.querySelector('svg');
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.style.transform = 'rotate(90deg)';
        } else {
            content.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        }
    };
    
    // Show More button handler
    document.getElementById('show-more-btn')?.addEventListener('click', function() {
        visibleCount += 20;
        showMultipleBetsTable(allBets, currentRegion);
    });
    
    // Toggle Group button handler
    document.getElementById('toggle-group-btn')?.addEventListener('click', function() {
        isGrouped = !isGrouped;
        const toggleText = document.getElementById('toggle-group-text');
        toggleText.textContent = isGrouped ? 'Danh sách' : 'Nhóm lại';
        showMultipleBetsTable(allBets, currentRegion);
    });

    function updatePreviewRates() {
        // This function is now empty as we removed the preview rate display
        // Keeping it to avoid breaking references
    }

    function showError(message, data = null) {
        emptyPreview.classList.add('hidden');
        previewPanel.classList.add('hidden');
        
        errorMessage.textContent = message;
        
        // Show highlighted_message if available, even when there's an error
        const errorHighlightedMsgPreview = document.getElementById('error-highlighted-message-preview');
        const errorHighlightedMsgContent = document.getElementById('error-highlighted-message-content');
        if (data && data.highlighted_message) {
            errorHighlightedMsgContent.innerHTML = data.highlighted_message;
            errorHighlightedMsgPreview.classList.remove('hidden');
        } else {
            errorHighlightedMsgPreview.classList.add('hidden');
        }
        
        errorPreview.classList.remove('hidden');
        if (copyJsonBtn) copyJsonBtn.disabled = true;
    }

    function hidePreview() {
        previewPanel.classList.add('hidden');
        errorPreview.classList.add('hidden');
        
        // Reset empty preview
        emptyPreview.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-file-alt text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">Nhập tin nhắn và nhấn "Tạo Phiếu Cược" để tạo phiếu</p>
            </div>
        `;
        emptyPreview.classList.remove('hidden');
        if (copyJsonBtn) copyJsonBtn.disabled = true;
    }

    // Copy JSON logic
    if (copyJsonBtn) {
        copyJsonBtn.addEventListener('click', async function() {
            if (!currentParseData) return;
            const jsonText = JSON.stringify(currentParseData, null, 2);
            try {
                await navigator.clipboard.writeText(jsonText);
                copyJsonBtn.innerHTML = '<i class="fas fa-check mr-1"></i>Đã copy';
                setTimeout(() => {
                    copyJsonBtn.innerHTML = '<i class="fas fa-copy mr-1"></i>Copy JSON';
                }, 1500);
            } catch (e) {
                // Fallback if Clipboard API not available
                const ta = document.createElement('textarea');
                ta.value = jsonText;
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); } catch (_) {}
                document.body.removeChild(ta);
                copyJsonBtn.innerHTML = '<i class="fas fa-check mr-1"></i>Đã copy';
                setTimeout(() => {
                    copyJsonBtn.innerHTML = '<i class="fas fa-copy mr-1"></i>Copy JSON';
                }, 1500);
            }
        });
    }

    // Form submit handler removed - tickets are now created automatically when parsing succeeds
    
    // Show success alert
    function showSuccessAlert(message) {
        // Create or get alert container
        let alertContainer = document.getElementById('success-alert-container');
        if (!alertContainer) {
            alertContainer = document.createElement('div');
            alertContainer.id = 'success-alert-container';
            alertContainer.className = 'fixed top-4 right-4 z-50';
            document.body.appendChild(alertContainer);
        }
        
        // Create alert element
        const alert = document.createElement('div');
        alert.className = 'bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3';
        alert.innerHTML = `
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span class="font-medium">${message}</span>
        `;
        
        alertContainer.appendChild(alert);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 3000);
    }
    
    // Show error alert
    function showErrorAlert(message) {
        // Create or get alert container
        let alertContainer = document.getElementById('error-alert-container');
        if (!alertContainer) {
            alertContainer = document.createElement('div');
            alertContainer.id = 'error-alert-container';
            alertContainer.className = 'fixed top-4 right-4 z-50';
            document.body.appendChild(alertContainer);
        }
        
        // Create alert element
        const alert = document.createElement('div');
        alert.className = 'bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3';
        alert.innerHTML = `
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span class="font-medium">${message}</span>
        `;
        
        alertContainer.appendChild(alert);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 3000);
    }

});
</script>
@endif
@endsection
