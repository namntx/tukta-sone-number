@extends('layouts.app')

@section('title', 'Chỉnh sửa phiếu cược - Keki SaaS')

@section('content')
<div class="pb-4">
  <!-- Header -->
  <div class="sticky top-14 z-10 bg-gray-50 border-b border-gray-200 -mx-3 px-3 py-2 mb-3">
    <div class="flex items-center justify-between">
      <div class="flex-1 min-w-0">
        <h1 class="text-base font-semibold text-gray-900">Sửa phiếu #{{ $bettingTicket->id }}</h1>
        <p class="text-xs text-gray-500 mt-0.5">{{ $bettingTicket->customer->name }}</p>
      </div>
      <a href="{{ route('user.betting-tickets.index') }}" class="btn btn-secondary btn-sm btn-icon">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
      </a>
    </div>
  </div>

  <form method="POST" action="{{ route('user.betting-tickets.update', $bettingTicket) }}" class="space-y-3">
    @csrf
    @method('PUT')

    <!-- Basic Info Card -->
    <div class="card">
      <div class="card-header">
        <h3 class="text-sm font-semibold text-gray-900">Thông tin cơ bản</h3>
      </div>
      <div class="card-body space-y-2.5">
        <div>
          <label for="customer_id" class="block text-xs font-medium text-gray-700 mb-1">Khách hàng <span class="text-red-500">*</span></label>
          <select id="customer_id" name="customer_id" required>
            <option value="">Chọn khách hàng</option>
            @foreach($customers as $customer)
              <option value="{{ $customer->id }}" {{ old('customer_id', $bettingTicket->customer_id) == $customer->id ? 'selected' : '' }}>
                {{ $customer->name }} ({{ $customer->phone }})
              </option>
            @endforeach
          </select>
          @error('customer_id')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
          @enderror
        </div>
        
        <div class="grid grid-cols-2 gap-2.5">
          <div>
            <label for="betting_date" class="block text-xs font-medium text-gray-700 mb-1">Ngày cược <span class="text-red-500">*</span></label>
            <input type="date" id="betting_date" name="betting_date"
                   value="{{ old('betting_date', $bettingTicket->betting_date->format('Y-m-d')) }}"
                   required>
            @error('betting_date')
              <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <div>
            <label for="region" class="block text-xs font-medium text-gray-700 mb-1">Miền <span class="text-red-500">*</span></label>
            <select id="region" name="region" required>
              <option value="">Chọn miền</option>
              <option value="bac" {{ old('region', $bettingTicket->region) == 'bac' ? 'selected' : '' }}>Bắc</option>
              <option value="trung" {{ old('region', $bettingTicket->region) == 'trung' ? 'selected' : '' }}>Trung</option>
              <option value="nam" {{ old('region', $bettingTicket->region) == 'nam' ? 'selected' : '' }}>Nam</option>
            </select>
            @error('region')
              <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <div>
          <label for="station" class="block text-xs font-medium text-gray-700 mb-1">Đài <span class="text-red-500">*</span></label>
          <input type="text" id="station" name="station"
                 value="{{ old('station', $bettingTicket->station) }}"
                 placeholder="Nhập tên đài"
                 required>
          @error('station')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
          @enderror
        </div>
      </div>
    </div>

    <!-- Betting Info Card -->
    <div class="card">
      <div class="card-header">
        <h3 class="text-sm font-semibold text-gray-900">Thông tin cược</h3>
      </div>
      <div class="card-body space-y-2.5">
        <div>
          <label for="original_message" class="block text-xs font-medium text-gray-700 mb-1">Tin nhắn gốc <span class="text-red-500">*</span></label>
          <textarea id="original_message" name="original_message" rows="3"
                    placeholder="Nhập tin nhắn cược"
                    required>{{ old('original_message', $bettingTicket->original_message) }}</textarea>
          @error('original_message')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
          @enderror
          <button type="button" id="parse-message-btn"
                  class="mt-2 btn btn-secondary btn-sm">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Parse lại
          </button>
        </div>
        
        <div id="parse-result" class="hidden">
          <div class="text-xs font-semibold text-gray-700 mb-2">Kết quả parse:</div>
          <div id="parse-preview" class="text-xs bg-gray-50 rounded p-2 border border-gray-200"></div>
        </div>
        
        <div>
          <label for="betting_type_id" class="block text-xs font-medium text-gray-700 mb-1">Loại cược <span class="text-red-500">*</span></label>
          <select id="betting_type_id" name="betting_type_id" required>
            <option value="">Chọn loại cược</option>
            @foreach($bettingTypes as $type)
              <option value="{{ $type->id }}" {{ old('betting_type_id', $bettingTicket->betting_type_id) == $type->id ? 'selected' : '' }}>
                {{ $type->name }}
              </option>
            @endforeach
          </select>
          @error('betting_type_id')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label for="bet_amount" class="block text-xs font-medium text-gray-700 mb-1">Tiền cược (VNĐ) <span class="text-red-500">*</span></label>
          <input type="number" id="bet_amount" name="bet_amount"
                 value="{{ old('bet_amount', $bettingTicket->bet_amount) }}"
                 min="0" step="1000"
                 placeholder="Nhập số tiền cược"
                 required>
          @error('bet_amount')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label for="betting_numbers" class="block text-xs font-medium text-gray-700 mb-1">Số cược</label>
          <input type="text" id="betting_numbers" name="betting_numbers"
                 value="{{ old('betting_numbers', is_array($bettingTicket->betting_data['numbers'] ?? []) ? implode(' ', $bettingTicket->betting_data['numbers']) : '') }}"
                 placeholder="Ví dụ: 12 34 56 hoặc 12,34,56">
          @error('betting_numbers')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
          @enderror
          <p class="mt-1 text-xs text-gray-500">Các số cược, cách nhau bởi dấu phẩy hoặc khoảng trắng</p>
        </div>
        
        <div class="flex justify-between items-center py-1.5 border-t border-gray-200 pt-2">
          <span class="text-gray-500 text-xs">Tiền trúng</span>
          <span class="font-medium text-green-600 text-xs">{{ number_format($bettingTicket->win_amount / 1000, 1) }}k</span>
        </div>

        @if($bettingTicket->parsed_message)
        <div class="pt-2 space-y-1">
          <div class="text-xs text-gray-500">Tin nhắn đã phân tích</div>
          <div class="text-xs text-gray-900 bg-gray-50 rounded-lg p-2 break-words">{{ $bettingTicket->parsed_message }}</div>
        </div>
        @endif
      </div>
    </div>

    <!-- Result Card -->
    <div class="card">
      <div class="card-header">
        <h3 class="text-sm font-semibold text-gray-900">Kết quả cược</h3>
      </div>
      <div class="card-body space-y-2.5">
        <div>
          <label for="result" class="block text-xs font-medium text-gray-700 mb-1">Kết quả <span class="text-red-500">*</span></label>
          <select id="result" name="result" required>
            <option value="pending" {{ old('result', $bettingTicket->result) == 'pending' ? 'selected' : '' }}>Chờ kết quả</option>
            <option value="win" {{ old('result', $bettingTicket->result) == 'win' ? 'selected' : '' }}>Ăn</option>
            <option value="lose" {{ old('result', $bettingTicket->result) == 'lose' ? 'selected' : '' }}>Thua</option>
          </select>
          @error('result')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label for="payout_amount" class="block text-xs font-medium text-gray-700 mb-1">Tiền trả thực tế</label>
          <input type="number" id="payout_amount" name="payout_amount"
                 value="{{ old('payout_amount', $bettingTicket->payout_amount) }}"
                 min="0" step="1000"
                 placeholder="Nhập số tiền trả">
          @error('payout_amount')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
          @enderror
          <p class="mt-1 text-xs text-gray-500">Số tiền thực tế đã trả cho khách hàng</p>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="pb-3">
      <button type="submit" class="btn btn-primary w-full">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Cập nhật phiếu cược
      </button>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const resultSelect = document.getElementById('result');
  const payoutAmount = document.getElementById('payout_amount');
  const parseBtn = document.getElementById('parse-message-btn');
  const originalMessage = document.getElementById('original_message');
  const parseResult = document.getElementById('parse-result');
  const parsePreview = document.getElementById('parse-preview');
  const regionSelect = document.getElementById('region');
  const bettingDate = document.getElementById('betting_date');
  const customerId = document.getElementById('customer_id').value;
  
  resultSelect.addEventListener('change', function() {
    if (this.value === 'lose') {
      payoutAmount.value = 0;
    }
  });
  
  if (parseBtn) {
    parseBtn.addEventListener('click', function() {
      const message = originalMessage.value.trim();
      if (!message) {
        alert('Vui lòng nhập tin nhắn cược');
        return;
      }
      
      const region = regionSelect.value;
      const date = bettingDate.value;
      
      parseBtn.disabled = true;
      parseBtn.innerHTML = '<svg class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Đang parse...';
      
      fetch('{{ route("user.betting-tickets.parse-message") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
          message: message,
          customer_id: customerId,
          region: region,
          date: date
        })
      })
      .then(response => response.json())
      .then(data => {
        parseBtn.disabled = false;
        parseBtn.innerHTML = '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Parse lại tin nhắn';
        
        if (data.is_valid) {
          // Handle multiple bets or single bet
          let bet = null;
          if (data.multiple_bets && data.multiple_bets.length > 0) {
            // Take first bet if multiple
            bet = data.multiple_bets[0];
          } else if (data.betting_type) {
            // Single bet format
            bet = {
              betting_type: data.betting_type,
              betting_type_id: data.betting_type_id,
              numbers: data.numbers || [],
              amount: data.amount || 0,
              station: data.station || ''
            };
          }
          
          if (bet) {
            // Update betting type if we have betting_type_id
            if (bet.betting_type_id) {
              document.getElementById('betting_type_id').value = bet.betting_type_id;
            }
            
            // Update bet amount
            if (bet.amount) {
              document.getElementById('bet_amount').value = bet.amount;
            }
            
            // Update numbers
            if (bet.numbers && bet.numbers.length > 0) {
              const numbersArray = Array.isArray(bet.numbers[0]) ? bet.numbers.flat() : bet.numbers;
              document.getElementById('betting_numbers').value = numbersArray.join(' ');
            }
            
            // Show preview
            let previewHtml = '<div class="space-y-1">';
            previewHtml += `<div><strong>Loại cược:</strong> ${bet.type || bet.betting_type || 'N/A'}</div>`;
            const numbersArray = bet.numbers && Array.isArray(bet.numbers) ? (Array.isArray(bet.numbers[0]) ? bet.numbers.flat() : bet.numbers) : [];
            previewHtml += `<div><strong>Số cược:</strong> ${numbersArray.length > 0 ? numbersArray.join(', ') : 'N/A'}</div>`;
            previewHtml += `<div><strong>Tiền cược:</strong> ${bet.amount ? (bet.amount / 1000).toFixed(1) + 'k' : 'N/A'}</div>`;
            if (bet.station) {
              previewHtml += `<div><strong>Đài:</strong> ${bet.station}</div>`;
            }
            if (bet.cost_xac) {
              previewHtml += `<div><strong>Tiền xác:</strong> ${(bet.cost_xac / 1000).toFixed(1)}k</div>`;
            }
            previewHtml += '</div>';
            
            parsePreview.innerHTML = previewHtml;
            parseResult.classList.remove('hidden');
          } else {
            parsePreview.innerHTML = '<div class="text-yellow-600">Parse thành công nhưng không có thông tin cược. Vui lòng nhập thủ công.</div>';
            parseResult.classList.remove('hidden');
          }
        } else {
          parsePreview.innerHTML = '<div class="text-red-600">' + (data.errors ? data.errors.join(', ') : data.message || 'Không thể parse tin nhắn') + '</div>';
          parseResult.classList.remove('hidden');
        }
      })
      .catch(error => {
        parseBtn.disabled = false;
        parseBtn.innerHTML = '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg> Parse lại tin nhắn';
        parsePreview.innerHTML = '<div class="text-red-600">Lỗi: ' + error.message + '</div>';
        parseResult.classList.remove('hidden');
      });
    });
  }
});
</script>
@endsection
