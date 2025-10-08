@extends('layouts.app')

@section('title', 'Quản lý Plans - Keki SaaS')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Quản lý Plans
                </h1>
                <p class="text-gray-600 mt-1">
                    Quản lý các gói subscription
                </p>
            </div>
            <a href="{{ route('admin.plans.create') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Tạo gói mới
            </a>
        </div>
    </div>

    <!-- Plans Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($plans as $plan)
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ $plan->name }}
                    </h3>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $plan->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                        </span>
                        @if($plan->is_custom)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            Custom
                        </span>
                        @endif
                    </div>
                </div>
                
                <div class="text-3xl font-bold text-indigo-600 mb-2">
                    {{ $plan->formatted_price }}
                </div>
                <div class="text-sm text-gray-500 mb-4">
                    {{ $plan->formatted_duration }}
                </div>
                
                @if($plan->description)
                <p class="text-sm text-gray-600 mb-4">
                    {{ $plan->description }}
                </p>
                @endif
                
                @if($plan->features && count($plan->features) > 0)
                <ul class="text-sm text-gray-600 mb-6 space-y-1">
                    @foreach(array_slice($plan->features, 0, 3) as $feature)
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        {{ $feature }}
                    </li>
                    @endforeach
                    @if(count($plan->features) > 3)
                    <li class="text-gray-500">
                        +{{ count($plan->features) - 3 }} tính năng khác
                    </li>
                    @endif
                </ul>
                @endif
                
                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                    <span>{{ $plan->subscriptions_count ?? 0 }} subscriptions</span>
                    <span>Thứ tự: {{ $plan->sort_order }}</span>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex space-x-2">
                        <a href="{{ route('admin.plans.show', $plan) }}" 
                           class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                            Xem
                        </a>
                        <a href="{{ route('admin.plans.edit', $plan) }}" 
                           class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                            Sửa
                        </a>
                    </div>
                    <div class="flex items-center space-x-2">
                        <form method="POST" action="{{ route('admin.plans.toggle-status', $plan) }}" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="text-sm {{ $plan->is_active ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900' }}">
                                {{ $plan->is_active ? 'Vô hiệu hóa' : 'Kích hoạt' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="inline"
                              onsubmit="return confirm('Bạn có chắc muốn xóa gói này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900 text-sm">
                                Xóa
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    
    <!-- Pagination -->
    @if($plans->hasPages())
    <div class="bg-white shadow rounded-lg p-6">
        {{ $plans->links() }}
    </div>
    @endif
</div>
@endsection
