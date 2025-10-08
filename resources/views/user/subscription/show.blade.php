@extends('layouts.app')

@section('title', 'Chi tiết gói - Keki SaaS')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ $plan->name }}
                </h1>
                <p class="text-gray-600 mt-1">
                    {{ $plan->description }}
                </p>
            </div>
            <a href="{{ route('user.subscription') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Quay lại
            </a>
        </div>
    </div>

    <!-- Plan Details -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Plan Info -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Thông tin gói
                </h2>
                
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Tên gói:</span>
                        <span class="font-medium text-gray-900">{{ $plan->name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Giá:</span>
                        <span class="font-medium text-gray-900">{{ $plan->formatted_price }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Thời gian:</span>
                        <span class="font-medium text-gray-900">{{ $plan->formatted_duration }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Số ngày:</span>
                        <span class="font-medium text-gray-900">{{ $plan->duration_days }} ngày</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Trạng thái:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $plan->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Features -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Tính năng
                </h2>
                
                @if($plan->features && count($plan->features) > 0)
                <ul class="space-y-2">
                    @foreach($plan->features as $feature)
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-700">{{ $feature }}</span>
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-gray-500">Không có tính năng đặc biệt</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Request Subscription -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="text-center">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">
                Đăng ký gói này
            </h2>
            <p class="text-gray-600 mb-6">
                Bạn có thể yêu cầu đăng ký gói này. Admin sẽ xem xét và kích hoạt cho bạn.
            </p>
            
            <form method="POST" action="{{ route('user.subscription.request', $plan) }}">
                @csrf
                <button type="submit" 
                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                    Yêu cầu đăng ký
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
