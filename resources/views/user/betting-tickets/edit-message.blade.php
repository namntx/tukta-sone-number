@extends('layouts.app')

@section('title', 'Sửa tin nhắn cược - Keki SaaS')

@section('content')
<div class="pb-4">
  <!-- Header -->
  <div class="sticky top-14 z-10 bg-gray-50 border-b border-gray-200 -mx-3 px-3 py-2 mb-3">
    <div class="flex items-center justify-between">
      <div class="flex-1 min-w-0">
        <h1 class="text-base font-semibold text-gray-900 truncate">Sửa tin nhắn cược</h1>
        <p class="text-xs text-gray-500">
          {{ $bettingTicket->customer->name }} · {{ $ticketsWithSameMessage }} phiếu cược
        </p>
      </div>
      <a href="{{ request()->has('return_to') && request()->return_to === 'customer' ? route('user.customers.show', $bettingTicket->customer_id) : route('user.betting-tickets.index') }}" class="btn btn-secondary btn-sm btn-icon">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
      </a>
    </div>
  </div>

  <!-- Success -->
  @if (session('status'))
  <div class="alert alert-success mb-3">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
    </svg>
    <p class="text-sm">{{ session('status') }}</p>
  </div>
  @endif

  <!-- Errors -->
  @if ($errors->any())
  <div class="alert alert-error mb-3">
    <div>
      <div class="text-sm font-medium mb-1">Vui lòng kiểm tra lại:</div>
      <ul class="text-xs space-y-0.5">
        @foreach ($errors->all() as $error)
          <li>• {{ $error }}</li>
        @endforeach
      </ul>
    </div>
  </div>
  @endif

  <form method="POST" action="{{ route('user.betting-tickets.update-message', $bettingTicket) }}" class="space-y-3">
    @csrf
    @method('PUT')

    <!-- Hidden fields -->
    <input type="hidden" name="betting_date" value="{{ $bettingTicket->betting_date }}">
    <input type="hidden" name="region" value="{{ $bettingTicket->region }}">
    @if(request()->has('return_to'))
    <input type="hidden" name="return_to" value="{{ request()->return_to }}">
    @endif

    <!-- Message Input -->
    <div class="card">
      <div class="card-body">
        <label for="original_message" class="block text-sm font-semibold text-gray-900 mb-3">
          Tin nhắn cược <span class="text-red-500">*</span>
        </label>
        <textarea id="original_message" name="original_message" rows="6"
                  class="w-full text-base"
                  placeholder="Ví dụ: lo 12 34 56 100000&#10;bao 01 02 03 50000 2d&#10;da 12 34 200000 hcm"
                  required>{{ old('original_message', $originalMessage) }}</textarea>
        @error('original_message')
          <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
        @enderror
        <p class="mt-3 text-xs text-gray-500">
          Hệ thống sẽ tự động phân tích và tạo lại các phiếu cược từ tin nhắn mới. Tất cả phiếu cược cũ có cùng tin nhắn sẽ bị xóa.
        </p>
      </div>
    </div>

    <!-- Info Card -->
    <div class="card bg-yellow-50 border-yellow-200">
      <div class="card-body">
        <div class="flex items-start gap-2">
          <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
          <div class="text-sm text-yellow-800">
            <p class="font-semibold mb-1">Lưu ý:</p>
            <ul class="list-disc list-inside space-y-1 text-xs">
              <li>Tất cả {{ $ticketsWithSameMessage }} phiếu cược có cùng tin nhắn này sẽ bị xóa</li>
              <li>Hệ thống sẽ phân tích tin nhắn mới và tạo lại các phiếu cược</li>
              <li>Nếu phiếu cược đã được quyết toán (ăn/thua), thống kê khách hàng sẽ được cập nhật lại</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Submit Button -->
    <div class="flex gap-2">
      <a href="{{ request()->has('return_to') && request()->return_to === 'customer' ? route('user.customers.show', $bettingTicket->customer_id) : route('user.betting-tickets.index') }}" 
         class="btn btn-secondary flex-1">
        Hủy
      </a>
      <button type="submit" class="btn btn-primary flex-1">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Lưu thay đổi
      </button>
    </div>
  </form>
</div>
@endsection

