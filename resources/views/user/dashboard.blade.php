@extends('layouts.app')

@section('title', 'Dashboard - Keki SaaS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Chào mừng, {{ $user->name }}!
                </h1>
                <p class="text-gray-600 mt-1">
                    Đây là dashboard của bạn
                </p>
            </div>
            <div class="mt-4 sm:mt-0">
                @php
                    $statusColors = [
                        'active' => 'bg-green-100 text-green-800',
                        'expired' => 'bg-red-100 text-red-800',
                        'none' => 'bg-gray-100 text-gray-800'
                    ];
                    $statusTexts = [
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'none' => 'No Plan'
                    ];
                @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$subscriptionStatus] }}">
                    {{ $statusTexts[$subscriptionStatus] }}
                </span>
            </div>
        </div>
    </div>

    <!-- Subscription Status Card -->
    @if($activeSubscription)
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">
                Subscription hiện tại
            </h2>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                {{ $activeSubscription->plan->name }}
            </span>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm font-medium text-gray-500">Gói</div>
                <div class="text-lg font-semibold text-gray-900">{{ $activeSubscription->plan->name }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm font-medium text-gray-500">Hết hạn</div>
                <div class="text-lg font-semibold text-gray-900">{{ $activeSubscription->formatted_expiry_date }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm font-medium text-gray-500">Còn lại</div>
                <div class="text-lg font-semibold text-gray-900">{{ $daysRemaining }} ngày</div>
            </div>
        </div>
        
        @if($daysRemaining <= 7 && $daysRemaining > 0)
        <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        Cảnh báo hết hạn
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>Subscription của bạn sẽ hết hạn trong {{ $daysRemaining }} ngày. Vui lòng liên hệ admin để gia hạn.</p>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    @else
    <div class="bg-white shadow rounded-lg p-6">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Chưa có subscription</h3>
            <p class="mt-1 text-sm text-gray-500">Bạn chưa có gói subscription nào. Vui lòng liên hệ admin để đăng ký.</p>
            <div class="mt-6">
                <a href="{{ route('user.subscription') }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Xem các gói
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Subscriptions -->
    @if($recentSubscriptions->count() > 0)
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                Lịch sử subscription
            </h2>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($recentSubscriptions as $subscription)
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center">
                            <h3 class="text-sm font-medium text-gray-900">
                                {{ $subscription->plan->name }}
                            </h3>
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($subscription->status === 'active') bg-green-100 text-green-800
                                @elseif($subscription->status === 'expired') bg-red-100 text-red-800
                                @elseif($subscription->status === 'cancelled') bg-gray-100 text-gray-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                {{ ucfirst($subscription->status) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500">
                            {{ $subscription->formatted_amount_paid }} • {{ $subscription->formatted_expiry_date }}
                        </p>
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $subscription->created_at->format('d/m/Y') }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($subscriptionStatus === 'active')
    <!-- Betting Message Input Form -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-ticket-alt mr-2"></i>Phân tích tin nhắn cược
        </h2>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Input Form -->
            <div>
                <form id="betting-form" action="{{ route('user.betting-tickets.store') }}" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-user mr-1"></i>Khách hàng
                            </label>
                            <select name="customer_id" id="customer_id" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                <option value="">Chọn khách hàng</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->phone }})</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="betting_date" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-calendar mr-1"></i>Ngày cược
                                </label>
                                <input type="date" name="betting_date" id="betting_date" value="{{ $global_date }}" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            </div>
                            <div>
                                <label for="region" class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fas fa-map-marker-alt mr-1"></i>Miền
                                </label>
                                <select name="region" id="region" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                    <option value="">Chọn miền</option>
                                    <option value="bac"  {{ $global_region=='bac'  ? 'selected' : '' }}>Miền Bắc</option>
                                    <option value="trung"{{ $global_region=='trung'? 'selected' : '' }}>Miền Trung</option>
                                    <option value="nam"  {{ $global_region=='nam'  ? 'selected' : '' }}>Miền Nam</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label for="station" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-broadcast-tower mr-1"></i>Đài cược
                            </label>
                            <input type="text" name="station" id="station" placeholder="Tự động phát hiện từ tin nhắn" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <p class="text-xs text-gray-500 mt-1">Để trống để tự động phát hiện từ tin nhắn</p>
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
                <div class="bg-gray-50 rounded-lg p-4 h-full">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-eye mr-1"></i>Preview phiếu cược
                    </h3>
                    
                    <div id="preview-panel" class="hidden">
                        <div class="bg-white rounded-lg border p-4 space-y-3">
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
                            
                            <!-- Multiple Bets Table -->
                            <div class="border-t pt-3 hidden" id="multiple-bets-preview">
                                <h4 class="text-sm font-medium text-gray-900 mb-3">Chi tiết các phiếu cược:</h4>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Miền</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đài</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số cược</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại cược</th>
                                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Số tiền</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200" id="bets-table-body">
                                            <!-- Rows will be populated by JavaScript -->
                                        </tbody>
                                        <tfoot class="bg-gray-50">
                                            <tr>
                                                <td colspan="5" class="px-3 py-2 text-sm font-medium text-gray-900 text-right">Tổng cộng:</td>
                                                <td class="px-3 py-2 text-sm font-bold text-red-600 text-right" id="total-amount">0 VNĐ</td>
                                            </tr>
                                        </tfoot>
                                    </table>
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
                                        <span class="text-gray-500">Tỷ lệ:</span>
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

    <!-- Today's Statistics -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            Thống kê hôm nay
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="text-sm font-medium text-blue-600">Tổng phiếu</div>
                <div class="text-2xl font-bold text-blue-900">{{ $todayStats['total_tickets'] }}</div>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
                <div class="text-sm font-medium text-green-600">Tổng cược</div>
                <div class="text-2xl font-bold text-green-900">{{ number_format($todayStats['total_bet_amount'], 0, ',', '.') }} VNĐ</div>
            </div>
            <div class="bg-yellow-50 rounded-lg p-4">
                <div class="text-sm font-medium text-yellow-600">Tổng ăn</div>
                <div class="text-2xl font-bold text-yellow-900">{{ number_format($todayStats['total_win_amount'], 0, ',', '.') }} VNĐ</div>
            </div>
            <div class="bg-red-50 rounded-lg p-4">
                <div class="text-sm font-medium text-red-600">Tổng thua</div>
                <div class="text-2xl font-bold text-red-900">{{ number_format($todayStats['total_lose_amount'], 0, ',', '.') }} VNĐ</div>
            </div>
        </div>
    </div>

    <!-- Today's Tickets -->
    @if($todayTickets->count() > 0)
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                Phiếu cược hôm nay
            </h2>
        </div>
        <div class="divide-y divide-gray-200">
            @foreach($todayTickets as $ticket)
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
                        </div>
                        <p class="text-sm text-gray-500">
                            {{ $ticket->bettingType->name }} • {{ $ticket->formatted_bet_amount }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ $ticket->parsed_message }}
                        </p>
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $ticket->created_at->format('H:i') }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endif

    <!-- Quick Actions -->
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            Thao tác nhanh
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('user.customers.index') }}" 
               class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">Khách hàng</p>
                    <p class="text-sm text-gray-500">Quản lý khách hàng</p>
                </div>
            </a>
            
            <a href="{{ route('user.betting-tickets.index') }}" 
               class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">Phiếu cược</p>
                    <p class="text-sm text-gray-500">Quản lý phiếu cược</p>
                </div>
            </a>
            
            <a href="{{ route('user.subscription') }}" 
               class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">Subscription</p>
                    <p class="text-sm text-gray-500">Xem và quản lý gói</p>
                </div>
            </a>
            
            <a href="{{ route('user.profile') }}" 
               class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">Profile</p>
                    <p class="text-sm text-gray-500">Cập nhật thông tin</p>
                </div>
            </a>
        </div>
    </div>
