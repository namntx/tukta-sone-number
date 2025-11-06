@extends('layouts.app')

@section('title', 'Thêm phiếu cược - Keki SaaS')

@section('content')
@php
  $globalDate = session('global_date', today());
  $globalRegion = session('global_region', 'bac');
@endphp

<div class="pb-4">
  <!-- Sticky Header -->
  <div class="sticky top-0 z-10 bg-white shadow-sm border-b border-gray-200 mb-3">
    <div class="px-3 py-2.5">
      <div class="flex items-center justify-between">
        <h1 class="text-lg font-bold text-gray-900">Thêm phiếu cược</h1>
        <a href="{{ route('user.betting-tickets.index') }}" 
           class="inline-flex items-center justify-center p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
        </a>
      </div>
      <div class="text-xs text-gray-500 mt-1">
        {{ \App\Support\Region::label($globalRegion) }} · {{ \Carbon\Carbon::parse($globalDate)->format('d/m/Y') }}
      </div>
    </div>
  </div>

  <form method="POST" action="{{ route('user.betting-tickets.store') }}" id="betting-ticket-form" class="px-3 space-y-4">
    @csrf
    
    <!-- Hidden fields for global date and region -->
    <input type="hidden" name="betting_date" id="betting_date" value="{{ $globalDate }}">
    <input type="hidden" name="region" id="region" value="{{ $globalRegion }}">
    <input type="hidden" name="station" id="station" value="">

    <!-- Customer Selection -->
    <div class="bg-white rounded-lg border border-gray-200 p-3">
      <label for="customer_id" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">
        Khách hàng <span class="text-red-500">*</span>
      </label>
      <select id="customer_id" name="customer_id" 
              class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 @error('customer_id') border-red-500 @enderror"
              required>
        <option value="">Chọn khách hàng</option>
        @foreach($customers as $customer)
          <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
            {{ $customer->name }} ({{ $customer->phone }})
          </option>
        @endforeach
      </select>
      @error('customer_id')
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
      @enderror
    </div>

    <!-- Message Input -->
    <div class="bg-white rounded-lg border border-gray-200 p-3">
      <label for="original_message" class="block text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">
        Tin nhắn cược <span class="text-red-500">*</span>
      </label>
      <textarea id="original_message" name="original_message" rows="4"
                class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 @error('original_message') border-red-500 @enderror"
                placeholder="Ví dụ: lo 12 34 56 100000&#10;bao 01 02 03 50000 2d&#10;da 12 34 200000 hcm"
                required>{{ old('original_message') }}</textarea>
      @error('original_message')
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
      @enderror
      <p class="mt-1.5 text-xs text-gray-500">
        Hệ thống sẽ tự động phân tích và phát hiện đài từ tin nhắn
      </p>
    </div>

    <!-- Parse Button -->
    <div>
      <button type="button" id="parse-btn" 
              class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition shadow-sm">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
        </svg>
        Phân tích tin nhắn
      </button>
    </div>

    <!-- Parse Result -->
    <div id="parse-result" class="hidden">
      <div class="bg-white rounded-lg border border-gray-200 p-3">
        <h4 class="text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Kết quả phân tích:</h4>
        <div id="parse-content" class="text-xs text-gray-600"></div>
      </div>
    </div>

    <!-- Actions -->
    <div class="pb-3 space-y-2">
      <button type="submit" 
              class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition shadow-sm">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Xử lý
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
  const parseBtn = document.getElementById('parse-btn');
  const originalMessage = document.getElementById('original_message');
  const customerId = document.getElementById('customer_id');
  const stationInput = document.getElementById('station');
  const parseResult = document.getElementById('parse-result');
  const parseContent = document.getElementById('parse-content');
  const bettingDate = document.getElementById('betting_date');
  const region = document.getElementById('region');
  
  parseBtn.addEventListener('click', function() {
    const message = originalMessage.value.trim();
    const customer = customerId.value;
    
    if (!message) {
      alert('Vui lòng nhập tin nhắn cược');
      return;
    }
    
    if (!customer) {
      alert('Vui lòng chọn khách hàng');
      return;
    }
    
    // Show loading
    parseBtn.disabled = true;
    parseBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Đang phân tích...';
    
    // Make AJAX request
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
        // Auto-fill station if detected
        if (data.multiple_bets && data.multiple_bets.length > 0) {
          const firstBet = data.multiple_bets[0];
          if (firstBet.station && !stationInput.value) {
            stationInput.value = firstBet.station;
          }
        } else if (data.station) {
          stationInput.value = data.station.name || data.station;
        }
        
        // Display parse result
        if (data.multiple_bets && data.multiple_bets.length > 0) {
          let html = `<p class="text-green-600 font-medium mb-2">✓ Phân tích được ${data.multiple_bets.length} phiếu cược</p>`;
          let totalAmount = 0;
          data.multiple_bets.forEach((bet, idx) => {
            totalAmount += bet.amount || 0;
            html += `<div class="text-xs mb-1 p-1.5 bg-gray-50 rounded">
              <strong>${idx + 1}.</strong> ${bet.type || 'N/A'} - ${bet.station || '-'} - ${(bet.amount || 0).toLocaleString()}đ
            </div>`;
          });
          html += `<p class="mt-2 text-xs font-semibold">Tổng: ${totalAmount.toLocaleString()}đ</p>`;
          parseContent.innerHTML = html;
        } else {
          parseContent.innerHTML = `
            <div class="space-y-1 text-xs">
              <p><strong>Loại:</strong> ${data.betting_type?.name || 'N/A'}</p>
              <p><strong>Số:</strong> ${data.numbers?.join(', ') || 'N/A'}</p>
              <p><strong>Tiền:</strong> ${(data.amount || 0).toLocaleString()}đ</p>
              <p class="text-green-600 font-medium">✓ Tin nhắn hợp lệ</p>
            </div>
          `;
        }
        parseResult.classList.remove('hidden');
      } else {
        parseContent.innerHTML = `
          <p class="text-red-600 font-medium mb-1">✗ Tin nhắn không hợp lệ</p>
          <ul class="text-xs text-red-600 list-disc list-inside">
            ${(data.errors || []).map(e => `<li>${e}</li>`).join('')}
          </ul>
        `;
        parseResult.classList.remove('hidden');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      parseContent.innerHTML = '<p class="text-red-600 text-xs">Có lỗi xảy ra khi phân tích</p>';
      parseResult.classList.remove('hidden');
    })
    .finally(() => {
      parseBtn.disabled = false;
      parseBtn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>Phân tích tin nhắn';
    });
  });
});
</script>
@endsection
