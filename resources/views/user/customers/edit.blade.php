@extends('layouts.app')

@section('title', 'Sửa khách hàng - Keki SaaS')

@section('content')
<div class="pb-4">
  <!-- Header -->
  <div class="sticky top-14 z-10 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 -mx-3 px-3 py-2 mb-3">
    <div class="flex items-center justify-between">
      <div class="flex-1 min-w-0">
        <h1 class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate">Sửa: {{ $customer->name }}</h1>
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $customer->phone }}</p>
      </div>
      <a href="{{ route('user.customers.index') }}" class="btn btn-secondary btn-sm btn-icon">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
      </a>
    </div>
  </div>

  <!-- Success -->
  @if (session('status'))
  <div class="alert alert-success mb-3">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
    </svg>
    <p class="text-sm">{{ session('status') }}</p>
  </div>
  @endif

  <!-- Errors -->
  @if ($errors->any())
  <div class="alert alert-error mb-3">
    <div>
      <div class="text-sm font-medium mb-1">Vui lòng kiểm tra lại:</div>
      <ul class="text-xs space-y-0.5">
        @foreach ($errors->all() as $error)
          <li>• {{ $error }}</li>
        @endforeach
      </ul>
    </div>
  </div>
  @endif

  <form method="POST" action="{{ route('user.customers.update', $customer) }}" class="space-y-3">
    @csrf
    @method('PUT')

    <!-- Basic Info -->
    <div class="card">
      <div class="card-header">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Thông tin cơ bản</h3>
      </div>
      <div class="card-body space-y-2.5">
        <div>
          <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Tên khách hàng <span class="text-red-500">*</span></label>
          <input type="text" name="name" required value="{{ old('name', $customer->name) }}">
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Số điện thoại</label>
          <input type="tel" name="phone" value="{{ old('phone', $customer->phone) }}">
        </div>

        <div class="grid grid-cols-2 gap-2.5">
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Trạng thái</label>
            <select name="is_active">
              <option value="1" {{ old('is_active', (string)$customer->is_active)=='1'?'selected':'' }}>Hoạt động</option>
              <option value="0" {{ old('is_active', (string)$customer->is_active)=='0'?'selected':'' }}>Khóa</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Vai trò</label>
            <div class="flex items-center gap-3 pt-1.5">
              <label class="inline-flex items-center gap-1">
                <input type="radio" name="is_owner" value="0" {{ old('is_owner', (string)$customer->is_owner)=='0'?'checked':'' }}>
                <span class="text-xs text-gray-800 dark:text-gray-200">Khách</span>
              </label>
              <label class="inline-flex items-center gap-1">
                <input type="radio" name="is_owner" value="1" {{ old('is_owner', (string)$customer->is_owner)=='1'?'checked':'' }}>
                <span class="text-xs text-gray-800 dark:text-gray-200">Chủ</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Rates -->
    <div class="card">
      <div class="card-header">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Bảng giá</h3>
      </div>
      <div class="card-body">
        <!-- Region Tabs -->
        <div class="flex gap-1.5 mb-3 overflow-x-auto hide-scrollbar">
          @php $firstTab = true; @endphp
          @foreach($regions as $rKey => $rLabel)
            <button type="button"
              class="tab-btn flex-shrink-0 px-3 py-1.5 rounded-md text-xs font-medium border transition-colors
                     {{ $firstTab ? 'bg-primary text-white border-primary' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-700' }}"
              data-tab="{{ $rKey }}">
              {{ $rLabel }}
            </button>
            @php $firstTab = false; @endphp
          @endforeach
        </div>

        <!-- Tab Panes -->
        @foreach($regions as $rKey => $rLabel)
        <div class="tab-pane {{ $loop->first ? '' : 'hidden' }}" data-tab="{{ $rKey }}">
          <div class="space-y-2.5 max-h-[calc(100vh-280px)] overflow-y-auto">
            @foreach($rateGroups as $groupTitle => $pairs)
              @php
                $isBayLoGroup = str_contains($groupTitle, 'Bảy lô');
                if ($rKey === 'bac' && $isBayLoGroup) continue;
              @endphp
              <div>
                <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">{{ $groupTitle }}</div>
                <div class="space-y-1.5">
                  @foreach($pairs as $betKey => $label)
                    @if($rKey==='bac' && in_array($betKey, ['baylo_2','baylo_3'], true)) @continue @endif
                    @php
                      // Map betKey to type_code and meta (same logic as controller)
                      $betKeyMap = match ($betKey) {
                          'de_dau'        => ['type_code' => 'dau',         'meta' => []],
                          'de_duoi'       => ['type_code' => 'duoi',        'meta' => []],
                          'de_duoi_4so'   => ['type_code' => 'duoi',        'meta' => ['digits' => 4]],
                          'bao_lo_2'      => ['type_code' => 'bao_lo',      'meta' => ['digits'=>2]],
                          'bao_lo_3'      => ['type_code' => 'bao_lo',      'meta' => ['digits'=>3]],
                          'bao_lo_4'      => ['type_code' => 'bao_lo',      'meta' => ['digits'=>4]],
                          'da_thang_1dai' => ['type_code' => 'da_thang',    'meta' => ['dai_count'=>1]],
                          'da_cheo_2dai'  => ['type_code' => 'da_xien',     'meta' => ['dai_count'=>2]],
                          'xien_2'        => ['type_code' => 'xien',        'meta' => ['xien_size'=>2]],
                          'xien_3'        => ['type_code' => 'xien',        'meta' => ['xien_size'=>3]],
                          'xien_4'        => ['type_code' => 'xien',        'meta' => ['xien_size'=>4]],
                          'xiu_chu'       => ['type_code' => 'xiu_chu',     'meta' => []],
                          'baylo_2'       => ['type_code' => 'bay_lo',      'meta' => ['digits'=>2]],
                          'baylo_3'       => ['type_code' => 'bay_lo',      'meta' => ['digits'=>3]],
                          default         => ['type_code' => null,          'meta' => []],
                      };
                      
                      // Build JSON key: "region:type_code[:d2][:x3][:c4]"
                      $bettingRates = $customer->betting_rates ?? [];
                      $jsonKey = null;
                      $commission = null;
                      $payout = null;
                      
                      if ($betKeyMap['type_code']) {
                          $keyParts = [$rKey, $betKeyMap['type_code']];
                          $meta = $betKeyMap['meta'];
                          if (isset($meta['digits']) && $meta['digits'] !== null) {
                              $keyParts[] = "d{$meta['digits']}";
                          }
                          if (isset($meta['xien_size']) && $meta['xien_size'] !== null) {
                              $keyParts[] = "x{$meta['xien_size']}";
                          }
                          if (isset($meta['dai_count']) && $meta['dai_count'] !== null) {
                              $keyParts[] = "c{$meta['dai_count']}";
                          }
                          $jsonKey = implode(':', $keyParts);
                          $commission = $bettingRates[$jsonKey]['buy_rate'] ?? null;
                          $payout = $bettingRates[$jsonKey]['payout'] ?? null;
                          
                          // Fallback to initialRates (resolved rates from controller) if JSON doesn't have value
                          if ($commission === null) {
                              $commission = $initialRates[$rKey][$betKey]['commission'] ?? null;
                          }
                          if ($payout === null) {
                              $payout = $initialRates[$rKey][$betKey]['payout_times'] ?? null;
                          }
                      }
                    @endphp
                    <div class="flex items-end gap-1.5 bg-gray-50 dark:bg-gray-900/50 rounded-lg p-2">
                      <div class="flex-1">
                        <label class="block text-xs text-gray-600 dark:text-gray-400 mb-0.5">{{ $label }}</label>
                        <input type="number" step="any" min="0"
                               name="rates[{{ $rKey }}][{{ $betKey }}][commission]"
                               value="{{ old("rates.$rKey.$betKey.commission", $commission) }}"
                               placeholder="Giá"
                               class="w-full input-sm">
                      </div>
                      <div class="flex-1">
                        <label class="block text-xs text-gray-600 mb-0.5">&nbsp;</label>
                        <input type="number" min="0"
                               name="rates[{{ $rKey }}][{{ $betKey }}][payout_times]"
                               value="{{ old("rates.$rKey.$betKey.payout_times", $payout) }}"
                               placeholder="Lần ăn"
                               class="w-full input-sm">
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
    </div>

    <!-- Submit -->
    <div class="pb-3">
      <button type="submit" class="btn btn-primary w-full">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Cập nhật khách hàng
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
        b.classList.remove('bg-primary','text-white','border-primary');
        b.classList.add('bg-white','dark:bg-gray-800','text-gray-700','dark:text-gray-300','border-gray-300','dark:border-gray-700');
      });
      btn.classList.add('bg-primary','text-white','border-primary');
      btn.classList.remove('bg-white','dark:bg-gray-800','text-gray-700','dark:text-gray-300','border-gray-300','dark:border-gray-700');
      panes.forEach(p => p.classList.toggle('hidden', p.dataset.tab !== tab));
    });
  });
});
</script>
@endsection
