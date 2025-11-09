<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#F2F2F7">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    
    <title>@yield('title', 'Keki SaaS')</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="/images/icon-192x192.png">
    
    <!-- iOS System Fonts - No external fonts needed -->
    
    <!-- Scripts -->
    @php
        try {
            $manifestPath = base_path('public/build/manifest.json');
            $useBuildAssets = false;
            $cssFile = null;
            $jsFile = null;
            
            if (@file_exists($manifestPath) && @is_readable($manifestPath)) {
                $manifestContent = @file_get_contents($manifestPath);
                if ($manifestContent !== false) {
                    $manifest = @json_decode($manifestContent, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($manifest)) {
                        $cssEntry = $manifest['resources/css/app.css'] ?? null;
                        $jsEntry = $manifest['resources/js/app.js'] ?? null;
                        
                        if ($cssEntry && isset($cssEntry['file']) && !empty($cssEntry['file'])) {
                            $cssFile = $cssEntry['file'];
                            $useBuildAssets = true;
                        }
                        if ($jsEntry && isset($jsEntry['file']) && !empty($jsEntry['file'])) {
                            $jsFile = $jsEntry['file'];
                            $useBuildAssets = true;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $useBuildAssets = false;
            $cssFile = null;
            $jsFile = null;
        }
    @endphp
    @if($useBuildAssets && ($cssFile || $jsFile))
        @if($cssFile)
            <link rel="stylesheet" href="{{ asset('build/' . $cssFile) }}">
        @endif
        @if($jsFile)
            <script type="module" src="{{ asset('build/' . $jsFile) }}"></script>
        @endif
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    
    <!-- Custom Scrollbar Styles -->
    <style>
        .scrollbar-thin {
            scrollbar-width: thin;
        }
        .scrollbar-thin::-webkit-scrollbar {
            height: 8px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Custom Loading Animations */
        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-2px);
            }
        }
        
        .shimmer-effect {
            animation: shimmer 2s infinite;
        }
        
        .float-animation {
            animation: float 2s ease-in-out infinite;
        }
        
        .loading-dots {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        /* Mobile Optimizations */
        @media (max-width: 768px) {
            body {
                -webkit-tap-highlight-color: transparent;
                touch-action: manipulation;
            }
            
            /* Smooth scrolling on mobile */
            html {
                -webkit-overflow-scrolling: touch;
            }
            
            /* Better touch targets */
            button, a, input, select, textarea {
                min-height: 44px;
                min-width: 44px;
            }
            
            /* Hide scrollbars on mobile for cleaner look */
            .hide-scrollbar::-webkit-scrollbar {
                display: none;
            }
            .hide-scrollbar {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
        }
        
        /* iOS Tab Bar Active State */
        .ios-tab-active {
            color: #007AFF;
        }
        
        .ios-tab-active svg {
            stroke-width: 2.5;
        }
        
        /* iOS Date/Region Selector */
        .ios-date-region-selector {
            @apply px-3 py-1.5 rounded-full text-xs font-medium;
            @apply bg-gray-100 text-gray-700;
            @apply border-0;
            -webkit-appearance: none;
            appearance: none;
        }
        
        /* iOS Flash Messages */
        .ios-alert {
            @apply rounded-2xl px-4 py-3 mb-4;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.1);
        }
        
        .ios-alert-success {
            @apply bg-green-50 text-green-900 border border-green-200;
        }
        
        .ios-alert-error {
            @apply bg-red-50 text-red-900 border border-red-200;
        }
        
        .ios-alert-warning {
            @apply bg-yellow-50 text-yellow-900 border border-yellow-200;
        }
        
        .ios-alert-info {
            @apply bg-blue-50 text-blue-900 border border-blue-200;
        }
    </style>
    
    @stack('styles')
</head>
<body class="h-full bg-gray-100 font-sans antialiased @auth @if(!auth()->user()->isAdmin()) has-bottom-nav @endif @endauth" style="font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'SF Pro Text', 'Helvetica Neue', Helvetica, Arial, sans-serif;">
    <div class="min-h-full pb-20 safe-area-bottom">
        <!-- iOS Navigation Bar -->
        <nav class="ios-blur border-b border-gray-200/30 sticky top-0 z-50 safe-area-top">
            <div class="max-w-full mx-auto px-4">
                <div class="flex justify-between items-center h-14">
                    <div class="flex items-center space-x-3">
                        <!-- Logo -->
                        <div class="flex-shrink-0">
                            <a href="{{ route('user.dashboard') }}" class="text-xl font-bold text-gray-900">
                                Keki
                            </a>
                        </div>
                        
                        <!-- Mobile Date/Region (Compact) -->
                        @auth
                        @if(!auth()->user()->isAdmin())
                            <!-- Global Date and Region Selectors -->
                            <div class="flex items-center gap-1.5 flex-1 min-w-0 ml-2">
                                <form method="POST" action="{{ route('global-filters.update') }}" class="flex items-center gap-1.5 flex-1 min-w-0" id="global-filters-form">
                                    @csrf
                                    <input type="date" 
                                            id="global_date" 
                                            name="global_date" 
                                            value="{{ $global_date }}"
                                            class="flex-1 text-xs border border-gray-300 rounded-lg px-2 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                            onchange="updateGlobalFilters()">
                                    <select id="global_region" 
                                            name="global_region" 
                                            class="text-xs border border-gray-300 rounded-lg px-2 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 flex-shrink-0"
                                            onchange="updateGlobalFilters()">
                                        <option value="bac" {{ $global_region == 'bac' ? 'selected' : '' }}>Bắc</option>
                                        <option value="trung" {{ $global_region == 'trung' ? 'selected' : '' }}>Trung</option>
                                        <option value="nam" {{ $global_region == 'nam' ? 'selected' : '' }}>Nam</option>
                                    </select>
                                </form>
                            </div>
                        @endif
                        @endauth
                    </div>
                        
                        <!-- Navigation Links -->
                        @auth
                        <div class="hidden md:ml-6 md:flex md:space-x-8">
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}" 
                                   class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors duration-200">
                                    Admin Dashboard
                                </a>
                                <a href="{{ route('admin.users.index') }}" 
                                   class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors duration-200">
                                    Quản lý User
                                </a>
                                <a href="{{ route('admin.plans.index') }}" 
                                   class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors duration-200">
                                    Quản lý Gói
                                </a>
                            @else
                                <a href="{{ route('user.dashboard') }}" 
                                   class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('user.dashboard') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium transition-colors duration-200">
                                    Dashboard
                                </a>
                                <a href="{{ route('user.customers.index') }}" 
                                   class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('user.customers*') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium transition-colors duration-200">
                                    Khách hàng
                                </a>
                                <a href="{{ route('user.betting-tickets.index') }}" 
                                   class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('user.betting-tickets*') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium transition-colors duration-200">
                                   Thống kê
                                </a>
                                <a href="{{ route('user.kqxs') }}" 
                                   class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('user.kqxs*') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium transition-colors duration-200">
                                    KQXS
                                </a>
                                <a href="{{ route('user.subscription') }}" 
                                   class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('user.subscription*') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} text-sm font-medium transition-colors duration-200">
                                    Subscription
                                </a>
                            @endif
                        </div>
                        @endauth                    
                    <!-- Right side -->
                    <div class="flex items-center space-x-1 md:space-x-2">
                        @auth
                            <!-- Subscription Timer (for users only) -->
                            @if(!auth()->user()->isAdmin())
                                @php
                                    $subscription = auth()->user()->activeSubscription;
                                    $daysRemaining = auth()->user()->getSubscriptionDaysRemaining();
                                    $status = auth()->user()->getSubscriptionStatus();
                                @endphp
                                
                                @if($subscription && $status === 'active')
                                    <!-- Days Remaining Badge -->
                                    <div class="hidden md:flex items-center space-x-2">
                                        <div class="subscription-timer flex items-center space-x-1 bg-gradient-to-r from-indigo-50 to-blue-50 px-3 py-1.5 rounded-full border border-indigo-200 shadow-sm">
                                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span class="text-sm font-semibold text-indigo-800">
                                                {{ $daysRemaining }} ngày
                                            </span>
                                        </div>
                                        
                                        @if($daysRemaining <= 7)
                                        <div class="pulse-warning flex items-center space-x-1 bg-gradient-to-r from-yellow-50 to-orange-50 px-3 py-1.5 rounded-full border border-yellow-200 shadow-sm">
                                            <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                            </svg>
                                            <span class="text-sm font-semibold text-yellow-800">
                                                Sắp hết hạn
                                            </span>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Mobile Timer (Compact) -->
                                    <div class="md:hidden">
                                        <div class="flex items-center space-x-1 bg-gradient-to-r from-indigo-50 to-blue-50 px-2 py-0.5 rounded-full border border-indigo-200">
                                            <span class="text-xs font-semibold text-indigo-800">
                                                {{ $daysRemaining }} ngày
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <!-- No Active Subscription -->
                                    <div class="hidden sm:flex items-center space-x-1 bg-gradient-to-r from-gray-50 to-gray-100 px-3 py-1.5 rounded-full border border-gray-200">
                                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                        <span class="text-sm font-semibold text-gray-700">
                                            Chưa có gói
                                        </span>
                                    </div>
                                @endif
                            @endif
                            
                            <!-- User Info -->
                            <div class="flex items-center space-x-1 md:space-x-2">
                                <!-- User name (hidden on mobile) -->
                                <span class="hidden lg:block text-sm font-medium text-gray-700">
                                    {{ auth()->user()->name }}
                                </span>
                                
                                <!-- Mobile Menu Button
                                <button type="button" class="md:hidden inline-flex items-center justify-center p-1.5 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none" onclick="toggleMobileMenu()">
                                    <span class="sr-only">Mở menu</span>
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>
                                 -->
                                <!-- Desktop Logout -->
                                <form method="POST" action="{{ route('logout') }}" class="hidden md:inline">
                                    @csrf
                                    <button type="submit" 
                                            class="text-sm text-gray-500 hover:text-gray-700 transition-colors duration-200">
                                        Đăng xuất
                                    </button>
                                </form>
                            </div>
                        @else
                            <a href="{{ route('login') }}" 
                               class="text-sm font-medium text-indigo-600 hover:text-indigo-500 transition-colors duration-200">
                                Đăng nhập
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
            
            <!-- Mobile menu -->
            @auth
            <div id="mobile-menu" class="md:hidden hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <!-- User Info in Mobile -->
                    <div class="px-4 py-3 border-b border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                    <span class="text-sm font-medium text-indigo-600">
                                        {{ substr(auth()->user()->name, 0, 1) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ auth()->user()->name }}
                                </p>
                                <p class="text-sm text-gray-500 truncate">
                                    {{ auth()->user()->email }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Mobile Subscription Status -->
                        @if(!auth()->user()->isAdmin())
                            @php
                                $subscription = auth()->user()->activeSubscription;
                                $daysRemaining = auth()->user()->getSubscriptionDaysRemaining();
                                $status = auth()->user()->getSubscriptionStatus();
                            @endphp
                            
                            <div class="mt-3">
                                @if($subscription && $status === 'active')
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <div class="flex items-center space-x-1 bg-gradient-to-r from-indigo-50 to-blue-50 px-2 py-1 rounded-full border border-indigo-200">
                                                <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="text-xs font-semibold text-indigo-800">
                                                    {{ $daysRemaining }} ngày còn lại
                                                </span>
                                            </div>
                                            
                                            @if($daysRemaining <= 7)
                                            <div class="flex items-center space-x-1 bg-gradient-to-r from-yellow-50 to-orange-50 px-2 py-1 rounded-full border border-yellow-200">
                                                <svg class="w-3 h-3 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                </svg>
                                                <span class="text-xs font-semibold text-yellow-800">
                                                    Sắp hết hạn
                                                </span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center space-x-1 bg-gradient-to-r from-gray-50 to-gray-100 px-2 py-1 rounded-full border border-gray-200 w-fit">
                                        <svg class="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                        <span class="text-xs font-semibold text-gray-700">
                                            Chưa có gói
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                    
                    <!-- Navigation Links -->
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" 
                           class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-colors duration-200">
                            Admin Dashboard
                        </a>
                        <a href="{{ route('admin.users.index') }}" 
                           class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-colors duration-200">
                            Quản lý User
                        </a>
                        <a href="{{ route('admin.plans.index') }}" 
                           class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition-colors duration-200">
                            Quản lý Gói
                        </a>
                    @else
                        <a href="{{ route('user.dashboard') }}" 
                           class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('user.dashboard') ? 'border-indigo-500 text-indigo-700 bg-indigo-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 hover:border-gray-300' }} text-base font-medium transition-colors duration-200">
                            Dashboard
                        </a>
                        <a href="{{ route('user.customers.index') }}" 
                           class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('user.customers*') ? 'border-indigo-500 text-indigo-700 bg-indigo-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 hover:border-gray-300' }} text-base font-medium transition-colors duration-200">
                            Khách hàng
                        </a>
                        <a href="{{ route('user.betting-tickets.index') }}" 
                           class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('user.betting-tickets*') ? 'border-indigo-500 text-indigo-700 bg-indigo-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 hover:border-gray-300' }} text-base font-medium transition-colors duration-200">
                            Thống kê
                        </a>
                        <a href="{{ route('user.subscription') }}" 
                           class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('user.subscription*') ? 'border-indigo-500 text-indigo-700 bg-indigo-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 hover:border-gray-300' }} text-base font-medium transition-colors duration-200">
                            Subscription
                        </a>
                        <a href="{{ route('user.profile') }}" 
                           class="block pl-3 pr-4 py-2 border-l-4 {{ request()->routeIs('user.profile') ? 'border-indigo-500 text-indigo-700 bg-indigo-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 hover:border-gray-300' }} text-base font-medium transition-colors duration-200">
                            Profile
                        </a>
                    @endif
                    
                    <!-- Mobile Logout -->
                    <div class="border-t border-gray-200 pt-3">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" 
                                    class="block w-full text-left pl-3 pr-4 py-2 text-base font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors duration-200">
                                Đăng xuất
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endauth
        </nav>
        
        <!-- Main content -->
        <main class="max-w-full mx-auto py-4 px-4 pb-24 ios-fade-in">
            <!-- Flash Messages -->
            <!-- @if(session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif -->
            
            
            <!-- Flash Messages -->
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md mx-4 mt-4" role="alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mx-4 mt-4" role="alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('warning'))
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-md mx-4 mt-4" role="alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">{{ session('warning') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('info'))
                <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-md mx-4 mt-4" role="alert">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium">{{ session('info') }}</p>
                        </div>
                    </div>
                </div>
            @endif
            
            @yield('content')
        </main>
        
        <!-- iOS Tab Bar -->
        @auth
        @if(!auth()->user()->isAdmin())
        <nav class="fixed bottom-0 left-0 right-0 ios-blur border-t border-gray-200/30 safe-area-bottom z-50">
            <div class="flex justify-around items-center h-16 px-2">
                <!-- Dashboard -->
                <a href="{{ route('user.dashboard') }}" 
                   class="flex flex-col items-center justify-center flex-1 py-1.5 {{ request()->routeIs('user.dashboard') ? 'text-blue-500' : 'text-gray-500' }} transition-colors duration-200">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('user.dashboard') ? '2.5' : '2' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="text-[10px] font-medium">Trang chủ</span>
                </a>
                
                <!-- Khách hàng -->
                <a href="{{ route('user.customers.index') }}" 
                   class="flex flex-col items-center justify-center flex-1 py-1.5 {{ request()->routeIs('user.customers*') ? 'text-blue-500' : 'text-gray-500' }} transition-colors duration-200">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('user.customers*') ? '2.5' : '2' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="text-[10px] font-medium">Khách hàng</span>
                </a>
                
                <!-- Phiếu cược -->
                <a href="{{ route('user.betting-tickets.index') }}" 
                   class="flex flex-col items-center justify-center flex-1 py-1.5 {{ request()->routeIs('user.betting-tickets*') ? 'text-blue-500' : 'text-gray-500' }} transition-colors duration-200">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('user.betting-tickets*') ? '2.5' : '2' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="text-[10px] font-medium">Thống kê</span>
                </a>
                
                <!-- KQXS -->
                <a href="{{ route('user.kqxs') }}" 
                   class="flex flex-col items-center justify-center flex-1 py-1.5 {{ request()->routeIs('user.kqxs*') ? 'text-blue-500' : 'text-gray-500' }} transition-colors duration-200">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('user.kqxs*') ? '2.5' : '2' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="text-[10px] font-medium">KQXS</span>
                </a>
                
                <!-- More/Menu -->
                <!-- <button type="button" onclick="toggleMobileMenu()" 
                        class="bottom-nav-item flex flex-col items-center justify-center flex-1 py-2 text-gray-600">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <span class="text-xs font-medium">Menu</span>
                </button> -->
            </div>
        </nav>
        @endif
        @endauth
    </div>
    
    @stack('scripts')
    
    <!-- PWA Installation Script -->
    <script>
        // Register service worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => console.log('SW registered:', registration))
                    .catch(err => console.log('SW registration failed:', err));
            });
        }
        
        // PWA Install prompt
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            // Show install button if you have one
        });
    </script>
    
    <script>
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobile-menu');
            if (mobileMenu) {
                mobileMenu.classList.toggle('hidden');
            }
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const mobileMenu = document.getElementById('mobile-menu');
            const menuButton = event.target.closest('[onclick="toggleMobileMenu()"]');
            
            if (mobileMenu && !mobileMenu.contains(event.target) && !menuButton) {
                mobileMenu.classList.add('hidden');
            }
        });
        
        // Close mobile menu on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const mobileMenu = document.getElementById('mobile-menu');
                if (mobileMenu) {
                    mobileMenu.classList.add('hidden');
                }
            }
        });
        
        // Global filters update function
        function updateGlobalFilters() {
            const form = document.getElementById('global-filters-form');
            if (form) {
                // Create a hidden form to submit
                const hiddenForm = document.createElement('form');
                hiddenForm.method = 'POST';
                hiddenForm.action = '{{ route("global-filters.update") }}';
                hiddenForm.style.display = 'none';
                
                // Add CSRF token
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                hiddenForm.appendChild(csrfToken);
                
                // Add global date
                const globalDate = document.getElementById('global_date');
                if (globalDate) {
                    const dateInput = document.createElement('input');
                    dateInput.type = 'hidden';
                    dateInput.name = 'global_date';
                    dateInput.value = globalDate.value;
                    hiddenForm.appendChild(dateInput);
                }
                
                // Add global region
                const globalRegion = document.getElementById('global_region');
                if (globalRegion) {
                    const regionInput = document.createElement('input');
                    regionInput.type = 'hidden';
                    regionInput.name = 'global_region';
                    regionInput.value = globalRegion.value;
                    hiddenForm.appendChild(regionInput);
                }
                
                // Submit form
                document.body.appendChild(hiddenForm);
                hiddenForm.submit();
            }
        }
    </script>
</body>
</html>
