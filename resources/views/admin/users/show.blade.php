@extends('layouts.app')

@section('title', 'Chi tiết User - Keki SaaS')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ $user->name }}
                </h1>
                <p class="text-gray-600 mt-1">
                    {{ $user->email }}
                </p>
            </div>
            <a href="{{ route('admin.users.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Quay lại
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Info -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Thông tin User
                </h2>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Họ tên</label>
                        <p class="text-sm text-gray-900">{{ $user->name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Email</label>
                        <p class="text-sm text-gray-900">{{ $user->email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Vai trò</label>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->isAdmin() ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ $user->isAdmin() ? 'Admin' : 'User' }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Trạng thái</label>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $user->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Ngày tạo</label>
                        <p class="text-sm text-gray-900">{{ $user->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Đăng nhập cuối</label>
                        <p class="text-sm text-gray-900">{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Chưa có' }}</p>
                    </div>
                </div>

                <!-- Toggle Status -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <form method="POST" action="{{ route('admin.users.status', $user) }}">
                        @csrf
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Trạng thái tài khoản</span>
                            <button type="submit" 
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 {{ $user->is_active ? 'bg-indigo-600' : 'bg-gray-200' }}">
                                <span class="sr-only">Toggle status</span>
                                <span class="translate-x-0 pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $user->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                        </div>
                        <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                    </form>
                </div>
            </div>
        </div>

        <!-- Subscription Management -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Current Subscription -->
            @if($activeSubscription)
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Subscription hiện tại
                    </h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Active
                    </span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Gói</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $activeSubscription->plan->name }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Hết hạn</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $activeSubscription->formatted_expiry_date }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Còn lại</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $activeSubscription->days_remaining }} ngày</div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap gap-3">
                    <button onclick="openExtendModal()" 
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Gia hạn
                    </button>
                    <form method="POST" action="{{ route('admin.users.cancel', $user) }}" class="inline">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                onclick="return confirm('Bạn có chắc muốn hủy subscription này?')">
                            Hủy subscription
                        </button>
                    </form>
                </div>
            </div>
            @endif

            <!-- Upgrade Subscription -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ $activeSubscription ? 'Upgrade Subscription' : 'Tạo Subscription' }}
                </h2>
                
                <form method="POST" action="{{ route('admin.users.upgrade', $user) }}">
                    @csrf
                    <div class="space-y-4">
                        <!-- Plan Selection -->
                        <div>
                            <label for="plan_id" class="block text-sm font-medium text-gray-700 mb-2">Chọn gói</label>
                            <select name="plan_id" id="plan_id" required 
                                    class="form-input"
                                    onchange="updatePlanInfo()">
                                <option value="">Chọn gói subscription</option>
                                @foreach($availablePlans as $plan)
                                <option value="{{ $plan->id }}" 
                                        data-name="{{ $plan->name }}"
                                        data-price="{{ $plan->price }}"
                                        data-duration="{{ $plan->duration_days }}"
                                        data-description="{{ $plan->description }}"
                                        {{ $activeSubscription && $activeSubscription->plan_id == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} - {{ $plan->formatted_price }} ({{ $plan->formatted_duration }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Plan Info Display -->
                        <div id="plan-info" class="hidden bg-gray-50 rounded-lg p-4 border border-gray-200">
                            <h3 class="text-sm font-medium text-gray-900 mb-2">Thông tin gói được chọn:</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Tên gói:</span>
                                    <span id="plan-name" class="font-medium text-gray-900 ml-1"></span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Giá:</span>
                                    <span id="plan-price" class="font-medium text-gray-900 ml-1"></span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Thời gian:</span>
                                    <span id="plan-duration" class="font-medium text-gray-900 ml-1"></span>
                                </div>
                            </div>
                            <div id="plan-description" class="mt-2 text-sm text-gray-600"></div>
                        </div>
                        
                        <!-- Notes -->
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Ghi chú (tùy chọn)</label>
                            <textarea name="notes" id="notes" rows="3"
                                      class="form-input"
                                      placeholder="Ghi chú về subscription này..."></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ $activeSubscription ? 'Upgrade Subscription' : 'Tạo Subscription' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Subscription History -->
            @if($subscriptionHistory->count() > 0)
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Lịch sử Subscription
                    </h2>
                </div>
                <div class="divide-y divide-gray-200">
                    @foreach($subscriptionHistory as $subscription)
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <h3 class="text-sm font-medium text-gray-900">
                                        {{ $subscription->plan->name }}
                                    </h3>
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($subscription->status === 'active') bg-green-100 text-green-800
                                        @elseif($subscription->status === 'expired') bg-red-100 text-red-800
                                        @elseif($subscription->status === 'cancelled') bg-gray-100 text-gray-800
                                        @else bg-yellow-100 text-yellow-800
                                        @endif">
                                        {{ ucfirst($subscription->status) }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500">
                                    {{ $subscription->formatted_amount_paid }} • {{ $subscription->formatted_expiry_date }}
                                </p>
                                @if($subscription->notes)
                                <p class="text-sm text-gray-500 mt-1">{{ $subscription->notes }}</p>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ $subscription->created_at->format('d/m/Y') }}
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Extend Modal -->
<div id="extendModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Gia hạn Subscription</h3>
            <form method="POST" action="{{ route('admin.users.extend', $user) }}">
                @csrf
                <div class="mb-4">
                    <label for="extend_plan_id" class="block text-sm font-medium text-gray-700 mb-2">Chọn gói gia hạn</label>
                    <select name="extend_plan_id" id="extend_plan_id" required 
                            class="form-input"
                            onchange="updateExtendPlanInfo()">
                        <option value="">Chọn gói để gia hạn</option>
                        @foreach($availablePlans as $plan)
                        <option value="{{ $plan->id }}" 
                                data-name="{{ $plan->name }}"
                                data-price="{{ $plan->price }}"
                                data-duration="{{ $plan->duration_days }}"
                                data-description="{{ $plan->description }}">
                            {{ $plan->name }} - {{ $plan->formatted_price }} ({{ $plan->formatted_duration }})
                        </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Extend Plan Info Display -->
                <div id="extend-plan-info" class="hidden mb-4 bg-gray-50 rounded-lg p-3 border border-gray-200">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Thông tin gói gia hạn:</h4>
                    <div class="text-sm space-y-1">
                        <div>
                            <span class="text-gray-500">Gói:</span>
                            <span id="extend-plan-name" class="font-medium text-gray-900 ml-1"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Thời gian thêm:</span>
                            <span id="extend-plan-duration" class="font-medium text-gray-900 ml-1"></span>
                        </div>
                        <div>
                            <span class="text-gray-500">Giá:</span>
                            <span id="extend-plan-price" class="font-medium text-gray-900 ml-1"></span>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="extend_notes" class="block text-sm font-medium text-gray-700 mb-2">Ghi chú (tùy chọn)</label>
                    <textarea name="notes" id="extend_notes" rows="3"
                              class="form-input"
                              placeholder="Ghi chú về việc gia hạn..."></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeExtendModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors duration-200">
                        Hủy
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors duration-200">
                        Gia hạn
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openExtendModal() {
    document.getElementById('extendModal').classList.remove('hidden');
}

function closeExtendModal() {
    document.getElementById('extendModal').classList.add('hidden');
    // Reset form when closing
    document.getElementById('extend_plan_id').value = '';
    document.getElementById('extend-plan-info').classList.add('hidden');
}

function updateExtendPlanInfo() {
    const select = document.getElementById('extend_plan_id');
    const planInfo = document.getElementById('extend-plan-info');
    const selectedOption = select.options[select.selectedIndex];
    
    if (select.value) {
        // Show plan info
        planInfo.classList.remove('hidden');
        
        // Update plan details
        document.getElementById('extend-plan-name').textContent = selectedOption.dataset.name;
        document.getElementById('extend-plan-price').textContent = formatPrice(selectedOption.dataset.price);
        document.getElementById('extend-plan-duration').textContent = formatDuration(selectedOption.dataset.duration);
    } else {
        // Hide plan info
        planInfo.classList.add('hidden');
    }
}

function updatePlanInfo() {
    const select = document.getElementById('plan_id');
    const planInfo = document.getElementById('plan-info');
    const selectedOption = select.options[select.selectedIndex];
    
    if (select.value) {
        // Show plan info
        planInfo.classList.remove('hidden');
        
        // Update plan details
        document.getElementById('plan-name').textContent = selectedOption.dataset.name;
        document.getElementById('plan-price').textContent = formatPrice(selectedOption.dataset.price);
        document.getElementById('plan-duration').textContent = formatDuration(selectedOption.dataset.duration);
        
        const description = selectedOption.dataset.description;
        const descriptionElement = document.getElementById('plan-description');
        if (description) {
            descriptionElement.textContent = description;
            descriptionElement.classList.remove('hidden');
        } else {
            descriptionElement.classList.add('hidden');
        }
    } else {
        // Hide plan info
        planInfo.classList.add('hidden');
    }
}

function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(price);
}

function formatDuration(days) {
    const daysInt = parseInt(days);
    if (daysInt >= 365) {
        const years = Math.floor(daysInt / 365);
        return years + ' năm';
    } else if (daysInt >= 30) {
        const months = Math.floor(daysInt / 30);
        return months + ' tháng';
    } else {
        return daysInt + ' ngày';
    }
}

// Initialize plan info on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePlanInfo();
});
</script>
@endsection
