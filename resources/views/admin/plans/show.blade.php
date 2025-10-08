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
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.plans.edit', $plan) }}" 
                   class="btn-secondary">
                    Sửa
                </a>
                <a href="{{ route('admin.plans.index') }}" 
                   class="btn-secondary">
                    Quay lại
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Plan Info -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Thông tin gói
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Tên gói</label>
                            <p class="text-sm text-gray-900">{{ $plan->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Slug</label>
                            <p class="text-sm text-gray-900">{{ $plan->slug }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Giá</label>
                            <p class="text-lg font-semibold text-gray-900">{{ $plan->formatted_price }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Thời gian</label>
                            <p class="text-sm text-gray-900">{{ $plan->formatted_duration }}</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Số ngày</label>
                            <p class="text-sm text-gray-900">{{ $plan->duration_days }} ngày</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Trạng thái</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $plan->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Loại gói</label>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $plan->is_custom ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $plan->is_custom ? 'Custom' : 'Standard' }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Thứ tự</label>
                            <p class="text-sm text-gray-900">{{ $plan->sort_order }}</p>
                        </div>
                    </div>
                </div>
                
                @if($plan->description)
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-500 mb-2">Mô tả</label>
                    <p class="text-sm text-gray-900">{{ $plan->description }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Stats -->
        <div class="space-y-6">
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Thống kê
                </h2>
                
                <div class="space-y-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Tổng subscriptions</div>
                        <div class="text-2xl font-bold text-gray-900">{{ $plan->subscriptions_count }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Ngày tạo</div>
                        <div class="text-sm text-gray-900">{{ $plan->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Cập nhật cuối</div>
                        <div class="text-sm text-gray-900">{{ $plan->updated_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Thao tác
                </h2>
                
                <div class="space-y-3">
                    <a href="{{ route('admin.plans.edit', $plan) }}" 
                       class="w-full btn-primary text-center block">
                        Sửa gói
                    </a>
                    
                    <form method="POST" action="{{ route('admin.plans.toggle-status', $plan) }}">
                        @csrf
                        <button type="submit" 
                                class="w-full {{ $plan->is_active ? 'btn-danger' : 'btn-primary' }} text-center block">
                            {{ $plan->is_active ? 'Vô hiệu hóa' : 'Kích hoạt' }}
                        </button>
                    </form>
                    
                    <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}"
                          onsubmit="return confirm('Bạn có chắc muốn xóa gói này?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full btn-danger text-center block">
                            Xóa gói
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Features -->
    @if($plan->features && count($plan->features) > 0)
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            Tính năng
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($plan->features as $feature)
            <div class="flex items-start">
                <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-gray-700">{{ $feature }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
