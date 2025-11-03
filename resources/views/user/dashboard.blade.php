@extends('layouts.app')

@section('title', 'Dashboard - Keki SaaS')

@section('content')
<div class="space-y-3 md:space-y-6">
    @if($subscriptionStatus === 'active')
    <!-- Betting Message Input Form -->
    <div class="bg-white shadow rounded-lg p-4 md:p-6">
        <h2 class="text-base md:text-lg font-semibold text-gray-900 mb-3 md:mb-4 flex items-center">
            <svg class="w-5 h-5 md:w-6 md:h-6 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
            </svg>
            Phân tích tin nhắn cược
        </h2>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6">
            <!-- Input Form -->
            <div>
                <form id="betting-form" action="{{ route('user.betting-tickets.store') }}" method="POST">
                    @csrf
                    <!-- Hidden fields for global date and region -->
                    <input type="hidden" name="betting_date" id="betting_date" value="{{ $globalDate }}">
                    <input type="hidden" name="region" id="region" value="{{ $globalRegion }}">
                    <input type="hidden" name="station" id="station" value="">
                    
                    <div class="space-y-3 md:space-y-4">
                        <div>
                            <label for="customer_id" class="block text-xs md:text-sm font-medium text-gray-700 mb-1 flex items-center">
                                <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Khách hàng
                            </label>
                            <select name="customer_id" id="customer_id" class="w-full text-sm md:text-base border border-gray-300 rounded-md px-3 py-2.5 md:py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                <option value="">Chọn khách hàng</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->phone }})</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label for="original_message" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-comment mr-1"></i>Tin nhắn cược
                            </label>
                            <textarea name="original_message" id="original_message" rows="4" placeholder="Ví dụ: lo 12 34 56 100000 mb&#10;bao 01 02 03 50000 2d&#10;da 12 34 200000 hcm" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required></textarea>
                        </div>
                        
                        <div class="flex space-x-3">
                            <button type="button" id="parse-btn" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                                <i class="fas fa-search mr-2"></i>Phân tích tin nhắn
                            </button>
                            <button type="button" id="clear-btn" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                                <i class="fas fa-eraser mr-2"></i>Xóa
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Preview Panel -->
            <div>
                <div class="bg-gray-50 rounded-lg h-full">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-eye mr-1"></i>Preview phiếu cược
                    </h3>
                    
                    <div id="preview-panel" class="hidden">
                        <div class="bg-white">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-medium text-gray-900" id="preview-customer">-</h4>
                                    <p class="text-sm text-gray-500" id="preview-date-region">-</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button type="button" id="copy-json-btn" class="px-2 py-1 text-xs bg-gray-200 text-gray-700 rounded hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-indigo-500 disabled:opacity-50" disabled>
                                        <i class="fas fa-copy mr-1"></i>Copy JSON
                                    </button>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full" id="preview-status">Chờ xử lý</span>
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
                                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Miền</th>
                                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đài</th>
                                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số</th>
                                                <th class="px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                                                <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cược</th>
                                                <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Xác</th>
                                                <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thắng</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200" id="bets-table-body">
                                            <!-- Rows will be populated by JavaScript -->
                                        </tbody>
                                        <tfoot class="bg-gray-50">
                                            <tr class="font-bold">
                                                <td colspan="5" class="px-2 py-2 text-sm text-gray-900 text-right">Tổng:</td>
                                                <td class="px-2 py-2 text-sm text-red-600 text-right" id="total-amount">0</td>
                                                <td class="px-2 py-2 text-sm text-orange-600 text-right" id="total-cost-xac">0</td>
                                                <td class="px-2 py-2 text-sm text-green-600 text-right" id="total-potential-win">0</td>
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
                                            <div class="text-right">
                                                <div class="text-sm font-bold text-green-600" id="total-potential-win-mobile">0</div>
                                                <div class="text-xs text-gray-600">Thắng</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border-t pt-3">
                                <span class="text-gray-500 text-sm">Tin nhắn đã phân tích:</span>
                                <p class="text-sm bg-gray-50 p-2 rounded mt-1" id="preview-parsed">-</p>
                            </div>
                            
                            <div class="border-t pt-3">
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <span class="text-gray-500">Tiền thắng dự kiến:</span>
                                        <p class="font-medium text-green-600" id="preview-win-amount">-</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Tiền xác:</span>
                                        <p class="font-medium" id="preview-rate">-</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="pt-3">
                                <button type="submit" form="betting-form" id="submit-btn" class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors disabled:bg-gray-400" disabled>
                                    <i class="fas fa-check mr-2"></i>Tạo phiếu cược
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="empty-preview" class="text-center py-8">
                        <i class="fas fa-file-alt text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">Nhập tin nhắn và nhấn "Phân tích" để xem preview</p>
                    </div>
                    
                    <div id="error-preview" class="hidden text-center py-8">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-300 mb-3"></i>
                        <p class="text-red-500" id="error-message">Có lỗi xảy ra</p>
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
    const submitBtn = document.getElementById('submit-btn');
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

    // Parse button click handler
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

            // Show loading
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
                    currentParseData = data;
                    showPreview(data);
                    if (copyJsonBtn) copyJsonBtn.disabled = false;
                    
                    // Auto-fill station if detected from first bet
                    if (data.multiple_bets && data.multiple_bets.length > 0) {
                        const firstBet = data.multiple_bets[0];
                        if (firstBet.station && !station.value) {
                            station.value = firstBet.station;
                        }
                    }
                } else {
                    showError('Lỗi phân tích: ' + (data.errors ? data.errors.join(', ') : 'Không thể phân tích tin nhắn'));
                    if (copyJsonBtn) copyJsonBtn.disabled = true;
                }
            })
            .catch(error => {
                showError('Có lỗi xảy ra khi phân tích tin nhắn');
                console.error('Error:', error);
                if (copyJsonBtn) copyJsonBtn.disabled = true;
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
            submitBtn.disabled = true;
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
                <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-3"></i>
                <p class="text-blue-600">Đang phân tích tin nhắn...</p>
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
        
        document.getElementById('preview-parsed').textContent = data.parsed_message || 'Tin nhắn phức tạp';
        
        // Update rates and win amount
        updatePreviewRates();
        
        previewPanel.classList.remove('hidden');
        submitBtn.disabled = false;
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
        const totalPotentialWinEl = document.getElementById('total-potential-win');
        const totalAmountMobileEl = document.getElementById('total-amount-mobile');
        const totalCostXacMobileEl = document.getElementById('total-cost-xac-mobile');
        const totalPotentialWinMobileEl = document.getElementById('total-potential-win-mobile');
        
        // Update count
        betsCountEl.textContent = bets.length;
        
        // Clear existing content
        tableBody.innerHTML = '';
        cardsContainer.innerHTML = '';
        
        let totalAmount = 0;
        let totalCostXac = 0;
        let totalPotentialWin = 0;
        
        // Format numbers
        const formatNumber = (num) => {
            if (num >= 1000) {
                return (num / 1000).toFixed(0) + 'k';
            }
            return num.toLocaleString();
        };
        
        const formatTotal = (num) => {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(0) + 'k';
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
            const potentialWin = bet.potential_win || 0;
            
            totalAmount += amount;
            totalCostXac += costXac;
            totalPotentialWin += potentialWin;
            
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
                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">${region}</td>
                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">${stationName}</td>
                <td class="px-2 py-2 text-xs text-gray-900">
                    <div class="max-w-xs truncate" title="${bet.numbers ? bet.numbers.join(', ') : ''}">
                        ${numbersDisplay}
                    </div>
                </td>
                <td class="px-2 py-2 whitespace-nowrap text-xs text-gray-900">${bettingTypeName}</td>
                <td class="px-2 py-2 whitespace-nowrap text-xs text-red-600 text-right font-medium">${formatNumber(amount)}</td>
                <td class="px-2 py-2 whitespace-nowrap text-xs text-orange-600 text-right">${formatNumber(costXac)}</td>
                <td class="px-2 py-2 whitespace-nowrap text-xs text-green-600 text-right">${formatNumber(potentialWin)}</td>
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
        totalPotentialWinEl.textContent = formatTotal(totalPotentialWin);
        
        // Update totals (mobile)
        totalAmountMobileEl.textContent = formatTotal(totalAmount);
        totalCostXacMobileEl.textContent = formatTotal(totalCostXac);
        totalPotentialWinMobileEl.textContent = formatTotal(totalPotentialWin);
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
                if (num >= 1000) return (num / 1000).toFixed(0) + 'k';
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
            if (num >= 1000) return (num / 1000).toFixed(0) + 'k';
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
        if (!currentParseData || !customerId.value) return;
        
        // Handle multiple bets case (new parser format)
        if (currentParseData.multiple_bets && currentParseData.multiple_bets.length > 0) {
            // Calculate total amount from all bets
            const totalAmount = currentParseData.multiple_bets.reduce((sum, bet) => sum + (bet.amount || 0), 0);
            document.getElementById('preview-win-amount').textContent = currentParseData.summary.total_potential_win.toLocaleString() + ' VNĐ';
            document.getElementById('preview-rate').textContent = currentParseData.summary.total_cost_xac.toLocaleString() + ' VNĐ';
            return;
        }
        
        // Handle single bet case (legacy format)
        if (!currentParseData.betting_type || !currentParseData.betting_type.id) {
            document.getElementById('preview-win-amount').textContent = 'Không xác định';
            document.getElementById('preview-rate').textContent = 'Không xác định';
            return;
        }
        
        // Fetch customer rates
        fetch(`{{ url('/user/customers') }}/${customerId.value}/rates`)
            .then(response => response.json())
            .then(rates => {
                const bettingTypeId = currentParseData.betting_type.id;
                const rate = rates.find(r => r.betting_type_id == bettingTypeId);
                
                if (rate) {
                    const winRate = parseFloat(rate.win_rate);
                    const loseRate = parseFloat(rate.lose_rate);
                    const amount = currentParseData.amount;
                    
                    // Calculate estimated win amount (simplified)
                    const estimatedWin = amount * winRate * 70; // Basic multiplier
                    
                    document.getElementById('preview-win-amount').textContent = estimatedWin.toLocaleString() + ' VNĐ';
                    document.getElementById('preview-rate').textContent = `${(winRate * 100).toFixed(1)}% / ${(loseRate * 100).toFixed(1)}%`;
                } else {
                    document.getElementById('preview-win-amount').textContent = 'Chưa có tỷ lệ';
                    document.getElementById('preview-rate').textContent = 'Chưa thiết lập';
                }
            })
            .catch(error => {
                console.error('Error fetching rates:', error);
                document.getElementById('preview-win-amount').textContent = 'Lỗi tính toán';
                document.getElementById('preview-rate').textContent = 'Lỗi';
            });
    }

    function showError(message) {
        emptyPreview.classList.add('hidden');
        previewPanel.classList.add('hidden');
        
        errorMessage.textContent = message;
        errorPreview.classList.remove('hidden');
        submitBtn.disabled = true;
        if (copyJsonBtn) copyJsonBtn.disabled = true;
    }

    function hidePreview() {
        previewPanel.classList.add('hidden');
        errorPreview.classList.add('hidden');
        
        // Reset empty preview
        emptyPreview.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-file-alt text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">Nhập tin nhắn và nhấn "Phân tích" để xem preview</p>
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

    // Submit form via AJAX
    const form = document.getElementById('betting-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang tạo...';
            
            // Get form data
            const formData = new FormData(form);
            
            // Submit via AJAX
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => {
                if (response.redirected) {
                    // Get the redirected URL and parse message
                    return fetch(response.url)
                        .then(res => res.text())
                        .then(html => {
                            // Try to extract success message from session flash
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const successMsg = doc.querySelector('.alert-success, [class*="success"]');
                            const message = successMsg ? successMsg.textContent : 'Phiếu cược đã được tạo thành công!';
                            
                            return { success: true, message: message };
                        });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Show success alert
                    showSuccessAlert(data.message || 'Phiếu cược đã được tạo thành công!');
                    
                    // Clear form
                    originalMessage.value = '';
                    customerId.value = '';
                    hidePreview();
                } else {
                    // Show error
                    showErrorAlert(data.message || 'Có lỗi xảy ra khi tạo phiếu cược');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorAlert('Có lỗi xảy ra khi tạo phiếu cược');
            })
            .finally(() => {
                // Re-enable button after delay
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-check mr-2"></i>Tạo phiếu cược';
                }, 3000);
            });
        });
    }
    
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
