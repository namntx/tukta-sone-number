@php
  // helper tìm giá hiện có
  if (!function_exists('findRate')) {
    function findRate($arr, $code, $meta=[]) {
      foreach ($arr as $r) {
        if ($r['type_code']!==$code) continue;
        foreach (['digits','xien_size','dai_count'] as $k) {
          $want = $meta[$k] ?? null;
          if (($r[$k] ?? null) !== $want) continue 2;
        }
        return $r;
      }
      return null;
    }
  }
  
  $meta = ['digits'=>$digits??null,'xien_size'=>$xien??null,'dai_count'=>$dai??null];
  $r = findRate($data ?? [], $code, $meta) ?? ['buy_rate'=>null,'payout'=>null,'id'=>null,'is_default'=>false];
@endphp
<div class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-center">
    <div class="sm:col-span-2 text-gray-700">{{ $text }}</div>
    <div class="sm:col-span-1">
        <input type="number" step="0.01" name="items[][buy]" value="{{ old('buy', $r['buy_rate']) }}"
            class="w-full border rounded px-2 py-1" placeholder="Giá">
    </div>
    <div class="sm:col-span-1">
        <input type="number" step="0.01" name="items[][payout]" value="{{ old('payout', $r['payout']) }}"
            class="w-full border rounded px-2 py-1" placeholder="Lần ăn">
    </div>
    <div class="sm:col-span-1 text-right text-xs text-gray-500">
        @if($r['is_default']) <span class="px-2 py-1 bg-gray-100 rounded">default</span> @endif
    </div>

    {{-- hidden fields --}}
    <input type="hidden" name="items[][region]" value="{{ $region }}">
    <input type="hidden" name="items[][type]"   value="{{ $code }}">
    <input type="hidden" name="items[][digits]" value="{{ $digits ?? '' }}">
    <input type="hidden" name="items[][xien]"   value="{{ $xien ?? '' }}">
    <input type="hidden" name="items[][dai]"    value="{{ $dai ?? '' }}">
</div>

