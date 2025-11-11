@extends('layouts.app')

@section('title', 'Thêm phiếu cược - Keki SaaS')

@section('content')
@php
  $globalDate = session('global_date', today());
  $globalRegion = session('global_region', 'bac');
@endphp

<div class="pb-4">
  <!-- Header -->
  <div class="sticky top-14 z-10 bg-gray-50 border-b border-gray-200 -mx-3 px-3 py-2 mb-3">
    <div class="flex items-center justify-between">
      <div class="flex-1 min-w-0">
        <h1 class="text-base font-semibold text-gray-900">Thêm phiếu cược</h1>
        <div class="flex items-center gap-2 mt-1">
          <span class="text-xs text-gray-500">{{ \App\Support\Region::label($globalRegion) }}</span>
          <span class="text-xs text-gray-400">•</span>
          <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($globalDate)->format('d/m/Y') }}</span>
        </div>
      </div>
      <a href="{{ route('user.betting-tickets.index') }}" class="btn bg-gray-100 btn-sm btn-icon">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
      </a>
    </div>
  </div>

  <form method="POST" action="{{ route('user.betting-tickets.store') }}" id="betting-ticket-form" class="space-y-5">
    @csrf
    
    <!-- Hidden fields -->
    <input type="hidden" name="betting_date" id="betting_date" value="{{ $globalDate }}">
    <input type="hidden" name="region" id="region" value="{{ $globalRegion }}">
    <input type="hidden" name="station" id="station" value="">

    <!-- Customer Selection -->
    <div class="bg-white border border-gray-200 rounded-lg mb-3">
      <div class="p-4">
        <label for="customer_id" class="block text-sm font-semibold text-gray-900 mb-3">
          Khách hàng <span class="text-red-500">*</span>
        </label>
        <select id="customer_id" name="customer_id" 
                class="w-full text-sm" required>
          <option value="">Chọn khách hàng</option>
          @foreach($customers as $customer)
            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
              {{ $customer->name }} ({{ $customer->phone }})
            </option>
          @endforeach
        </select>
        @error('customer_id')
          <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
        @enderror
      </div>
    </div>

    <!-- Message Input -->
    <div class="bg-white border border-gray-200 rounded-lg mb-3">
      <div class="p-4">
        <label for="original_message" class="block text-sm font-semibold text-gray-900 mb-3">
          Tin nhắn cược <span class="text-red-500">*</span>
        </label>
        <textarea id="original_message" name="original_message" rows="6"
                  class="w-full text-sm"
                  placeholder="Ví dụ: lo 12 34 56 100000&#10;bao 01 02 03 50000 2d&#10;da 12 34 200000 hcm"
                  required>{{ old('original_message') }}</textarea>
        @error('original_message')
          <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
        @enderror
        <p class="mt-3 text-xs text-gray-500">
          Hệ thống sẽ tự động phân tích và phát hiện đài từ tin nhắn
        </p>
      </div>
    </div>

    <!-- Parse Button -->
    <button type="button" id="parse-btn" 
            class="btn btn-primary w-full mb-3">
      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
      </svg>
      Phân tích tin nhắn
    </button>

    <!-- Parse Result -->
    <div id="parse-result" class="hidden">
      <div class="bg-white border border-gray-200 rounded-lg mb-3">
        <div class="p-4 space-y-5">
          <!-- Tổng tiền theo loại cược -->
          <div id="total-summary-table" class="hidden">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Tổng tiền cược & xác</h3>
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Loại cược</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-700">Tiền cược</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-700">Tiền xác</th>
                  </tr>
                </thead>
                <tbody id="total-summary-table-body" class="divide-y divide-gray-100">
                </tbody>
                <tfoot id="total-summary-table-footer" class="bg-gray-50">
                </tfoot>
              </table>
            </div>
          </div>
          
          <!-- Preview Numbers -->
          <div id="preview-numbers" class="hidden">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Preview số</h3>
            <div id="preview-numbers-content" class="space-y-2"></div>
          </div>
          
          <!-- Kết quả phân tích chi tiết -->
          <div id="parse-details" class="hidden">
            <div class="border-t border-gray-200 pt-4">
              <h3 class="text-sm font-semibold text-gray-900 mb-3">Kết quả phân tích</h3>
              <div id="parse-content" class="text-xs text-gray-600"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="space-y-2 pb-6">
      <button type="submit" class="btn btn-primary w-full">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Xử lý
      </button>
      <a href="{{ route('user.betting-tickets.index') }}" 
         class="btn btn-secondary w-full text-center">
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
    parseBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Đang phân tích...';
    
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
          // Tạo key unique cho từng loại cược chi tiết
          function getDetailedTypeKey(bet) {
            const typeCode = bet.type_code || 'unknown';
            const meta = bet.meta || {};
            
            // Bao lô: phân theo digits (2, 3, 4)
            if (typeCode === 'bao_lo') {
              const digits = meta.digits || 2;
              return `bao_lo_${digits}`;
            }
            
            // Đá xiên: phân theo số đài (2, 3, 4)
            if (typeCode === 'da_xien') {
              const daiCount = meta.dai_count || 2;
              return `da_xien_${daiCount}`;
            }
            
            // Xiên: phân theo xien_size (2, 3, 4)
            if (typeCode === 'xien') {
              const xienSize = meta.xien_size || 2;
              return `xien_${xienSize}`;
            }
            
            // Các loại khác: dùng type_code
            return typeCode;
          }
          
          // Tạo label chi tiết
          function getDetailedTypeLabel(bet) {
            const typeCode = bet.type_code || 'unknown';
            const meta = bet.meta || {};
            
            // Bao lô: đã có digits trong label từ controller
            if (typeCode === 'bao_lo') {
              const digits = meta.digits || 2;
              return `Bao lô ${digits} số`;
            }
            
            // Đá xiên: thêm số đài
            if (typeCode === 'da_xien') {
              const daiCount = meta.dai_count || 2;
              return `Đá xiên ${daiCount} đài`;
            }
            
            // Xiên: thêm size
            if (typeCode === 'xien') {
              const xienSize = meta.xien_size || 2;
              return `Xiên ${xienSize}`;
            }
            
            // Các loại khác: dùng label từ controller
            return bet.type || typeCode;
          }
          
          // Tổng tiền theo loại cược chi tiết
          const summaryByDetailedType = {};
          const numbersByDetailedType = {};
          let grandTotalAmount = 0;
          let grandTotalCostXac = 0;
          
          data.multiple_bets.forEach(bet => {
            const detailedKey = getDetailedTypeKey(bet);
            const detailedLabel = getDetailedTypeLabel(bet);
            const amount = bet.amount || 0;
            const costXac = bet.cost_xac || 0;
            const station = bet.station || '';
            
            // Initialize summary
            if (!summaryByDetailedType[detailedKey]) {
              summaryByDetailedType[detailedKey] = {
                label: detailedLabel,
                totalAmount: 0,
                totalCostXac: 0
              };
              numbersByDetailedType[detailedKey] = {
                label: detailedLabel,
                numbers: []
              };
            }
            
            // Tính tổng tiền
            summaryByDetailedType[detailedKey].totalAmount += amount;
            summaryByDetailedType[detailedKey].totalCostXac += costXac;
            grandTotalAmount += amount;
            grandTotalCostXac += costXac;
            
            // Collect numbers với station (giữ nguyên tất cả, không loại bỏ trùng lặp)
            if (bet.numbers && Array.isArray(bet.numbers)) {
              bet.numbers.forEach(num => {
                let numStr = '';
                if (Array.isArray(num)) {
                  numStr = num.join('-');
                } else {
                  numStr = num.toString();
                }
                
                // Thêm station vào số
                const stationLabel = station ? ` [${station}]` : '';
                numbersByDetailedType[detailedKey].numbers.push({
                  number: numStr,
                  station: stationLabel
                });
              });
            }
          });
          
          // Hiển thị bảng tổng tiền - iOS Style
          const totalSummaryTableDiv = document.getElementById('total-summary-table');
          const totalSummaryTableBody = document.getElementById('total-summary-table-body');
          const totalSummaryTableFooter = document.getElementById('total-summary-table-footer');
          
          let tableBodyHtml = '';
          Object.keys(summaryByDetailedType).sort().forEach(key => {
            const summaryData = summaryByDetailedType[key];
            const amountInK = (summaryData.totalAmount / 1000).toFixed(1);
            const costInK = (summaryData.totalCostXac / 1000).toFixed(1);
            
            tableBodyHtml += `<tr>
              <td class="px-3 py-2 text-gray-900">${summaryData.label}</td>
              <td class="px-3 py-2 text-right text-blue-600 font-semibold">${amountInK}k</td>
              <td class="px-3 py-2 text-right text-green-600 font-semibold">${costInK}k</td>
            </tr>`;
          });
          
          const grandAmountInK = (grandTotalAmount / 1000).toFixed(1);
          const grandCostInK = (grandTotalCostXac / 1000).toFixed(1);
          const tableFooterHtml = `<tr class="bg-gray-50 border-t-2 border-gray-300">
            <td class="px-3 py-2 text-gray-900 font-semibold">Tổng cộng</td>
            <td class="px-3 py-2 text-right text-blue-600 font-semibold">${grandAmountInK}k</td>
            <td class="px-3 py-2 text-right text-green-600 font-semibold">${grandCostInK}k</td>
          </tr>`;
          
          if (tableBodyHtml) {
            totalSummaryTableBody.innerHTML = tableBodyHtml;
            totalSummaryTableFooter.innerHTML = tableFooterHtml;
            totalSummaryTableDiv.classList.remove('hidden');
          } else {
            totalSummaryTableDiv.classList.add('hidden');
          }
          
          // Hàm chuyển đổi tên đài thành code
          function getStationCode(stationName) {
            if (!stationName) return '';
            
            const stationMap = {
              'tien giang': 'tg',
              'tiền giang': 'tg',
              'an giang': 'ag',
              'tay ninh': 'tn',
              'tây ninh': 'tn',
              'tp.hcm': 'hcm',
              'tp hcm': 'hcm',
              'ho chi minh': 'hcm',
              'hồ chí minh': 'hcm',
              'ben tre': 'bt',
              'bến tre': 'bt',
              'vinh long': 'vl',
              'vĩnh long': 'vl',
              'tra vinh': 'tv',
              'trà vinh': 'tv',
              'kien giang': 'kg',
              'kiên giang': 'kg',
              'da lat': 'dl',
              'đà lạt': 'dl',
              'ca mau': 'cm',
              'cà mau': 'cm',
              'can tho': 'ct',
              'cần thơ': 'ct',
              'dong nai': 'dn',
              'đồng nai': 'dn',
              'dong thap': 'dthap',
              'đồng tháp': 'dthap',
              'soc trang': 'st',
              'sóc trăng': 'st',
              'vung tau': 'vt',
              'vũng tàu': 'vt',
              'long an': 'la',
              'binh phuoc': 'bp',
              'bình phước': 'bp',
              'hau giang': 'hg',
              'hậu giang': 'hg',
              'binh duong': 'bd',
              'bình dương': 'bd',
              'bac lieu': 'bl',
              'bạc liêu': 'bl',
              'binh thuan': 'bth',
              'bình thuận': 'bth',
              'mien bac': 'mb',
              'miền bắc': 'mb',
              'ha noi': 'hn',
              'hà nội': 'hn',
              'da nang': 'dna',
              'đà nẵng': 'dna',
              'khanh hoa': 'kh',
              'khánh hòa': 'kh',
              'phu yen': 'py',
              'phú yên': 'py',
              'quang nam': 'qna',
              'quảng nam': 'qna',
              'quang ngai': 'qng',
              'quảng ngãi': 'qng',
              'binh dinh': 'bdi',
              'bình định': 'bdi',
              'thua thien hue': 'tth',
              'thừa thiên huế': 'tth',
            };
            
            const normalized = stationName.toLowerCase().trim();
            return stationMap[normalized] || stationName;
          }
          
          // Preview Numbers
          const previewNumbersDiv = document.getElementById('preview-numbers');
          const previewNumbersContent = document.getElementById('preview-numbers-content');
          
          let previewHtml = '';
          Object.keys(numbersByDetailedType).sort().forEach(key => {
            const typeData = numbersByDetailedType[key];
            
            previewHtml += `<div class="bg-white border border-gray-200 rounded-lg p-3">
              <div class="text-sm font-semibold text-gray-900 mb-2">${typeData.label}</div>
              <div class="flex flex-wrap gap-2">
                ${typeData.numbers.map(item => {
                  // Extract station name from item.station (format: " [station name]")
                  const stationMatch = item.station.match(/\[(.+?)\]/);
                  const stationCode = stationMatch ? getStationCode(stationMatch[1]) : '';
                  const stationLabel = stationCode ? ` [${stationCode}]` : '';
                  return `<span class="px-3 py-1.5 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-lg text-xs font-semibold text-blue-700 shadow-sm">${item.number}${stationLabel}</span>`;
                }).join('')}
              </div>
            </div>`;
          });
          
          if (previewHtml) {
            previewNumbersContent.innerHTML = previewHtml;
            previewNumbersDiv.classList.remove('hidden');
          } else {
            previewNumbersDiv.classList.add('hidden');
          }
          
          // Parse Details
          const parseDetailsDiv = document.getElementById('parse-details');
          let html = `<p class="text-green-600 font-medium mb-3 text-xs">✓ Phân tích được ${data.multiple_bets.length} phiếu cược</p>`;
          data.multiple_bets.forEach((bet, idx) => {
            html += `<div class="bg-gray-50 rounded-lg p-2 mb-2">
              <div class="text-xs font-medium text-gray-900">
                <span class="text-blue-600">${bet.type || 'N/A'}</span>
                <span class="text-gray-500">•</span>
                <span class="text-green-600">${(bet.numbers || []).join(', ')}</span>
              </div>
              <div class="text-xs text-gray-500 mt-0.5">${bet.station || '-'} • ${(bet.amount || 0).toLocaleString()}đ</div>
            </div>`;
          });
          parseContent.innerHTML = html;
          parseDetailsDiv.classList.remove('hidden');
        } else {
          // Single bet
          document.getElementById('total-summary-table').classList.add('hidden');
          document.getElementById('preview-numbers').classList.add('hidden');
          document.getElementById('parse-details').classList.add('hidden');
          
          parseContent.innerHTML = `
            <div class="space-y-2 text-sm text-gray-900">
              <p><strong>Loại:</strong> ${data.betting_type?.name || 'N/A'}</p>
              <p><strong>Số:</strong> ${data.numbers?.join(', ') || 'N/A'}</p>
              <p><strong>Tiền:</strong> ${(data.amount || 0).toLocaleString()}đ</p>
              <p class="text-green-600 font-medium">✓ Tin nhắn hợp lệ</p>
            </div>
          `;
          document.getElementById('parse-details').classList.remove('hidden');
        }
        parseResult.classList.remove('hidden');
      } else {
        // Error
        document.getElementById('total-summary-table').classList.add('hidden');
        document.getElementById('preview-numbers').classList.add('hidden');
        document.getElementById('parse-details').classList.add('hidden');
        
        parseContent.innerHTML = `
          <p class="text-red-500 font-medium mb-2">✗ Tin nhắn không hợp lệ</p>
          <ul class="text-sm text-red-500 list-disc list-inside">
            ${(data.errors || []).map(e => `<li>${e}</li>`).join('')}
          </ul>
        `;
        document.getElementById('parse-details').classList.remove('hidden');
        parseResult.classList.remove('hidden');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      parseContent.innerHTML = '<p class="text-red-500 text-sm">Có lỗi xảy ra khi phân tích</p>';
      parseResult.classList.remove('hidden');
    })
    .finally(() => {
      parseBtn.disabled = false;
      parseBtn.innerHTML = `<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
      </svg>Phân tích tin nhắn`;
    });
  });
});
</script>
@endsection
