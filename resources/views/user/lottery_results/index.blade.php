@extends('layouts.app')

@section('content')
@php
  $regionLabels = ['nam'=>'Miền Nam','trung'=>'Miền Trung','bac'=>'Miền Bắc'];
  $orderMN_MT   = ['g8','g7','g6','g5','g4','g3','g2','g1','db'];
  $orderMB      = ['db','g1','g2','g3','g4','g5','g6','g7'];

  // Render badges số (mỗi số 1 dòng cho MN/MT, 2 số 1 dòng cho MB)
  $badges = function(array $nums, $isLoading = false, $prizeLabel = '', $region = '') use ($orderMN_MT, $orderMB): string {
    if ($isLoading) {
      // Hiển thị placeholder đẹp cho từng giải dựa trên label
      $prizeCounts = ['g1' => 1, 'g2' => 1, 'g3' => 2, 'g4' => 6, 'g5' => 1, 'g6' => 3, 'g7' => 1, 'g8' => 1, 'db' => 1];
      $count = $prizeCounts[$prizeLabel] ?? 1;
      
      if ($region === 'bac') {
        // Miền Bắc: 2 số 1 dòng
        $out = '<div class="space-y-1.5">';
        for ($i = 0; $i < $count; $i += 2) {
          $delay = $i * 150;
          $out .= '<div class="flex gap-1.5">';
          for ($j = 0; $j < 2 && ($i + $j) < $count; $j++) {
            $out .= '<div class="relative flex-1 inline-flex items-center justify-center px-3 py-2 rounded-lg bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 border border-blue-200/50 text-sm font-medium overflow-hidden group float-animation" style="animation-delay: '.($delay + $j * 100).'ms">';
            $out .= '<div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/40 to-transparent shimmer-effect"></div>';
            $out .= '<div class="flex items-center gap-2 relative z-10">';
            $out .= '<div class="flex space-x-1">';
            $out .= '<div class="w-2 h-2 bg-gradient-to-r from-blue-400 to-blue-500 rounded-full loading-dots" style="animation-delay: '.($delay + $j * 100 + 100).'ms"></div>';
            $out .= '<div class="w-2 h-2 bg-gradient-to-r from-indigo-400 to-indigo-500 rounded-full loading-dots" style="animation-delay: '.($delay + $j * 100 + 300).'ms"></div>';
            $out .= '<div class="w-2 h-2 bg-gradient-to-r from-purple-400 to-purple-500 rounded-full loading-dots" style="animation-delay: '.($delay + $j * 100 + 500).'ms"></div>';
            $out .= '</div>';
            $out .= '<span class="text-blue-600/80 text-xs font-medium loading-dots">Đang chờ kết quả...</span>';
            $out .= '</div>';
            $out .= '</div>';
          }
          $out .= '</div>';
        }
        $out .= '</div>';
        return $out;
      } else {
        // Miền Nam/Trung: mỗi số 1 dòng
        $out = '<div class="space-y-1.5">';
        for ($i = 0; $i < $count; $i++) {
          $delay = $i * 150;
          $out .= '<div class="relative inline-flex items-center justify-center px-3 py-2 rounded-lg bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 border border-blue-200/50 text-sm font-medium w-full overflow-hidden group float-animation" style="animation-delay: '.$delay.'ms">';
          $out .= '<div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/40 to-transparent shimmer-effect"></div>';
          $out .= '<div class="flex items-center gap-2 relative z-10">';
          $out .= '<div class="flex space-x-1">';
          $out .= '<div class="w-2 h-2 bg-gradient-to-r from-blue-400 to-blue-500 rounded-full loading-dots" style="animation-delay: '.($delay + 100).'ms"></div>';
          $out .= '<div class="w-2 h-2 bg-gradient-to-r from-indigo-400 to-indigo-500 rounded-full loading-dots" style="animation-delay: '.($delay + 300).'ms"></div>';
          $out .= '<div class="w-2 h-2 bg-gradient-to-r from-purple-400 to-purple-500 rounded-full loading-dots" style="animation-delay: '.($delay + 500).'ms"></div>';
          $out .= '</div>';
          $out .= '<span class="text-blue-600/80 text-xs font-medium loading-dots">Đang chờ kết quả...</span>';
          $out .= '</div>';
          $out .= '</div>';
        }
        $out .= '</div>';
        return $out;
      }
    }
    
    if (!count($nums)) return '<span class="text-gray-400 text-sm">—</span>';
    
    if ($region === 'bac') {
      // Miền Bắc: 2 số 1 dòng
      $out = '<div class="space-y-1">';
      for ($i = 0; $i < count($nums); $i += 2) {
        $out .= '<div class="flex gap-1.5">';
        for ($j = 0; $j < 2 && ($i + $j) < count($nums); $j++) {
          $out .= '<div class="flex-1 inline-flex items-center justify-center px-2.5 py-1 rounded-md bg-gradient-to-br from-blue-50 to-indigo-100 border border-blue-200 text-sm font-semibold text-blue-800 shadow-sm hover:shadow-md transition-shadow duration-200">'.$nums[$i + $j].'</div>';
        }
        $out .= '</div>';
      }
      $out .= '</div>';
      return $out;
    } else {
      // Miền Nam/Trung: mỗi số 1 dòng
      $out = '<div class="space-y-1">';
      foreach ($nums as $n) {
        $out .= '<div class="inline-flex items-center justify-center px-2.5 py-1 rounded-md bg-gradient-to-br from-blue-50 to-indigo-100 border border-blue-200 text-sm font-semibold text-blue-800 shadow-sm hover:shadow-md transition-shadow duration-200 w-full">'.$n.'</div>';
      }
      $out .= '</div>';
      return $out;
    }
  };

  // Với G4/G6/G3 nhiều giải con, vẫn dùng badge + wrap để không tràn ngang
  $renderCell = function(string $label, array $nums, $isLoading = false, $region = '') use ($badges): string {
    return $badges($nums, $isLoading, $label, $region);
  };