</div>

@if($subscriptionStatus === 'active')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const parseBtn = document.getElementById('parse-btn');
    const clearBtn = document.getElementById('clear-btn');
    const submitBtn = document.getElementById('submit-btn');
    const originalMessage = document.getElementById('original_message');
    const customerId = document.getElementById('customer_id');
    const bettingDate = document.getElementById('betting_date');
    const region = document.getElementById('region');
    const station = document.getElementById('station');
    
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
                    customer_id: customer
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
            station.value = '';
            
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
        
        // Update basic info
        document.getElementById('preview-customer').textContent = customerText;
        document.getElementById('preview-date-region').textContent = `${dateValue} - ${regionValue}`;
        
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
    
    function showMultipleBetsTable(bets, region) {
        const tableBody = document.getElementById('bets-table-body');
        const totalAmountEl = document.getElementById('total-amount');
        
        // Clear existing rows
        tableBody.innerHTML = '';
        
        let totalAmount = 0;
        
        bets.forEach((bet, index) => {
            const row = document.createElement('tr');
            row.className = index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
            
            // Get station name (new format)
            const stationName = bet.station || '-';
            
            // Get numbers display (new format)
            // Get numbers display (xiên -> nối bằng dấu '-')
            let numbersDisplay = '-';
            if (Array.isArray(bet.numbers) && bet.numbers.length > 0) {
                const pretty = bet.numbers.map(n => Array.isArray(n) ? n.join('-') : n);
                numbersDisplay = pretty.length > 5 ? pretty.slice(0,5).join(', ') + `... (+${pretty.length - 5})` : pretty.join(', ');
            }
            
            // Get betting type name (new format)
            const bettingTypeName = bet.type || 'Không xác định';
            
            // Calculate amount
            const amount = bet.amount || 0;
            totalAmount += amount;
            
            row.innerHTML = `
                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">${index + 1}</td>
                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">${region}</td>
                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">${stationName}</td>
                <td class="px-3 py-2 text-sm text-gray-900">
                    <div class="max-w-xs truncate" title="${bet.numbers ? bet.numbers.join(', ') : ''}">
                        ${numbersDisplay}
                    </div>
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">${bettingTypeName}</td>
                <td class="px-3 py-2 whitespace-nowrap text-sm text-red-600 text-right font-medium">${amount.toLocaleString()} VNĐ</td>
            `;
            
            tableBody.appendChild(row);
        });
        
        // Update total amount
        totalAmountEl.textContent = totalAmount.toLocaleString() + ' VNĐ';
    }

    function updatePreviewRates() {
        if (!currentParseData || !customerId.value) return;
        
        // Handle multiple bets case (new parser format)
        if (currentParseData.multiple_bets && currentParseData.multiple_bets.length > 0) {
            // Calculate total amount from all bets
            const totalAmount = currentParseData.multiple_bets.reduce((sum, bet) => sum + (bet.amount || 0), 0);
            document.getElementById('preview-win-amount').textContent = 'Tính toán phức tạp';
            document.getElementById('preview-rate').textContent = 'Nhiều tỷ lệ khác nhau';
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

});
</script>
@endif
@endsection
