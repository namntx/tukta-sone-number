@extends('layouts.app')

@section('title', 'Kết quả xổ số - Keki SaaS')

@section('content')
@php
  $regionLabels = ['nam'=>'Miền Nam','trung'=>'Miền Trung','bac'=>'Miền Bắc'];
  $orderMN_MT   = ['g8','g7','g6','g5','g4','g3','g2','g1','db'];
  $orderMB      = ['db','g1','g2','g3','g4','g5','g6','g7'];

  // Render badges số (mỗi số 1 dòng cho MN/MT, 2 số 1 dòng cho MB)
  $badges = function(array $nums, $isLoading = false, $prizeLabel = '', $region = '') use ($orderMN_MT, $orderMB): string {
    if ($isLoading) {
      $prizeCounts = ['g1' => 1, 'g2' => 1, 'g3' => 2, 'g4' => 6, 'g5' => 1, 'g6' => 3, 'g7' => 1, 'g8' => 1, 'db' => 1];
      $count = $prizeCounts[$prizeLabel] ?? 1;
      
      if ($region === 'bac') {
        $out = '<div class="space-y-1">';
        for ($i = 0; $i < $count; $i += 2) {
          $out .= '<div class="flex gap-1">';
          for ($j = 0; $j < 2 && ($i + $j) < $count; $j++) {
            $out .= '<div class="flex-1 inline-flex items-center justify-center px-2 py-1.5 rounded bg-gray-100 text-xs text-gray-400">...</div>';
          }
          $out .= '</div>';
        }
        $out .= '</div>';
        return $out;
      } else {
        $out = '<div class="space-y-1">';
        for ($i = 0; $i < $count; $i++) {
          $out .= '<div class="inline-flex items-center justify-center px-2 py-1.5 rounded bg-gray-100 text-xs text-gray-400 w-full">...</div>';
        }
        $out .= '</div>';
        return $out;
      }
    }
    
    if (!count($nums)) return '<span class="text-gray-400 text-xs">—</span>';
    
    if ($region === 'bac') {
      $out = '<div class="space-y-1">';
      for ($i = 0; $i < count($nums); $i += 2) {
        $out .= '<div class="flex gap-1">';
        for ($j = 0; $j < 2 && ($i + $j) < count($nums); $j++) {
          $out .= '<div class="flex-1 inline-flex items-center justify-center px-2 py-1 rounded bg-gradient-to-br from-blue-50 to-indigo-100 border border-blue-200 text-xs font-semibold text-blue-800">'.$nums[$i + $j].'</div>';
        }
        $out .= '</div>';
      }
      $out .= '</div>';
      return $out;
    } else {
      $out = '<div class="space-y-1">';
      foreach ($nums as $n) {
        $out .= '<div class="inline-flex items-center justify-center px-2 py-1 rounded bg-gradient-to-br from-blue-50 to-indigo-100 border border-blue-200 text-xs font-semibold text-blue-800 w-full">'.$n.'</div>';
      }
      $out .= '</div>';
      return $out;
    }
  };

  $renderCell = function(string $label, array $nums, $isLoading = false, $region = '') use ($badges): string {
    return $badges($nums, $isLoading, $label, $region);
  };
@endphp

