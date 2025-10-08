@extends('layouts.app')

@section('title', 'Chỉnh sửa phiếu cược - Keki SaaS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Chỉnh sửa phiếu cược
                </h1>
                <p class="text-gray-600 mt-1">
                    Cập nhật thông tin và kết quả phiếu cược
                </p>
            </div>
            <a href="{{ route('user.betting-tickets.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Quay lại
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('user.betting-tickets.update', $bettingTicket) }}">
            @csrf
            @method('PUT')
            
            <!-- Basic Information -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Thông tin cơ bản</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Khách hàng <span class="text-red-500">*</span>
                        </label>
                        <select id="customer_id" 
                                name="customer_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('customer_id') border-red-500 @enderror"
                                required>
                            <option value="">Chọn khách hàng</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id', $bettingTicket->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->phone }})
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="betting_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Ngày cược <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               id="betting_date" 
                               name="betting_date" 
                               value="{{ old('betting_date', $bettingTicket->betting_date->format('Y-m-d')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('betting_date') border-red-500 @enderror"
                               required>
                        @error('betting_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="region" class="block text-sm font-medium text-gray-700 mb-2">
                            Miền <span class="text-red-500">*</span>
                        </label>
                        <select id="region" 
                                name="region" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('region') border-red-500 @enderror"
                                required>
                            <option value="">Chọn miền</option>
                            <option value="Bắc" {{ old('region', $bettingTicket->region) == 'Bắc' ? 'selected' : '' }}>Bắc</option>
                            <option value="Trung" {{ old('region', $bettingTicket->region) == 'Trung' ? 'selected' : '' }}>Trung</option>
                            <option value="Nam" {{ old('region', $bettingTicket->region) == 'Nam' ? 'selected' : '' }}>Nam</option>
                        </select>
                        @error('region')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="station" class="block text-sm font-medium text-gray-700 mb-2">
                            Đài <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="station" 
                               name="station" 
                               value="{{ old('station', $bettingTicket->station) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('station') border-red-500 @enderror"
                               placeholder="Nhập tên đài"
                               required>
                        @error('station')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Betting Information -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Thông tin cược</h3>
                <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loại cược</label>
                            <p class="text-sm text-gray-900">{{ $bettingTicket->bettingType->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số cược</label>
                            <p class="text-sm text-gray-900">{{ implode(', ', $bettingTicket->betting_data['numbers'] ?? []) }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tiền cược</label>
                            <p class="text-sm text-gray-900">{{ number_format($bettingTicket->bet_amount, 0, ',', '.') }} VNĐ</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tiền trúng</label>
                            <p class="text-sm text-gray-900">{{ number_format($bettingTicket->win_amount, 0, ',', '.') }} VNĐ</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tin nhắn gốc</label>
                        <p class="text-sm text-gray-900">{{ $bettingTicket->original_message }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tin nhắn đã phân tích</label>
                        <p class="text-sm text-gray-900">{{ $bettingTicket->parsed_message }}</p>
                    </div>
                </div>
            </div>

            <!-- Result Information -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Kết quả cược</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="result" class="block text-sm font-medium text-gray-700 mb-2">
                            Kết quả <span class="text-red-500">*</span>
                        </label>
                        <select id="result" 
                                name="result" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('result') border-red-500 @enderror"
                                required>
                            <option value="pending" {{ old('result', $bettingTicket->result) == 'pending' ? 'selected' : '' }}>Chờ kết quả</option>
                            <option value="win" {{ old('result', $bettingTicket->result) == 'win' ? 'selected' : '' }}>Ăn</option>
                            <option value="lose" {{ old('result', $bettingTicket->result) == 'lose' ? 'selected' : '' }}>Thua</option>
                        </select>
                        @error('result')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="payout_amount" class="block text-sm font-medium text-gray-700 mb-2">
                            Tiền trả thực tế
                        </label>
                        <input type="number" 
                               id="payout_amount" 
                               name="payout_amount" 
                               value="{{ old('payout_amount', $bettingTicket->payout_amount) }}"
                               min="0"
                               step="1000"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('payout_amount') border-red-500 @enderror"
                               placeholder="Nhập số tiền trả thực tế">
                        @error('payout_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">
                            Số tiền thực tế đã trả cho khách hàng (có thể khác với tiền trúng tính toán)
                        </p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="{{ route('user.betting-tickets.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Hủy
                </a>
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Cập nhật phiếu cược
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resultSelect = document.getElementById('result');
    const payoutAmount = document.getElementById('payout_amount');
    
    // Auto-fill payout amount when result changes
    resultSelect.addEventListener('change', function() {
        if (this.value === 'win') {
            // You can set default payout amount here if needed
            // payoutAmount.value = {{ $bettingTicket->win_amount }};
        } else if (this.value === 'lose') {
            payoutAmount.value = 0;
        }
    });
});
</script>
@endsection
