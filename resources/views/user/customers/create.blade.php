@extends('layouts.app')

@section('title', 'Thêm khách hàng - Keki SaaS')

@section('content')
<div class="pb-4">
  <!-- Sticky Header -->
  <div class="sticky top-0 z-10 bg-white shadow-sm border-b border-gray-200 mb-3">
    <div class="px-3 py-2.5">
      <div class="flex items-center justify-between">
        <div class="flex-1 min-w-0">
          <h1 class="text-lg font-bold text-gray-900">Thêm khách hàng</h1>
          <p class="text-xs text-gray-500 mt-0.5">Tạo khách hàng mới và thiết lập bảng giá</p>
        </div>
        <div class="flex-shrink-0 ml-2">
          <a href="{{ route('user.customers.index') }}" 
             class="inline-flex items-center justify-center px-3 py-1.5 bg-purple-600 text-white text-xs font-medium rounded-lg hover:bg-purple-700 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
          </a>
        </div>
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
                 class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
        </div>
        
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Số điện thoại</label>
          <input type="tel" name="phone" value="{{ old('phone') }}"
                 class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
        </div>
        
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Trạng thái</label>
            <select name="is_active" class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
              <option value="1" {{ old('is_active','1')=='1'?'selected':'' }}>Hoạt động</option>
              <option value="0" {{ old('is_active')=='0'?'selected':'' }}>Khóa</option>
            </select>
          </div>
          
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Vai trò</label>
            <div class="flex items-center gap-4 pt-2">
              <label class="inline-flex items-center gap-1.5">
                <input type="radio" name="is_owner" value="0" class="h-4 w-4 text-purple-600"
                       {{ old('is_owner','0')=='0'?'checked':'' }}>
                <span class="text-xs text-gray-800">Khách</span>
              </label>
              <label class="inline-flex items-center gap-1.5">
                <input type="radio" name="is_owner" value="1" class="h-4 w-4 text-purple-600"
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
      <div class="flex gap-2 mb-3 overflow-x-auto pb-1" style="scrollbar-width: none; -ms-overflow-style: none;">
        @php $firstTab = true; @endphp
        @foreach($regions as $rKey => $rLabel)
          <button type="button"
            class="tab-btn flex-shrink-0 inline-flex items-center px-3 py-2 rounded-lg text-xs font-medium border transition-all
                   {{ $firstTab ? 'bg-purple-600 text-white border-purple-600' : 'bg-white text-gray-700 border-gray-300 active:bg-purple-50' }}"
            data-tab="{{ $rKey }}">
            {{ $rLabel }}
          </button>
          @php $firstTab = false; @endphp
        @endforeach
      </div>

      <!-- Tab Panes -->
      @foreach($regions as $rKey => $rLabel)
      <div class="tab-pane {{ $loop->first ? '' : 'hidden' }}" data-tab="{{ $rKey }}">
        <div class="space-y-3 max-h-[calc(100vh-320px)] overflow-y-auto">
          @foreach($rateGroups as $groupTitle => $pairs)
            @php
              $isBayLoGroup = str_contains($groupTitle, 'Bảy lô');
              if ($rKey === 'bac' && $isBayLoGroup) continue;
            @endphp
            <div>
              <div class="text-xs font-semibold text-gray-700 mb-2 px-1">{{ $groupTitle }}</div>
              <div class="space-y-2">
                @foreach($pairs as $betKey => $label)
                  @if($rKey==='bac' && in_array($betKey, ['baylo_2','baylo_3'], true)) @continue @endif
                  @php $val = $initialRates[$rKey][$betKey] ?? ['commission'=>null,'payout_times'=>null]; @endphp
                  <div class="flex items-end gap-2 bg-gray-50 rounded-lg p-2">
                    <div class="flex-1 min-w-0">
                      <label class="block text-xs text-gray-600 mb-1">{{ $label }}</label>
                      <input type="number" step="any" min="0"
                             name="rates[{{ $rKey }}][{{ $betKey }}][commission]"
                             value="{{ old("rates.$rKey.$betKey.commission", $val['commission']) }}"
                             placeholder="Giá"
                             class="w-full text-sm rounded-lg border border-gray-300 bg-white px-2.5 py-2 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <div class="flex-1 min-w-0">
                      <label class="block text-xs text-gray-600 mb-1">&nbsp;</label>
                      <input type="number" min="0"
                             name="rates[{{ $rKey }}][{{ $betKey }}][payout_times]"
                             value="{{ old("rates.$rKey.$betKey.payout_times", $val['payout_times']) }}"
                             placeholder="Lần ăn"
                             class="w-full text-sm rounded-lg border border-gray-300 bg-white px-2.5 py-2 focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
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
      <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition shadow-sm">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Lưu khách hàng & bảng giá
      </button>
    </div>
  </form>
</div>

<style>
  .overflow-x-auto::-webkit-scrollbar {
    display: none;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const btns = document.querySelectorAll('.tab-btn');
  const panes = document.querySelectorAll('.tab-pane');
  btns.forEach(btn => {
    btn.addEventListener('click', () => {
      const tab = btn.dataset.tab;
      btns.forEach(b => {
        b.classList.remove('bg-purple-600','text-white','border-purple-600');
        b.classList.add('bg-white','text-gray-700','border-gray-300');
      });
      btn.classList.add('bg-purple-600','text-white','border-purple-600');
      btn.classList.remove('bg-white','text-gray-700','border-gray-300');
      panes.forEach(p => p.classList.toggle('hidden', p.dataset.tab !== tab));
    });
  });
});
</script>
@endsection
