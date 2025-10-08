@extends('layouts.app')

@section('title', 'Thêm khách hàng - Keki SaaS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Thêm khách hàng mới
                </h1>
                <p class="text-gray-600 mt-1">
                    Thêm thông tin khách hàng và cấu hình hệ số cược
                </p>
            </div>
            <a href="{{ route('user.customers.index') }}" 
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
        <form method="POST" action="{{ route('user.customers.store') }}">
            @csrf
            
            <!-- Basic Information -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Thông tin cơ bản</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Tên khách hàng <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror"
                               placeholder="Nhập tên khách hàng"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Số điện thoại <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="phone" 
                               name="phone" 
                               value="{{ old('phone') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('phone') border-red-500 @enderror"
                               placeholder="Nhập số điện thoại"
                               required>
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Betting Rates -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Cấu hình hệ số cược</h3>
                <p class="text-sm text-gray-600 mb-6">
                    Thiết lập hệ số thu và trả cho từng loại cược. Hệ số từ 0.0 đến 1.0 (0% đến 100%).
                </p>
                
                <div class="space-y-4">
                    @foreach($bettingTypes as $index => $bettingType)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-medium text-gray-900">{{ $bettingType->name }}</h4>
                            <span class="text-xs text-gray-500">{{ $bettingType->code }}</span>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Hệ số thu (Win Rate)
                                </label>
                                <input type="number" 
                                       name="betting_rates[{{ $index }}][win_rate]" 
                                       value="{{ old('betting_rates.'.$index.'.win_rate', '0.8') }}"
                                       min="0" 
                                       max="1" 
                                       step="0.01"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('betting_rates.'.$index.'.win_rate') border-red-500 @enderror"
                                       placeholder="0.80">
                                <input type="hidden" name="betting_rates[{{ $index }}][betting_type_id]" value="{{ $bettingType->id }}">
                                @error('betting_rates.'.$index.'.win_rate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Hệ số trả (Lose Rate)
                                </label>
                                <input type="number" 
                                       name="betting_rates[{{ $index }}][lose_rate]" 
                                       value="{{ old('betting_rates.'.$index.'.lose_rate', '0.2') }}"
                                       min="0" 
                                       max="1" 
                                       step="0.01"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('betting_rates.'.$index.'.lose_rate') border-red-500 @enderror"
                                       placeholder="0.20">
                                @error('betting_rates.'.$index.'.lose_rate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        @if($bettingType->description)
                        <p class="text-xs text-gray-500 mt-2">{{ $bettingType->description }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="{{ route('user.customers.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Hủy
                </a>
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Tạo khách hàng
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-calculate lose rate when win rate changes
document.addEventListener('DOMContentLoaded', function() {
    const winRateInputs = document.querySelectorAll('input[name*="[win_rate]"]');
    const loseRateInputs = document.querySelectorAll('input[name*="[lose_rate]"]');
    
    winRateInputs.forEach((input, index) => {
        input.addEventListener('input', function() {
            const winRate = parseFloat(this.value) || 0;
            const loseRate = Math.max(0, 1 - winRate);
            loseRateInputs[index].value = loseRate.toFixed(2);
        });
    });
    
    loseRateInputs.forEach((input, index) => {
        input.addEventListener('input', function() {
            const loseRate = parseFloat(this.value) || 0;
            const winRate = Math.max(0, 1 - loseRate);
            winRateInputs[index].value = winRate.toFixed(2);
        });
    });
});
</script>
@endsection
