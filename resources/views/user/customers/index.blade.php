@extends('layouts.app')

@section('title', 'Quản lý khách hàng - Keki SaaS')

@section('content')
<div class="pb-4">
    <!-- Header -->
    <div class="sticky top-14 z-10 bg-gray-50 border-b border-gray-200 -mx-3 px-3 py-2 mb-3">
        <div class="flex items-center justify-between mb-2">
            <h1 class="text-base font-semibold text-gray-900">Khách hàng</h1>
            <div class="flex items-center gap-2">
                <!-- <a href="{{ route('user.backup-restore.index') }}" class="btn btn-secondary btn-sm">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Backup/Restore
                </a> -->
                <a href="{{ route('user.customers.create') }}" class="btn btn-primary btn-sm">
                    Thêm
                </a>
            </div>
        </div>

        <!-- Search -->
        <form method="GET" action="{{ route('user.customers.index') }}" class="flex gap-1.5">
            <input type="text" 
                   name="search" 
                   value="{{ request('search') }}"
                   placeholder="Tìm tên/SĐT..."
                   class="flex-1 input-sm">
            <button type="submit" class="btn btn-secondary btn-sm btn-icon">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </button>
        </form>
    </div>

    <!-- Customer List -->
    <div class="space-y-1.5">
        @if($customers->count() > 0)
            @foreach($customers as $customer)
            <div class="bg-white border border-gray-200 rounded-lg flex items-center">
                <a href="{{ route('user.customers.show', $customer) }}" class="flex-1 min-w-0 px-4 py-2.5 hover:bg-gray-50:bg-gray-700 transition-colors">
                    <div class="flex items-center gap-2 mb-0.5">
                        <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $customer->name }}</h3>
                        @if($customer->is_active)
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 flex-shrink-0"></span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        @if($customer->phone)
                        <span>{{ $customer->phone }}</span>
                        @endif
                        @php
                            $dailyWin = $customer->daily_win_for_date ?? 0;
                            $dailyLose = $customer->daily_lose_for_date ?? 0;
                            $dailyNetProfit = $dailyWin - $dailyLose;
                        @endphp
                        @if($dailyWin > 0 || $dailyLose > 0)
                        <span class="text-gray-300">•</span>
                        <span class="{{ $dailyNetProfit >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                            {{ $dailyNetProfit >= 0 ? '+' : '' }}{{ number_format($dailyNetProfit / 1000, 1) }}k
                        </span>
                        @endif
                    </div>
                </a>
                <div class="flex items-center gap-2 flex-shrink-0 px-4 py-2.5 border-l border-gray-200">
                    <button type="button"
                            onclick="event.stopPropagation(); event.preventDefault(); window.location.href='{{ route('user.customers.edit', $customer) }}'; return false;"
                            class="px-3 py-1.5 h-8 text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100:bg-blue-900/70 rounded-md transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Sửa
                    </button>
                    <button type="button"
                            onclick="event.stopPropagation(); event.preventDefault(); handleDelete('{{ $customer->id }}', '{{ addslashes($customer->name) }}', '{{ route('user.customers.destroy', $customer) }}'); return false;"
                            class="px-3 py-1.5 h-8 text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100:bg-red-900/70 rounded-md transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Xóa
                    </button>
                </div>
            </div>
            @endforeach
            
            <!-- Pagination -->
            @if($customers->hasPages())
            <div class="py-3 flex justify-center">
                {{ $customers->links() }}
            </div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-state-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="empty-state-title">Chưa có khách hàng</h3>
                <p class="empty-state-description mb-3">Bắt đầu bằng cách thêm khách hàng đầu tiên</p>
                <a href="{{ route('user.customers.create') }}" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Thêm khách hàng
                </a>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function handleDelete(customerId, customerName, deleteUrl) {
    if (confirm('⚠️ CẢNH BÁO: Bạn có chắc chắn muốn xóa khách hàng "' + customerName + '"?\n\nHành động này không thể hoàn tác. Tất cả dữ liệu liên quan đến khách hàng này sẽ bị xóa vĩnh viễn.')) {
        // Tạo form để submit DELETE request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = deleteUrl;
        
        // Thêm CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '{{ csrf_token() }}';
        form.appendChild(csrfInput);
        
        // Thêm method spoofing
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        // Submit form
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
@endsection