<div class="pb-4">
  <!-- Sticky Header -->
  <div class="sticky top-0 z-10 bg-white shadow-sm border-b border-gray-200 mb-3">
    <div class="px-3 py-2.5">
      <div class="flex items-center justify-between mb-2">
        <h1 class="text-lg font-bold text-gray-900">Kết quả xổ số</h1>
        <div class="flex items-center gap-2">
          <button type="button" id="scrape-btn" onclick="scrapeResults()"
                  class="inline-flex items-center justify-center px-3 py-1.5 bg-purple-600 text-white text-xs font-medium rounded-lg hover:bg-purple-700 transition shadow-sm">
            <svg id="scrape-icon" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span id="scrape-text">Lấy kết quả</span>
          </button>
          <a href="{{ route('user.kqxs') }}" 
             class="inline-flex items-center justify-center px-2 py-1 text-xs text-gray-600 hover:bg-gray-100 rounded-lg transition">
            Hôm nay
          </a>
        </div>
      </div>
      
      <!-- Date Display -->
      <div class="flex items-center gap-2 text-xs text-gray-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
        <span>Ngày: {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span>
        <span class="text-gray-400">(Thay đổi ngày ở header)</span>
      </div>
    </div>
  </div>

  <!-- Results by Region - Mobile Stack -->
  <div class="space-y-3 px-3">
    @foreach(['nam','trung','bac'] as $reg)
      @php
        $rows = ($byRegion[$reg] ?? collect())->values();
        $dateText = \Carbon\Carbon::parse($date)->format('d/m/Y');
        $stations = $rows->map(fn($r) => [
          'name'   => $r->station,
          'code'   => $r->station_code,
          'prizes' => $r->prizes ?? [],
        ]);
        $colCount = max(1, $stations->count());
        $order    = $reg === 'bac' ? $orderMB : $orderMN_MT;
      @endphp

      <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <!-- Region Header -->
        <div class="px-3 py-2 border-b border-gray-200">
          <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full {{ $reg === 'nam' ? 'bg-green-500' : ($reg === 'trung' ? 'bg-yellow-500' : 'bg-red-500') }}"></div>
            <h2 class="text-sm font-bold text-gray-900">{{ $regionLabels[$reg] ?? strtoupper($reg) }}</h2>
            <span class="text-xs text-gray-500">{{ $dateText }}</span>
          </div>
        </div>

        <!-- Results Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200">
                <th class="w-12 px-2 py-2 text-center font-bold text-gray-700 border-r border-gray-200">Giải</th>
                @if($reg === 'bac')
                  <th class="px-2 py-2 text-left font-semibold text-gray-800">Miền Bắc</th>
                @else
                  @if($stations->isEmpty())
                    <th class="px-2 py-2 text-left font-semibold text-gray-800">Đài</th>
                  @else
                    @for($i=0; $i<$colCount; $i++)
                      <th class="px-2 py-2 text-left font-semibold text-gray-800 {{ $i < $colCount - 1 ? 'border-r border-gray-200' : '' }}">
                        <div class="flex items-center gap-1">
                          <div class="w-1.5 h-1.5 bg-blue-500 rounded-full"></div>
                          <span class="truncate">{{ $stations[$i]['name'] ?? '' }}</span>
                        </div>
                      </th>
                    @endfor
                  @endif
                @endif
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @if($stations->isEmpty())
              @foreach($order as $index => $label)
                <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                  <td class="w-12 px-2 py-2 text-center font-semibold text-gray-600 uppercase border-r border-gray-200 text-[10px]">
                    {{ strtoupper($label) }}
                  </td>
                  @if($reg === 'bac')
                    <td class="px-2 py-2">
                      {!! $renderCell($label, [], true, $reg) !!}
                    </td>
                  @else
                    @for($i=0; $i<$colCount; $i++)
                      <td class="px-2 py-2 {{ $i < $colCount - 1 ? 'border-r border-gray-200' : '' }}">
                        {!! $renderCell($label, [], true, $reg) !!}
                      </td>
                    @endfor
                  @endif
                </tr>
              @endforeach
            @else
                @foreach($order as $index => $label)
                  <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                    <td class="w-12 px-2 py-2 text-center font-semibold text-gray-600 uppercase border-r border-gray-200 text-[10px]">
                      {{ strtoupper($label) }}
                    </td>
                    @if($reg === 'bac')
                      <td class="px-2 py-2">
                        {!! $renderCell($label, $stations[0]['prizes'][$label] ?? [], false, $reg) !!}
                      </td>
                    @else
                      @for($i=0; $i<$colCount; $i++)
                        <td class="px-2 py-2 {{ $i < $colCount - 1 ? 'border-r border-gray-200' : '' }}">
                          {!! $renderCell($label, $stations[$i]['prizes'][$label] ?? [], false, $reg) !!}
                        </td>
                      @endfor
                    @endif
                  </tr>
                @endforeach
              @endif
            </tbody>
          </table>
        </div>
      </div>
    @endforeach
  </div>
</div>

<script>
async function scrapeResults() {
  const btn = document.getElementById('scrape-btn');
  const icon = document.getElementById('scrape-icon');
  const text = document.getElementById('scrape-text');
  
  // Disable button and show loading
  btn.disabled = true;
  btn.classList.add('opacity-50', 'cursor-not-allowed');
  text.textContent = 'Đang lấy...';
  icon.classList.add('animate-spin');
  
  try {
    const response = await fetch('{{ route("user.kqxs.scrape") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Show success message
      const message = `${data.message}\n` +
        `Miền Nam: ${data.results.nam} kết quả\n` +
        `Miền Trung: ${data.results.trung} kết quả\n` +
        `Miền Bắc: ${data.results.bac} kết quả\n` +
        `Tổng: ${data.total} kết quả`;
      alert(message);
      
      // Reload page to show new results
      window.location.reload();
    } else {
      // Show error message
      let errorMsg = data.message;
      if (data.errors) {
        errorMsg += '\n\nLỗi chi tiết:\n';
        Object.entries(data.errors).forEach(([region, error]) => {
          errorMsg += `${region}: ${error}\n`;
        });
      }
      alert(errorMsg);
      
      // Still reload if there are some results
      if (data.total > 0) {
        if (confirm('Đã lấy được một số kết quả. Bạn có muốn tải lại trang?')) {
          window.location.reload();
        }
      }
    }
  } catch (error) {
    alert('Lỗi khi lấy kết quả: ' + error.message);
  } finally {
    // Re-enable button
    btn.disabled = false;
    btn.classList.remove('opacity-50', 'cursor-not-allowed');
    text.textContent = 'Lấy kết quả';
    icon.classList.remove('animate-spin');
  }
}
</script>
@endsection
