@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-6 space-y-6">

  <div class="bg-white shadow rounded-lg p-6 border border-gray-200">
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-lg font-semibold text-gray-900">Sửa khách hàng</h1>
      <a href="{{ route('user.customers.index') }}"
         class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-600 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
        Quay lại
      </a>
    </div>

    @if (session('status'))
      <div class="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ session('status') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <div class="font-medium mb-1">Vui lòng kiểm tra lại:</div>
        <ul class="list-disc list-inside space-y-0.5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('user.customers.update', $customer) }}" class="space-y-8">
      @csrf
      @method('PUT')

      {{-- Basic info --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Tên khách hàng <span class="text-red-500">*</span></label>
          <input type="text" name="name" required value="{{ old('name', $customer->name) }}"
                 class="w-full rounded-md border border-gray-300 bg-white text-sm px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Số điện thoại</label>
          <input type="tel" name="phone" value="{{ old('phone', $customer->phone) }}"
                 class="w-full rounded-md border border-gray-300 bg-white text-sm px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-gray-600 mb-1">Ghi chú</label>
          <input type="text" name="note" value="{{ old('note', $customer->note) }}"
                 class="w-full rounded-md border border-gray-300 bg-white text-sm px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Trạng thái</label>
          <select name="is_active" class="w-full rounded-md border border-gray-300 bg-white text-sm px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="1" {{ old('is_active', (string)$customer->is_active)=='1'?'selected':'' }}>Đang hoạt động</option>
            <option value="0" {{ old('is_active', (string)$customer->is_active)=='0'?'selected':'' }}>Tạm khóa</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Vai trò</label>
          <div class="flex items-center gap-6">
            <label class="inline-flex items-center gap-2">
              <input type="radio" name="is_owner" value="0" class="h-4 w-4 text-indigo-600 border-gray-300"
                     {{ old('is_owner', (string)$customer->is_owner)=='0'?'checked':'' }}>
              <span class="text-sm text-gray-800">Khách</span>
            </label>
            <label class="inline-flex items-center gap-2">
              <input type="radio" name="is_owner" value="1" class="h-4 w-4 text-indigo-600 border-gray-300"
                     {{ old('is_owner', (string)$customer->is_owner)=='1'?'checked':'' }}>
              <span class="text-sm text-gray-800">Chủ</span>
            </label>
          </div>
        </div>
      </div>

      {{-- Tabs --}}
      <div>
        <div class="flex flex-wrap gap-2 mb-4">
          @php $firstTab = true; @endphp
          @foreach($regions as $rKey => $rLabel)
            <button type="button"
              class="tab-btn inline-flex items-center px-3 py-1.5 rounded-md text-sm border
                     {{ $firstTab ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-900 border-gray-300 hover:bg-gray-50' }}"
              data-tab="{{ $rKey }}">
              {{ $rLabel }}
            </button>
            @php $firstTab = false; @endphp
          @endforeach
        </div>

        @foreach($regions as $rKey => $rLabel)
        <div class="tab-pane {{ $loop->first ? '' : 'hidden' }}" data-tab="{{ $rKey }}">
          <div class="space-y-4">
            @foreach($rateGroups as $groupTitle => $pairs)
              @php
                $isBayLoGroup = str_contains($groupTitle, 'Bảy lô');
                if ($rKey === 'bac' && $isBayLoGroup) continue;
              @endphp
              <div class="rounded-lg border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-800">{{ $groupTitle }}</div>
                <div class="divide-y divide-gray-200">
                  @foreach($pairs as $betKey => $label)
                    @if($rKey==='bac' && in_array($betKey, ['baylo_2','baylo_3'], true)) @continue @endif
                    @php $val = $initialRates[$rKey][$betKey] ?? ['commission'=>null,'payout_times'=>null]; @endphp
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                      <div class="text-sm font-medium text-gray-900">{{ $label }}</div>
                      <div>
                        <label class="block text-xs text-gray-600 mb-1">Giá</label>
                        <input type="number" step="0.01" min="0" max="1"
                               name="rates[{{ $rKey }}][{{ $betKey }}][commission]"
                               value="{{ old("rates.$rKey.$betKey.commission", $val['commission']) }}"
                               class="w-full rounded-md border border-gray-300 bg-white text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                      </div>
                      <div>
                        <label class="block text-xs text-gray-600 mb-1">Lần ăn</label>
                        <input type="number" min="0"
                               name="rates[{{ $rKey }}][{{ $betKey }}][payout_times]"
                               value="{{ old("rates.$rKey.$betKey.payout_times", $val['payout_times']) }}"
                               class="w-full rounded-md border border-gray-300 bg-white text-sm focus:ring-indigo-500 focus:border-indigo-500" />
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

      <div class="flex justify-end">
        <button type="submit"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
          Lưu thay đổi & bảng giá
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const btns = document.querySelectorAll('.tab-btn');
    const panes= document.querySelectorAll('.tab-pane');
    btns.forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const tab = btn.dataset.tab;
        btns.forEach(b=>b.classList.remove('bg-indigo-600','text-white','border-indigo-600'));
        btns.forEach(b=>b.classList.add('bg-white','text-gray-900','border-gray-300'));
        btn.classList.add('bg-indigo-600','text-white','border-indigo-600');
        btn.classList.remove('bg-white','text-gray-900','border-gray-300');
        panes.forEach(p => p.classList.toggle('hidden', p.dataset.tab !== tab));
      });
    });
  });
</script>
@endsection
