@extends('layouts.app')

@section('title', 'Chi tiết khách hàng - Keki SaaS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ $customer->name }}
                </h1>
                <p class="text-gray-600 mt-1">
                    Chi tiết thông tin khách hàng và lịch sử cược
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('user.customers.edit', $customer) }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Chỉnh sửa
                </a>
                <a href="{{ route('user.customers.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Quay lại
                </a>
            </div>
        </div>
    </div>

    <!-- Customer Information -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Basic Info -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Thông tin cơ bản</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Tên khách hàng</dt>
                        <dd class="text-sm text-gray-900">{{ $customer->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Số điện thoại</dt>
                        <dd class="text-sm text-gray-900">{{ $customer->phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Trạng thái</dt>
                        <dd>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $customer->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $customer->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Ngày tạo</dt>
                        <dd class="text-sm text-gray-900">{{ $customer->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Cập nhật cuối</dt>
                        <dd class="text-sm text-gray-900">{{ $customer->updated_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Statistics -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Thống kê tài chính</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <p class="text-sm text-gray-500">Tổng ăn</p>
                        <p class="text-2xl font-bold text-green-600">{{ number_format($customer->total_win_amount, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-400">VNĐ</p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500">Tổng thua</p>
                        <p class="text-2xl font-bold text-red-600">{{ number_format($customer->total_lose_amount, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-400">VNĐ</p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500">Lãi/Lỗ tổng</p>
                        <p class="text-2xl font-bold {{ $customer->net_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($customer->net_profit, 0, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-400">VNĐ</p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500">Lãi/Lỗ hôm nay</p>
                        <p class="text-2xl font-bold {{ $customer->daily_net_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($customer->daily_net_profit, 0, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-400">VNĐ</p>
                    </div>
                </div>
                
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <p class="text-sm text-gray-500">Lãi/Lỗ tháng này</p>
                        <p class="text-lg font-semibold {{ $customer->monthly_net_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($customer->monthly_net_profit, 0, ',', '.') }} VNĐ
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500">Lãi/Lỗ năm nay</p>
                        <p class="text-lg font-semibold {{ $customer->yearly_net_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($customer->yearly_net_profit, 0, ',', '.') }} VNĐ
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500">Tổng phiếu cược</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $customer->bettingTickets->count() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Betting Rates -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Hệ số cược</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại cược</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hệ số thu</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hệ số trả</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($customer->bettingRates as $rate)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $rate->bettingType->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $rate->bettingType->code }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($rate->win_rate * 100, 1) }}%
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($rate->lose_rate * 100, 1) }}%
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $rate->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $rate->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            Chưa có hệ số cược nào được thiết lập
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Tickets -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Phiếu cược gần đây</h3>
            <a href="{{ route('user.betting-tickets.index', ['customer' => $customer->id]) }}" 
               class="text-sm text-indigo-600 hover:text-indigo-900">
                Xem tất cả
            </a>
        </div>
        
        @if($recentTickets->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ngày</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại cược</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Miền/Đài</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số tiền cược</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kết quả</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tiền trúng</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($recentTickets as $ticket)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $ticket->betting_date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $ticket->bettingType->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $ticket->region }} - {{ $ticket->station }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ number_format($ticket->bet_amount, 0, ',', '.') }} VNĐ
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $ticket->result_badge_class }}">
                                {{ ucfirst($ticket->result) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $ticket->win_amount > 0 ? 'text-green-600' : 'text-gray-500' }}">
                            {{ $ticket->win_amount > 0 ? number_format($ticket->win_amount, 0, ',', '.') . ' VNĐ' : '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Chưa có phiếu cược</h3>
            <p class="mt-1 text-sm text-gray-500">Khách hàng này chưa có phiếu cược nào.</p>
        </div>
        @endif
    </div>
</div>
@endsection
