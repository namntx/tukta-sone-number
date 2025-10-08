@extends('layouts.app')

@section('title', 'Tính năng Premium - Keki SaaS')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Tính năng Premium
                </h1>
                <p class="text-gray-600 mt-1">
                    Đây là nội dung chỉ dành cho users có subscription active
                </p>
            </div>
            <div class="flex items-center">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Premium Content -->
    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-lg p-8">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-purple-100 mb-4">
                <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900 mb-4">
                Chúc mừng! Bạn đã truy cập được tính năng Premium
            </h2>
            <p class="text-lg text-gray-600 mb-6">
                Đây là nội dung đặc biệt chỉ dành cho những users có subscription active.
                Middleware đã kiểm tra và cho phép bạn truy cập trang này.
            </p>
            
            <div class="bg-white rounded-lg p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Thông tin subscription của bạn:
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-left">
                    <div>
                        <div class="text-sm text-gray-500">Gói hiện tại</div>
                        <div class="font-semibold text-gray-900">{{ auth()->user()->activeSubscription->plan->name ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Hết hạn</div>
                        <div class="font-semibold text-gray-900">{{ auth()->user()->activeSubscription->formatted_expiry_date ?? 'N/A' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Còn lại</div>
                        <div class="font-semibold text-gray-900">{{ auth()->user()->getSubscriptionDaysRemaining() }} ngày</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Features Demo -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                Tính năng 1: Báo cáo nâng cao
            </h3>
            <p class="text-gray-600 mb-4">
                Truy cập các báo cáo chi tiết và phân tích dữ liệu nâng cao.
            </p>
            <div class="bg-gray-100 rounded-lg p-4">
                <div class="text-sm text-gray-500">Demo Chart</div>
                <div class="h-32 bg-gradient-to-r from-blue-400 to-purple-500 rounded flex items-center justify-center text-white font-semibold">
                    📊 Biểu đồ dữ liệu
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                Tính năng 2: API Access
            </h3>
            <p class="text-gray-600 mb-4">
                Truy cập API để tích hợp với các hệ thống khác.
            </p>
            <div class="bg-gray-100 rounded-lg p-4">
                <div class="text-sm text-gray-500">API Endpoint</div>
                <code class="text-xs bg-gray-200 px-2 py-1 rounded">GET /api/premium/data</code>
            </div>
        </div>
    </div>

    <!-- Back to Dashboard -->
    <div class="text-center">
        <a href="{{ route('user.dashboard') }}" 
           class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Quay lại Dashboard
        </a>
    </div>
</div>
@endsection
