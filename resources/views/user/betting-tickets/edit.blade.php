@extends('layouts.app')

@section('title', 'Chỉnh sửa phiếu cược - Keki SaaS')

@section('content')
<div class="pb-4">
  <!-- Sticky Header -->
  <div class="sticky top-0 z-10 bg-white shadow-sm border-b border-gray-200 mb-3">
    <div class="px-3 py-2.5">
      <div class="flex items-center justify-between">
        <div class="flex-1 min-w-0">
          <h1 class="text-lg font-bold text-gray-900">Sửa phiếu #{{ $bettingTicket->id }}</h1>
          <p class="text-xs text-gray-500 mt-0.5">{{ $bettingTicket->customer->name }}</p>
        </div>
        <a href="{{ route('user.betting-tickets.index') }}" 
           class="inline-flex items-center justify-center p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition ml-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
        </a>
      </div>
    </div>
  </div>

  <form method="POST" action="{{ route('user.betting-tickets.update', $bettingTicket) }}" class="px-3 space-y-4">
    @csrf
    @method('PUT')
    
    <!-- Basic Info Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-3">
      <h3 class="text-xs font-semibold text-gray-700 mb-3 uppercase tracking-wide">Thông tin cơ bản</h3>
      
      <div class="space-y-3">
        <div>
          <label for="customer_id" class="block text-xs font-medium text-gray-600 mb-1">Khách hàng <span class="text-red-500">*</span></label>
          <select id="customer_id" name="customer_id" 
                  class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 @error('customer_id') border-red-500 @enderror"
                  required>
            <option value="">Chọn khách hàng</option>
            @foreach($customers as $customer)
              <option value="{{ $customer->id }}" {{ old('customer_id', $bettingTicket->customer_id) == $customer->id ? 'selected' : '' }}>
                {{ $customer->name }} ({{ $customer->phone }})
              </option>
            @endforeach
          </select>
          @error('customer_id')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
          @enderror
        </div>
        
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label for="betting_date" class="block text-xs font-medium text-gray-600 mb-1">Ngày cược <span class="text-red-500">*</span></label>
            <input type="date" id="betting_date" name="betting_date" 
                   value="{{ old('betting_date', $bettingTicket->betting_date->format('Y-m-d')) }}"
                   class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 @error('betting_date') border-red-500 @enderror"
                   required>
            @error('betting_date')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>
          
          <div>
            <label for="region" class="block text-xs font-medium text-gray-600 mb-1">Miền <span class="text-red-500">*</span></label>
            <select id="region" name="region" 
                    class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 @error('region') border-red-500 @enderror"
                    required>
              <option value="">Chọn miền</option>
              <option value="bac" {{ old('region', $bettingTicket->region) == 'bac' ? 'selected' : '' }}>Bắc</option>
              <option value="trung" {{ old('region', $bettingTicket->region) == 'trung' ? 'selected' : '' }}>Trung</option>
              <option value="nam" {{ old('region', $bettingTicket->region) == 'nam' ? 'selected' : '' }}>Nam</option>
            </select>
            @error('region')
              <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
          </div>
        </div>
        
        <div>
          <label for="station" class="block text-xs font-medium text-gray-600 mb-1">Đài <span class="text-red-500">*</span></label>
          <input type="text" id="station" name="station" 
                 value="{{ old('station', $bettingTicket->station) }}"
                 class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 @error('station') border-red-500 @enderror"
                 placeholder="Nhập tên đài"
                 required>
          @error('station')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
          @enderror
        </div>
      </div>
    </div>

    <!-- Betting Info Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-3">
      <h3 class="text-xs font-semibold text-gray-700 mb-3 uppercase tracking-wide">Thông tin cược</h3>
      
      <div class="space-y-2.5 text-sm">
        <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
          <span class="text-gray-500">Loại cược</span>
          <span class="font-medium text-gray-900">{{ $bettingTicket->bettingType->name }}</span>
        </div>
        
        <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
          <span class="text-gray-500">Số cược</span>
          <span class="font-medium text-gray-900">
            @php
              $numbers = $bettingTicket->betting_data['numbers'] ?? [];
              if (is_array($numbers) && !empty($numbers)) {
                echo implode(', ', array_slice($numbers, 0, 5));
                if (count($numbers) > 5) echo '...';
              } else {
                echo '—';
              }
            @endphp
          </span>
        </div>
        
        <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
          <span class="text-gray-500">Tiền cược</span>
          <span class="font-medium text-gray-900">{{ number_format($bettingTicket->bet_amount / 1000, 1) }}k</span>
        </div>
        
        <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
          <span class="text-gray-500">Tiền trúng</span>
          <span class="font-medium text-green-600">{{ number_format($bettingTicket->win_amount / 1000, 1) }}k</span>
        </div>
        
        <div class="pt-2 space-y-1.5">
          <div class="text-xs text-gray-500">Tin nhắn gốc</div>
          <div class="text-xs text-gray-900 bg-gray-50 rounded p-2 break-words">{{ $bettingTicket->original_message }}</div>
        </div>
        
        @if($bettingTicket->parsed_message)
        <div class="pt-2 space-y-1.5">
          <div class="text-xs text-gray-500">Tin nhắn đã phân tích</div>
          <div class="text-xs text-gray-900 bg-gray-50 rounded p-2 break-words">{{ $bettingTicket->parsed_message }}</div>
        </div>
        @endif
      </div>
    </div>

    <!-- Result Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-3">
      <h3 class="text-xs font-semibold text-gray-700 mb-3 uppercase tracking-wide">Kết quả cược</h3>
      
      <div class="space-y-3">
        <div>
          <label for="result" class="block text-xs font-medium text-gray-600 mb-1">Kết quả <span class="text-red-500">*</span></label>
          <select id="result" name="result" 
                  class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 @error('result') border-red-500 @enderror"
                  required>
            <option value="pending" {{ old('result', $bettingTicket->result) == 'pending' ? 'selected' : '' }}>Chờ kết quả</option>
            <option value="win" {{ old('result', $bettingTicket->result) == 'win' ? 'selected' : '' }}>Ăn</option>
            <option value="lose" {{ old('result', $bettingTicket->result) == 'lose' ? 'selected' : '' }}>Thua</option>
          </select>
          @error('result')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
          @enderror
        </div>
        
        <div>
          <label for="payout_amount" class="block text-xs font-medium text-gray-600 mb-1">Tiền trả thực tế</label>
          <input type="number" id="payout_amount" name="payout_amount" 
                 value="{{ old('payout_amount', $bettingTicket->payout_amount) }}"
                 min="0" step="1000"
                 class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 @error('payout_amount') border-red-500 @enderror"
                 placeholder="Nhập số tiền trả">
          @error('payout_amount')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
          @enderror
          <p class="mt-1 text-xs text-gray-500">Số tiền thực tế đã trả cho khách hàng</p>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="pb-3 space-y-2">
      <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition shadow-sm">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Cập nhật phiếu cược
      </button>
      <a href="{{ route('user.betting-tickets.index') }}" 
         class="block w-full text-center px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
        Hủy
      </a>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const resultSelect = document.getElementById('result');
  const payoutAmount = document.getElementById('payout_amount');
  
  resultSelect.addEventListener('change', function() {
    if (this.value === 'lose') {
      payoutAmount.value = 0;
    }
  });
});
</script>
@endsection
