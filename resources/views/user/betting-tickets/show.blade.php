@extends('layouts.app')

@section('title', 'Chi tiết phiếu cược - Keki SaaS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Chi tiết phiếu cược #{{ $bettingTicket->id }}
                </h1>
                <p class="text-gray-600 mt-1">
                    Thông tin chi tiết và lịch sử phiếu cược
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('user.betting-tickets.edit', $bettingTicket) }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Chỉnh sửa
                </a>
                <a href="{{ route('user.betting-tickets.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Quay lại
                </a>
            </div>
        </div>
    </div>

    <!-- Ticket Information -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Basic Info -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Thông tin cơ bản</h3>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">ID Phiếu cược</dt>
                        <dd class="text-sm text-gray-900">#{{ $bettingTicket->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Khách hàng</dt>
                        <dd class="text-sm text-gray-900">{{ $bettingTicket->customer->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Số điện thoại</dt>
                        <dd class="text-sm text-gray-900">{{ $bettingTicket->customer->phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Ngày cược</dt>
                        <dd class="text-sm text-gray-900">{{ $bettingTicket->betting_date->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Miền</dt>
                        <dd class="text-sm text-gray-900">{{ $bettingTicket->region }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Đài</dt>
                        <dd class="text-sm text-gray-900">{{ $bettingTicket->station }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Trạng thái</dt>
                        <dd>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $bettingTicket->status_badge_class }}">
                                {{ ucfirst($bettingTicket->status) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Ngày tạo</dt>
                        <dd class="text-sm text-gray-900">{{ $bettingTicket->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Betting Details -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Chi tiết cược</h3>
                
                <!-- Betting Type and Numbers -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Loại cược</label>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-sm font-medium text-gray-900">{{ $bettingTicket->bettingType->name }}</p>
                            <p class="text-xs text-gray-500">{{ $bettingTicket->bettingType->code }}</p>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Số cược</label>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <div class="flex flex-wrap gap-2">
                                @if(isset($bettingTicket->betting_data['numbers']))
                                    @foreach($bettingTicket->betting_data['numbers'] as $number)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $number }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-sm text-gray-500">Không có dữ liệu</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tin nhắn gốc</label>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-sm text-gray-900">{{ $bettingTicket->original_message }}</p>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tin nhắn đã phân tích</label>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-sm text-gray-900">{{ $bettingTicket->parsed_message }}</p>
                        </div>
                    </div>
                </div>

                <!-- Financial Information -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <p class="text-sm text-gray-500">Tiền cược</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($bettingTicket->bet_amount, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-400">VNĐ</p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500">Tiền trúng</p>
                        <p class="text-2xl font-bold text-green-600">{{ number_format($bettingTicket->win_amount, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-400">VNĐ</p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500">Tiền trả thực tế</p>
                        <p class="text-2xl font-bold {{ $bettingTicket->payout_amount > 0 ? 'text-red-600' : 'text-gray-500' }}">
                            {{ number_format($bettingTicket->payout_amount, 0, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-400">VNĐ</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Result Information -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Kết quả cược</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kết quả</label>
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $bettingTicket->result_badge_class }}">
                        {{ ucfirst($bettingTicket->result) }}
                    </span>
                    @if($bettingTicket->result === 'win')
                        <span class="text-green-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </span>
                    @elseif($bettingTicket->result === 'lose')
                        <span class="text-red-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </span>
                    @else
                        <span class="text-yellow-600">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                            </svg>
                        </span>
                    @endif
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Lãi/Lỗ</label>
                @php
                    $profit = $bettingTicket->result === 'win' ? $bettingTicket->payout_amount - $bettingTicket->bet_amount : 
                              ($bettingTicket->result === 'lose' ? -$bettingTicket->bet_amount : 0);
                @endphp
                <p class="text-lg font-semibold {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $profit >= 0 ? '+' : '' }}{{ number_format($profit, 0, ',', '.') }} VNĐ
                </p>
            </div>
        </div>
        
        @if($bettingTicket->result !== 'pending')
        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Tóm tắt tài chính</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Tiền cược:</span>
                    <span class="font-medium text-gray-900">{{ number_format($bettingTicket->bet_amount, 0, ',', '.') }} VNĐ</span>
                </div>
                <div>
                    <span class="text-gray-500">Tiền trả:</span>
                    <span class="font-medium text-red-600">{{ number_format($bettingTicket->payout_amount, 0, ',', '.') }} VNĐ</span>
                </div>
                <div>
                    <span class="text-gray-500">Lãi/Lỗ:</span>
                    <span class="font-medium {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $profit >= 0 ? '+' : '' }}{{ number_format($profit, 0, ',', '.') }} VNĐ
                    </span>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Actions -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Thao tác</h3>
                <p class="text-sm text-gray-600">Các thao tác có thể thực hiện với phiếu cược này</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('user.betting-tickets.edit', $bettingTicket) }}" 
                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Chỉnh sửa
                </a>
                <form method="POST" action="{{ route('user.betting-tickets.destroy', $bettingTicket) }}" class="inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa phiếu cược này?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Xóa
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
