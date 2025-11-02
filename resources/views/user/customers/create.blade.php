@extends('layouts.app')

@section('title', 'Thêm khách hàng - Keki SaaS')

@section('content')
<div class="pb-4">
  <!-- Sticky Header -->
  <div class="sticky top-0 z-10 bg-white shadow-sm border-b border-gray-200 mb-3">
    <div class="px-3 py-2.5">
      <div class="flex items-center justify-between">
        <h1 class="text-lg font-bold text-gray-900">Thêm khách hàng</h1>
        <a href="{{ route('user.customers.index') }}" 
           class="inline-flex items-center justify-center p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
          </svg>
        </a>
      </div>
    </div>
  </div>

  <!-- Error Messages -->
  @if ($errors->any())
  <div class="px-3 mb-3">
    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
      <div class="text-sm font-medium text-red-800 mb-1">Vui lòng kiểm tra lại:</div>
      <ul class="text-xs text-red-700 space-y-0.5">
        @foreach ($errors->all() as $error)
          <li>• {{ $error }}</li>
        @endforeach
      </ul>
    </div>
  </div>
  @endif

  <form method="POST" action="{{ route('user.customers.store') }}" class="px-3 space-y-4">
    @csrf

    <!-- Basic Info Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-3">
      <h3 class="text-xs font-semibold text-gray-700 mb-3 uppercase tracking-wide">Thông tin cơ bản</h3>
      
      <div class="space-y-3">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Tên khách hàng <span class="text-red-500">*</span></label>
          <input type="text" name="name" required value="{{ old('name') }}"
                 class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Số điện thoại</label>
          <input type="tel" name="phone" value="{{ old('phone') }}"
                 class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Trạng thái</label>
            <select name="is_active" class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
              <option value="1" {{ old('is_active','1')=='1'?'selected':'' }}>Hoạt động</option>
              <option value="0" {{ old('is_active')=='0'?'selected':'' }}>Khóa</option>
            </select>
          </div>
          
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Vai trò</label>
            <div class="flex items-center gap-4 pt-2">
              <label class="inline-flex items-center gap-1.5">
                <input type="radio" name="is_owner" value="0" class="h-4 w-4 text-indigo-600"
                       {{ old('is_owner','0')=='0'?'checked':'' }}>
                <span class="text-xs text-gray-800">Khách</span>
              </label>
              <label class="inline-flex items-center gap-1.5">
                <input type="radio" name="is_owner" value="1" class="h-4 w-4 text-indigo-600"
                       {{ old('is_owner')=='1'?'checked':'' }}>
                <span class="text-xs text-gray-800">Chủ</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Rates Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-3">
      <h3 class="text-xs font-semibold text-gray-700 mb-3 uppercase tracking-wide">Bảng giá</h3>
      
      <!-- Region Tabs -->
      <div class="flex gap-2 mb-3 overflow-x-auto scrollbar-thin pb-1">
        @php $firstTab = true; @endphp
        @foreach($regions as $rKey => $rLabel)
          <button type="button"
            class="tab-btn flex-shrink-0 inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium border
                   {{ $firstTab ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-900 border-gray-300 hover:bg-gray-50' }}"
            data-tab="{{ $rKey }}">
            {{ $rLabel }}
          </button>
          @php $firstTab = false; @endphp
        @endforeach
      </div>

      <!-- Tab Panes -->
      @foreach($regions as $rKey => $rLabel)
      <div class="tab-pane {{ $loop->first ? '' : 'hidden' }}" data-tab="{{ $rKey }}">
        <div class="space-y-3 max-h-96 overflow-y-auto scrollbar-thin">
          @foreach($rateGroups as $groupTitle => $pairs)
            @php
              $isBayLoGroup = str_contains($groupTitle, 'Bảy lô');
              if ($rKey === 'bac' && $isBayLoGroup) continue;
            @endphp
            <div class="rounded-lg border border-gray-200 overflow-hidden">
              <div class="bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-800">{{ $groupTitle }}</div>
              <div class="divide-y divide-gray-100">
                @foreach($pairs as $betKey => $label)
                  @if($rKey==='bac' && in_array($betKey, ['baylo_2','baylo_3'], true)) @continue @endif
                  @php $val = $initialRates[$rKey][$betKey] ?? ['commission'=>null,'payout_times'=>null]; @endphp
                  <div class="p-2.5 space-y-2">
                    <div class="text-xs font-medium text-gray-900">{{ $label }}</div>
                    <div class="grid grid-cols-2 gap-2">
                      <div>
                        <label class="block text-[10px] text-gray-500 mb-0.5">Giá</label>
                        <input type="number" step="0.01" min="0" max="1"
                               name="rates[{{ $rKey }}][{{ $betKey }}][commission]"
                               value="{{ old("rates.$rKey.$betKey.commission", $val['commission']) }}"
                               class="w-full text-xs rounded border border-gray-300 bg-white px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                      </div>
                      <div>
                        <label class="block text-[10px] text-gray-500 mb-0.5">Lần ăn</label>
                        <input type="number" min="0"
                               name="rates[{{ $rKey }}][{{ $betKey }}][payout_times]"
                               value="{{ old("rates.$rKey.$betKey.payout_times", $val['payout_times']) }}"
                               class="w-full text-xs rounded border border-gray-300 bg-white px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      </div>
      @endforeach
    </div>

    <!-- Submit Button -->
    <div class="pb-3">
      <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition shadow-sm">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Lưu khách hàng & bảng giá
      </button>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const btns = document.querySelectorAll('.tab-btn');
  const panes = document.querySelectorAll('.tab-pane');
  btns.forEach(btn => {
    btn.addEventListener('click', () => {
      const tab = btn.dataset.tab;
      btns.forEach(b => {
        b.classList.remove('bg-indigo-600','text-white','border-indigo-600');
        b.classList.add('bg-white','text-gray-900','border-gray-300');
      });
      btn.classList.add('bg-indigo-600','text-white','border-indigo-600');
      btn.classList.remove('bg-white','text-gray-900','border-gray-300');
      panes.forEach(p => p.classList.toggle('hidden', p.dataset.tab !== tab));
    });
  });
});
</script>
@endsection
