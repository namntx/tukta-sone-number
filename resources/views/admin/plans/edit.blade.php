@extends('layouts.app')

@section('title', 'Sửa gói - Keki SaaS')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Sửa gói: {{ $plan->name }}
                </h1>
                <p class="text-gray-600 mt-1">
                    Cập nhật thông tin gói subscription
                </p>
                <div class="flex items-center space-x-4 mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $plan->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $plan->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                    </span>
                    @if($plan->is_custom)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        Custom
                    </span>
                    @endif
                    <span class="text-sm text-gray-500">
                        {{ $plan->subscriptions_count ?? 0 }} subscriptions
                    </span>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.plans.show', $plan) }}" 
                   class="btn-secondary">
                    Xem chi tiết
                </a>
                <a href="{{ route('admin.plans.index') }}" 
                   class="btn-secondary">
                    Quay lại
                </a>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('admin.plans.update', $plan) }}">
            @csrf
            @method('PUT')
            
            <div class="space-y-6">
                <!-- Name -->
                <div class="form-group">
                    <label for="name" class="form-label">Tên gói</label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="{{ old('name', $plan->name) }}"
                           required
                           class="form-input @error('name') error @enderror"
                           placeholder="Ví dụ: Gói 1 tháng">
                    @error('name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Slug -->
                <div class="form-group">
                    <label for="slug" class="form-label">Slug (URL-friendly)</label>
                    <input type="text" 
                           name="slug" 
                           id="slug" 
                           value="{{ old('slug', $plan->slug) }}"
                           required
                           class="form-input @error('slug') error @enderror"
                           placeholder="vi-du-1-thang">
                    @error('slug')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label for="description" class="form-label">Mô tả</label>
                    <textarea name="description" 
                              id="description" 
                              rows="3"
                              class="form-input @error('description') error @enderror"
                              placeholder="Mô tả chi tiết về gói subscription">{{ old('description', $plan->description) }}</textarea>
                    @error('description')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Price and Duration -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label for="price" class="form-label">Giá (VNĐ)</label>
                        <input type="number" 
                               name="price" 
                               id="price" 
                               value="{{ old('price', $plan->price) }}"
                               required
                               min="0"
                               step="1000"
                               class="form-input @error('price') error @enderror"
                               placeholder="100000">
                        @error('price')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="duration_days" class="form-label">Số ngày</label>
                        <input type="number" 
                               name="duration_days" 
                               id="duration_days" 
                               value="{{ old('duration_days', $plan->duration_days) }}"
                               required
                               min="1"
                               class="form-input @error('duration_days') error @enderror"
                               placeholder="30">
                        @error('duration_days')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Sort Order -->
                <div class="form-group">
                    <label for="sort_order" class="form-label">Thứ tự hiển thị</label>
                    <input type="number" 
                           name="sort_order" 
                           id="sort_order" 
                           value="{{ old('sort_order', $plan->sort_order) }}"
                           min="0"
                           class="form-input @error('sort_order') error @enderror"
                           placeholder="0">
                    @error('sort_order')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div class="form-group">
                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="is_active" 
                               id="is_active" 
                               value="1"
                               {{ old('is_active', $plan->is_active) ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">
                            Kích hoạt gói này
                        </label>
                    </div>
                </div>

                <!-- Custom Plan -->
                <div class="form-group">
                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="is_custom" 
                               id="is_custom" 
                               value="1"
                               {{ old('is_custom', $plan->is_custom) ? 'checked' : '' }}
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="is_custom" class="ml-2 block text-sm text-gray-900">
                            Gói tùy chỉnh (Custom)
                        </label>
                    </div>
                </div>

                <!-- Features -->
                <div class="form-group">
                    <label class="form-label">Tính năng (mỗi dòng một tính năng)</label>
                    <textarea name="features_text" 
                              id="features_text" 
                              rows="5"
                              class="form-input"
                              placeholder="Truy cập đầy đủ tính năng&#10;Hỗ trợ 24/7&#10;Báo cáo chi tiết&#10;Tích hợp API">{{ old('features_text', $plan->features ? implode("\n", $plan->features) : '') }}</textarea>
                    <p class="text-sm text-gray-500 mt-1">Mỗi dòng sẽ là một tính năng riêng biệt</p>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3 mt-8">
                <a href="{{ route('admin.plans.show', $plan) }}" 
                   class="btn-secondary">
                    Hủy
                </a>
                <button type="submit" class="btn-primary">
                    Cập nhật gói
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-generate slug from name
document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const slug = name
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // Remove diacritics
        .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
        .replace(/\s+/g, '-') // Replace spaces with hyphens
        .replace(/-+/g, '-') // Replace multiple hyphens with single
        .trim('-'); // Remove leading/trailing hyphens
    
    document.getElementById('slug').value = slug;
});

// Convert features text to JSON array
document.querySelector('form').addEventListener('submit', function(e) {
    const featuresText = document.getElementById('features_text').value;
    const features = featuresText
        .split('\n')
        .map(feature => feature.trim())
        .filter(feature => feature.length > 0);
    
    // Create hidden input for features
    const featuresInput = document.createElement('input');
    featuresInput.type = 'hidden';
    featuresInput.name = 'features';
    featuresInput.value = JSON.stringify(features);
    this.appendChild(featuresInput);
});
</script>
@endsection