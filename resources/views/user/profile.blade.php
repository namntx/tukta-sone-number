@extends('layouts.app')

@section('title', 'Thông tin cá nhân - Keki SaaS')

@section('content')
<div class="pb-4">
  <!-- Sticky Header -->
  <div class="sticky top-0 z-10 bg-white shadow-sm border-b border-gray-200 mb-3">
    <div class="px-3 py-2.5">
      <div class="flex items-center justify-between">
        <h1 class="text-lg font-bold text-gray-900">Thông tin cá nhân</h1>
      </div>
    </div>
  </div>

  <!-- Profile Form -->
  <form method="POST" action="{{ route('user.profile.update') }}" class="px-3 space-y-4">
    @csrf
    @method('PUT')
    
    <!-- Basic Info Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-3">
      <h3 class="text-xs font-semibold text-gray-700 mb-3 uppercase tracking-wide">Thông tin tài khoản</h3>
      
      <div class="space-y-3">
        <div>
          <label for="name" class="block text-xs font-medium text-gray-600 mb-1">Họ và tên</label>
          <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}"
                 class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror">
          @error('name')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label for="email" class="block text-xs font-medium text-gray-600 mb-1">Email</label>
          <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}"
                 class="w-full text-sm rounded-lg border border-gray-300 bg-white px-3 py-2 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 @error('email') border-red-500 @enderror">
          @error('email')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
          @enderror
        </div>
      </div>
    </div>

    <!-- Account Info Card -->
    <div class="bg-white rounded-lg border border-gray-200 p-3">
      <h3 class="text-xs font-semibold text-gray-700 mb-3 uppercase tracking-wide">Thông tin tài khoản</h3>
      
      <div class="space-y-2.5 text-sm">
        <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
          <span class="text-gray-500">Vai trò</span>
          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $user->isAdmin() ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
            {{ $user->isAdmin() ? 'Admin' : 'User' }}
          </span>
        </div>
        
        <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
          <span class="text-gray-500">Trạng thái</span>
          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
            {{ $user->is_active ? 'Hoạt động' : 'Không hoạt động' }}
          </span>
        </div>
        
        <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
          <span class="text-gray-500">Ngày tạo</span>
          <span class="text-gray-900 font-medium">{{ $user->created_at->format('d/m/Y H:i') }}</span>
        </div>
        
        <div class="flex justify-between items-center py-1.5">
          <span class="text-gray-500">Đăng nhập cuối</span>
          <span class="text-gray-900 font-medium">{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Chưa có' }}</span>
        </div>
      </div>
    </div>

    <!-- Submit Button -->
    <div class="pb-3">
      <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition shadow-sm">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        Cập nhật thông tin
      </button>
    </div>
  </form>
</div>
@endsection
