@extends('layouts.app')

@section('content')
@php
  $orderMN_MT = ['g8','g7','g6','g5','g4','g3','g2','g1','db'];
  $orderMB    = ['db','g1','g2','g3','g4','g5','g6','g7'];
  $fmtNums = function($arr) {
    $arr = is_array($arr) ? $arr : [];
    if (!count($arr)) return '<span class="text-gray-400">—</span>';
    $html = '';
    foreach ($arr as $n) {
      $html .= '<span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-800">'.$n.'</span> ';
    }
    return $html;
  };
  $isMB = ($row->region === 'bac');
  $order = $isMB ? $orderMB : $orderMN_MT;
@endphp

<div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-6">
  <div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">
      {{ $row->station }} ({{ strtoupper($row->region) }}) — {{ $row->draw_date->format('d/m/Y') }}
    </h2>

    <div class="overflow-x-auto rounded-lg ring-1 ring-gray-200">
      <table class="min-w-full divide-y divide-gray-200">
        <tbody class="bg-white">
          @foreach($order as $label)
            <tr class="even:bg-gray-50 align-top">
              <td class="w-24 px-3 py-2 text-xs font-semibold text-gray-700 uppercase">{{ strtoupper($label) }}</td>
              <td class="px-3 py-2">{!! $fmtNums(($row->prizes[$label] ?? [])) !!}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

  </div>
</div>
@endsection
