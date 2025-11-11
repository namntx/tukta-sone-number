@extends('layouts.app')

@section('title', 'Báo cáo ngày - Keki SaaS')

@section('content')
<div class="pb-4">
    <!-- Sticky Header -->
    <div class="sticky top-0 z-10 bg-white shadow-sm border-b border-gray-200 mb-3">
        <div class="py-2.5">
            <div class="flex items-center justify-between mb-2 px-3">
                <div class="flex-1 min-w-0">
                    <h1 class="text-lg font-bold text-gray-900">Báo cáo nhà cái</h1>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ \App\Support\Region::label($region) }} · {{ \Carbon\Carbon::parse($reportDate)->format('d/m/Y') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('user.betting-tickets.index') }}" 
                       class="btn bg-gray-100 btn-icon">
                       <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="mb-3">
        <div class="grid grid-cols-2 gap-3">
            <!-- Tổng tiền xác thu -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <div class="text-xs text-blue-600 font-medium mb-1">Tiền xác thu</div>
                <div class="text-2xl font-bold text-blue-700">{{ number_format($totalXac / 1000, 1) }}k</div>
            </div>
            
            <!-- Tổng tiền thắng trả -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                <div class="text-xs text-red-600 font-medium mb-1">Tiền thắng trả</div>
                <div class="text-2xl font-bold text-red-700">{{ number_format($totalThang / 1000, 1) }}k</div>
            </div>
        </div>
    </div>

    <!-- Profit Card -->
    <div class="mb-3">
        <div class="bg-gradient-to-r {{ $userProfit >= 0 ? 'from-green-500 to-green-600' : 'from-red-500 to-red-600' }} rounded-xl p-4 text-white shadow-lg">
            <!-- <div class="text-xs font-medium opacity-90 mb-1">Lãi/Lỗ Nhà Cái</div> -->
            <div class="text-3xl font-bold mb-2">
                {{ $userProfit >= 0 ? '+' : '' }}{{ number_format($userProfit / 1000, 1) }}k
            </div>
            <div class="text-sm opacity-80">
                {{ $userProfit >= 0 ? 'Lời' : 'Lỗ' }} {{ number_format(abs($userProfit) / 1000, 1) }}k
            </div>
        </div>
    </div>

    <!-- Customer Report -->
    @if(count($customerReport) > 0)
    <div class="mb-3">
        <h3 class="text-sm font-semibold text-gray-700 mb-2 uppercase tracking-wide">Báo cáo theo khách hàng</h3>
        <div class="space-y-2">
            @foreach($customerReport as $report)
            <div class="bg-white rounded-lg border border-gray-200 hover:border-gray-300 transition p-3">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-900">{{ $report['customer']->name }}</h4>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $report['tickets']->count() }} phiếu</p>
                    </div>
                    <div class="flex items-center gap-4 ml-4">
                        <div class="text-right">
                            <div class="text-[10px] text-gray-500 mb-0.5">Tiền xác</div>
                            <div class="text-sm font-semibold text-blue-600">{{ number_format($report['total_xac'] / 1000, 1) }}k</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] text-gray-500 mb-0.5">Tiền thắng</div>
                            <div class="text-sm font-semibold text-green-600">{{ number_format($report['total_thang'] / 1000, 1) }}k</div>
                        </div>
                        <div class="text-right border-l border-gray-300 pl-4 min-w-[70px]">
                            <div class="text-[10px] font-semibold {{ $report['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }} mb-0.5">
                                {{ $report['profit'] >= 0 ? 'Lời' : 'Lỗ' }}
                            </div>
                            <div class="text-base font-bold {{ $report['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $report['profit'] >= 0 ? '+' : '' }}{{ number_format($report['profit'] / 1000, 1) }}k
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="px-3">
        <div class="bg-white rounded-lg border border-gray-200 p-8 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gray-100 mb-4">
                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 class="text-base font-medium text-gray-900 mb-1">Chưa có dữ liệu</h3>
            <p class="text-sm text-gray-500">Chưa có phiếu cược nào đã quyết toán cho ngày này</p>
        </div>
    </div>
    @endif
</div>
@endsection