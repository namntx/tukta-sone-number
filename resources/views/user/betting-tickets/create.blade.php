@extends('layouts.app')

@section('title', 'Thêm phiếu cược - Keki SaaS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Thêm phiếu cược mới
                </h1>
                <p class="text-gray-600 mt-1">
                    Tạo phiếu cược từ tin nhắn hoặc thông tin thủ công
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
        <form method="POST" action="{{ route('user.betting-tickets.store') }}" id="betting-ticket-form">
            @csrf
            
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
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
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
                               value="{{ old('betting_date', $global_date) }}"
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
                            <option value="Bắc" {{ old('region', $global_region) == 'Bắc' ? 'selected' : '' }}>Bắc</option>
                            <option value="Trung" {{ old('region', $global_region) == 'Trung' ? 'selected' : '' }}>Trung</option>
                            <option value="Nam" {{ old('region', $global_region) == 'Nam' ? 'selected' : '' }}>Nam</option>
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
                               value="{{ old('station') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('station') border-red-500 @enderror"
                               placeholder="Nhập tên đài (sẽ tự động nhận diện từ tin nhắn)"
                               required>
                        @error('station')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">
                            Hệ thống sẽ tự động nhận diện tên đài từ tin nhắn. Bạn có thể nhập thủ công nếu cần.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Message Input -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Tin nhắn cược</h3>
                <div class="space-y-4">
                    <div>
                        <label for="original_message" class="block text-sm font-medium text-gray-700 mb-2">
                            Tin nhắn gốc <span class="text-red-500">*</span>
                        </label>
                        <textarea id="original_message" 
                                  name="original_message" 
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('original_message') border-red-500 @enderror"
                                  placeholder="Nhập tin nhắn cược (ví dụ: lo 12 34 56 100000)"
                                  required>{{ old('original_message') }}</textarea>
                        @error('original_message')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">
                            Ví dụ: "lo 12 34 56 100000", "bao 01 02 03 50000", "da 12 34 200000"
                        </p>
                    </div>
                    
                    <div class="flex space-x-4">
                        <button type="button" 
                                id="parse-btn" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                            Phân tích tin nhắn
                        </button>
                    </div>
                    
                    <!-- Parse Result -->
                    <div id="parse-result" class="hidden">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Kết quả phân tích:</h4>
                            <div id="parse-content" class="text-sm text-gray-600"></div>
                        </div>
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
                    Tạo phiếu cược
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const parseBtn = document.getElementById('parse-btn');
    const originalMessage = document.getElementById('original_message');
    const customerId = document.getElementById('customer_id');
    const parseResult = document.getElementById('parse-result');
    const parseContent = document.getElementById('parse-content');
    
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
        parseBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Đang phân tích...';
        
        // Make AJAX request
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
                // Auto-fill station if detected
                if (data.station) {
                    document.getElementById('station').value = data.station.name;
                }
                
                // Display stations info
                let stationsInfo = '';
                if (data.stations && data.stations.length > 0) {
                    stationsInfo = `<p><strong>Đài:</strong> ${data.stations.join(', ')}</p>`;
                } else if (data.station) {
                    stationsInfo = `<p><strong>Đài:</strong> ${data.station.name}</p>`;
                }
                
                // Check if we have multiple bets
                if (data.multiple_bets && data.multiple_bets.length > 0) {
                    // Show multiple bets table
                    let tableHTML = `
                        <div class="space-y-3">
                            <p class="text-green-600"><strong>✓ Tin nhắn hợp lệ - Phân tích được ${data.multiple_bets.length} phiếu cược</strong></p>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Đài</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Số cược</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Loại cược</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Số tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                    `;
                    
                    let totalAmount = 0;
                    data.multiple_bets.forEach((bet, index) => {
                        const stationName = bet.station ? bet.station.name : 
                                           (bet.stations && bet.stations.length > 0 ? bet.stations.join(', ') : '-');
                        const numbersDisplay = bet.numbers && bet.numbers.length > 0 ? 
                                              (bet.numbers.length > 5 ? 
                                               bet.numbers.slice(0, 5).join(', ') + `... (+${bet.numbers.length - 5})` :
                                               bet.numbers.join(', ')) : '-';
                        const bettingTypeName = bet.betting_type ? bet.betting_type.name : 'Không xác định';
                        const amount = bet.amount || 0;
                        totalAmount += amount;
                        
                        tableHTML += `
                            <tr class="${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}">
                                <td class="px-3 py-2 text-sm text-gray-900">${index + 1}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">${stationName}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">
                                    <div class="max-w-xs truncate" title="${bet.numbers ? bet.numbers.join(', ') : ''}">
                                        ${numbersDisplay}
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-900">${bettingTypeName}</td>
                                <td class="px-3 py-2 text-sm text-red-600 text-right font-medium">${amount.toLocaleString()} VNĐ</td>
                            </tr>
                        `;
                    });
                    
                    tableHTML += `
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td colspan="4" class="px-3 py-2 text-sm font-medium text-gray-900 text-right">Tổng cộng:</td>
                                            <td class="px-3 py-2 text-sm font-bold text-red-600 text-right">${totalAmount.toLocaleString()} VNĐ</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <p><strong>Tin nhắn gốc:</strong> ${data.parsed_message || 'Tin nhắn phức tạp'}</p>
                        </div>
                    `;
                    parseContent.innerHTML = tableHTML;
                } else {
                    // Single bet display
                    parseContent.innerHTML = `
                        <div class="space-y-2">
                            <p><strong>Loại cược:</strong> ${data.betting_type ? data.betting_type.name : 'Không xác định'}</p>
                            <p><strong>Số cược:</strong> ${data.numbers && data.numbers.length > 0 ? data.numbers.join(', ') : 'Theo mẫu'}</p>
                            <p><strong>Số tiền:</strong> ${data.amount ? data.amount.toLocaleString() : '0'} VNĐ</p>
                            ${stationsInfo}
                            <p><strong>Tin nhắn đã phân tích:</strong> ${data.parsed_message}</p>
                            <p class="text-green-600"><strong>✓ Tin nhắn hợp lệ</strong></p>
                        </div>
                    `;
                }
                parseResult.classList.remove('hidden');
            } else {
                parseContent.innerHTML = `
                    <div class="space-y-2">
                        <p class="text-red-600"><strong>✗ Tin nhắn không hợp lệ</strong></p>
                        <ul class="list-disc list-inside text-red-600">
                            ${data.errors.map(error => `<li>${error}</li>`).join('')}
                        </ul>
                    </div>
                `;
                parseResult.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            parseContent.innerHTML = '<p class="text-red-600">Có lỗi xảy ra khi phân tích tin nhắn</p>';
            parseResult.classList.remove('hidden');
        })
        .finally(() => {
            // Reset button
            parseBtn.disabled = false;
            parseBtn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>Phân tích tin nhắn';
        });
    });
});
</script>
@endsection
