@extends('layouts.app')

@section('title', 'Chi tiết khách hàng - Keki SaaS')

@section('content')
<div class="pb-4">
    <!-- Header -->
    <div class="sticky top-14 z-10 bg-gray-50 border-b border-gray-200 -mx-3 px-3 py-2 mb-3">
        <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
                <h1 class="text-base font-semibold text-gray-900 truncate">{{ $customer->name }}</h1>
                <p class="text-xs text-gray-500">
                    {{ \App\Support\Region::label($region) }} · {{ \Carbon\Carbon::parse($reportDate)->format('d/m/Y') }}
                </p>
            </div>
            <a href="{{ route('user.customers.index') }}" class="btn bg-gray-100 btn-sm btn-icon">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white border-b border-gray-200 -mx-3 px-3 py-3">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-2 font-semibold text-gray-700">Loại cược</th>
                        <th class="text-right py-2 font-semibold text-gray-700">Tiền cược</th>
                        <th class="text-right py-2 font-semibold text-gray-700">Cược ăn</th>
                        <th class="text-right py-2 font-semibold text-gray-700">Xác</th>
                        <th class="text-right py-2 font-semibold text-gray-700">Ăn/Thua</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($statsByType as $typeData)
                    <tr>
                        <td class="px-2 py-2 font-medium text-gray-900">{{ $typeData['label'] }}</td>
                        <td class="px-2 py-2 text-right text-xs whitespace-nowrap">
                            @php
                                $tienCuocInK = $typeData['tien_cuoc'] / 1000;
                                $tienCuocFormatted = $typeData['tien_cuoc'] % 1000 == 0 ? (int)$tienCuocInK : number_format($tienCuocInK, 1, '.', '');
                                echo $tienCuocFormatted;
                            @endphp
                        </td>
                        <td class="px-2 py-2 text-right text-xs whitespace-nowrap {{ $typeData['cuoc_an'] > 0 ? 'text-green-700 font-semibold' : 'text-gray-500' }}">
                            @php
                                $cuocAnInK = $typeData['cuoc_an'] / 1000;
                                $cuocAnFormatted = $typeData['cuoc_an'] % 1000 == 0 ? (int)$cuocAnInK : number_format($cuocAnInK, 1, '.', '');
                                echo $cuocAnFormatted;
                            @endphp
                        </td>
                        <td class="px-2 py-2 text-right text-xs text-blue-700 font-semibold whitespace-nowrap">{{ number_format($typeData['xac'] / 1000, 0) }}k</td>
                        <td class="px-2 py-2 text-right text-xs font-semibold whitespace-nowrap {{ $typeData['an_thua_color'] }}">
                            {{ $typeData['an_thua'] >= 0 ? '+' : '' }}{{ number_format($typeData['an_thua'] / 1000, 0) }}k
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary - Tổng -->
    <div class="bg-white border-b border-gray-200 -mx-3 px-3 py-3">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <tfoot>
                    <tr class="bg-gray-50 border-t-2 border-gray-300">
                        <td class="px-2 py-2 font-semibold text-gray-900 whitespace-nowrap">📊 Tổng:</td>
                        <td class="px-2 py-2 text-right font-semibold text-gray-900 whitespace-nowrap">
                            @php
                                $totalTienCuocInK = $totalTienCuoc / 1000;
                                echo $totalTienCuoc % 1000 == 0 ? (int)$totalTienCuocInK : number_format($totalTienCuocInK, 1, '.', '');
                            @endphp
                        </td>
                        <td class="px-2 py-2 text-right font-semibold whitespace-nowrap {{ $totalCuocAn > 0 ? 'text-green-700' : 'text-gray-900' }}">
                            @php
                                $totalCuocAnInK = $totalCuocAn / 1000;
                                echo $totalCuocAn % 1000 == 0 ? (int)$totalCuocAnInK : number_format($totalCuocAnInK, 1, '.', '');
                            @endphp
                        </td>
                        <td class="px-2 py-2 text-right font-semibold text-blue-700 whitespace-nowrap">{{ number_format($totalXac / 1000, 0) }}k</td>
                        <td class="px-2 py-2 text-right font-semibold whitespace-nowrap {{ $totalAnThua >= 0 ? 'text-green-700' : 'text-red-700' }}">
                            {{ $totalAnThua >= 0 ? '+' : '' }}{{ number_format($totalAnThua / 1000, 0) }}k
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Detail Text Area - Group by original_message -->
    <div class="bg-white border-b border-gray-200 -mx-3 px-3 py-2">
        <div class="text-xs text-gray-600 font-mono leading-relaxed">
            @php
                $index = 1;
                $totalMessages = $ticketsByMessage->count();
            @endphp
            @forelse($ticketsByMessage as $messageTickets)
                @php
                    $originalMessage = $messageTickets->first()->original_message ?? '';
                    $firstTicket = $messageTickets->first();
                    $isLast = $index === $totalMessages;
                @endphp
                <div class="flex items-start gap-2 pb-2 {{ !$isLast ? 'border-b border-gray-200 mb-2' : '' }}">
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-blue-500 text-white text-xs font-bold flex-shrink-0">{{ $index }}</span>
                    <span class="break-words flex-1">{{ $originalMessage ?: 'Chưa có tin nhắn' }}</span>
                    @if($firstTicket)
                    <a href="{{ route('user.betting-tickets.edit-message', $firstTicket) }}?return_to=customer" 
                       class="w-7 h-7 rounded-full bg-pink-100 flex items-center justify-center flex-shrink-0 hover:bg-pink-200:bg-pink-900/70 transition-colors">
                        <svg class="w-3.5 h-3.5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </a>
                    @endif
                </div>
                @php
                    $index++;
                @endphp
            @empty
                <div class="text-gray-400">Chưa có dữ liệu</div>
            @endforelse
        </div>
    </div>

    <div class="h-16"></div>
</div>
@endsection
