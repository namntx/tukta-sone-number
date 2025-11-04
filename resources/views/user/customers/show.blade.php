@extends('layouts.app')

@section('title', 'Chi tiết khách hàng - Keki SaaS')

@section('content')
<div class="pb-4">
    <!-- Sticky Header -->
    <div class="sticky top-0 z-10 bg-white shadow-sm border-b border-gray-200 mb-3">
        <div class="px-3 py-2.5">
            <div class="flex items-center justify-between mb-2">
                <div class="flex-1 min-w-0">
                    <h1 class="text-lg font-bold text-gray-900 truncate">{{ $customer->name }}</h1>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $customer->phone }}</p>
                </div>
                <div class="flex-shrink-0 flex items-center gap-2 ml-2">
                    <a href="{{ route('user.customers.edit', $customer) }}" 
                       class="inline-flex items-center justify-center px-3 py-1.5 bg-purple-600 text-white text-xs font-medium rounded-lg hover:bg-purple-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </a>
                    <a href="{{ route('user.customers.index') }}" 
                       class="inline-flex items-center justify-center px-3 py-1.5 bg-purple-600 text-white text-xs font-medium rounded-lg hover:bg-purple-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                </div>
            </div>
            
            <!-- Status Badge -->
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $customer->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $customer->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                </span>
                <span class="text-xs text-gray-500">
                    Tạo: {{ $customer->created_at->format('d/m/Y') }}
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