@endphp

<div class="space-y-6">

  {{-- CARD: Filter ngày --}}
  <div class="bg-white shadow-lg rounded-xl p-6 border border-gray-200 hover:shadow-xl transition-shadow duration-300">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-3 h-3 rounded-full bg-indigo-500"></div>
      <h2 class="text-lg font-bold text-gray-900">Lọc kết quả</h2>
    </div>
    <form method="get" class="flex flex-wrap items-end gap-3">
      <div class="min-w-[220px]">
        <label class="block text-xs font-medium text-gray-600 mb-1">Ngày</label>
        <input
          type="date"
          name="date"
          value="{{ $filters['date'] ?? '' }}"
          class="w-full rounded-md border border-gray-100 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
      </div>
      <div class="flex gap-2">
        <button
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Xem kết quả
        </button>
        <a
          href="{{ route('user.kqxs') }}"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-gray-900 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 border-gray-100"
        >
          Hôm nay
        </a>
      </div>
    </form>
  </div>

  {{-- 3 BẢNG: hiển thị theo hàng ở desktop, xếp cột ở mobile --}}
  <div class="flex flex-col lg:flex-row gap-3">
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

      <div class="flex-1 min-w-0 bg-white shadow-lg rounded-xl p-6 border border-gray-200 hover:shadow-xl transition-shadow duration-300">
        <div class="mb-6 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-3 h-3 rounded-full {{ $reg === 'nam' ? 'bg-green-500' : ($reg === 'trung' ? 'bg-yellow-500' : 'bg-red-500') }}"></div>
            <h2 class="text-lg font-bold text-gray-900">
              {{ $regionLabels[$reg] ?? strtoupper($reg) }}
            </h2>
            <span class="text-sm text-gray-500 font-medium">{{ $dateText }}</span>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full table-auto border-collapse">
            <thead>
              <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                <th class="w-16 px-3 py-3 text-center text-xs font-bold uppercase tracking-wider text-gray-700 border-r border-gray-100">Giải</th>
                @if($reg === 'bac')
                  <th class="px-3 py-3 text-left text-sm font-bold text-gray-800">Miền Bắc</th>
                @else
                  @if($stations->isEmpty())
                    <th class="px-3 py-3 text-left text-sm font-bold text-gray-800">Đài</th>
                  @else
                    @for($i=0; $i<$colCount; $i++)
                      <th class="px-3 py-3 text-left text-sm font-bold text-gray-800 {{ $i < $colCount - 1 ? 'border-r border-gray-100' : '' }}">
                        <div class="flex items-center gap-2">
                          <div class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"></div>
                          <span class="text-gray-800 capitalize">{{ $stations[$i]['name'] ?? '' }}</span>
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
                <tr class="hover:bg-gray-50 transition-colors duration-150 {{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-25' }}">
                  <td class="w-16 px-3 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wide border-r border-gray-100">
                    <div class="flex items-center justify-center gap-1">
                      <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full flex-shrink-0"></div>
                      <span class="whitespace-nowrap">{{ strtoupper($label) }}</span>
                    </div>
                  </td>
                  @if($reg === 'bac')
                    <td class="px-3 py-3">
                      {!! $renderCell($label, [], true, $reg) !!}
                    </td>
                  @else
                    @for($i=0; $i<$colCount; $i++)
                      <td class="px-3 py-3 {{ $i < $colCount - 1 ? 'border-r border-gray-100' : '' }}">
                        {!! $renderCell($label, [], true, $reg) !!}
                      </td>
                    @endfor
                  @endif
                </tr>
              @endforeach
            @else
                @foreach($order as $index => $label)
                  <tr class="hover:bg-gray-50 transition-colors duration-150 {{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-25' }}">
                    <td class="w-16 px-3 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wide border-r border-gray-100">
                      <div class="flex items-center justify-center gap-1">
                        <div class="w-1.5 h-1.5 bg-indigo-400 rounded-full flex-shrink-0"></div>
                        <span class="whitespace-nowrap">{{ strtoupper($label) }}</span>
                      </div>
                    </td>
                    @if($reg === 'bac')
                      <td class="px-3 py-3">
                        {!! $renderCell($label, $stations[0]['prizes'][$label] ?? [], false, $reg) !!}
                      </td>
                    @else
                      @for($i=0; $i<$colCount; $i++)
                        <td class="px-3 py-3 {{ $i < $colCount - 1 ? 'border-r border-gray-100' : '' }}">
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
@endsection
