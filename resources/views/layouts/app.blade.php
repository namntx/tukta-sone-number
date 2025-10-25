<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Keki SaaS')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
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
    </style>
    
    @stack('styles')
</head>
<body class="h-full bg-gray-50 font-sans antialiased">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <!-- Logo -->
                        <div class="flex-shrink-0">
                            <a href="{{ route('user.dashboard') }}" class="text-2xl font-bold text-indigo-600">
                                Keki
                            </a>
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
                                    Phiếu cược
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
                    </div>
                    
                    <!-- Right side -->
                    <div class="flex items-center space-x-2">
                        @auth
                            <!-- Global Filters (for users only) -->
                            @if(!auth()->user()->isAdmin())
                                <!-- Global Date and Region Selectors -->
                                <div class="hidden lg:flex items-center space-x-3 mr-4">
                                    <form method="POST" action="{{ route('global-filters.update') }}" class="flex items-center space-x-2" id="global-filters-form">
                                        @csrf
                                        <div class="flex items-center space-x-1">
                                            <label for="global_date" class="text-xs font-medium text-gray-600">Ngày:</label>
                                            <input type="date" 
                                                   id="global_date" 
                                                   name="global_date" 
                                                   value="{{ $global_date }}"
                                                   class="text-xs border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                                   onchange="updateGlobalFilters()">
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <label for="global_region" class="text-xs font-medium text-gray-600">Miền:</label>
                                            <select id="global_region" 
                                                    name="global_region" 
                                                    class="text-xs border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                                    onchange="updateGlobalFilters()">
                                                <option value="Bắc" {{ $global_region == 'Bắc' ? 'selected' : '' }}>Bắc</option>
                                                <option value="Trung" {{ $global_region == 'Trung' ? 'selected' : '' }}>Trung</option>
                                                <option value="Nam" {{ $global_region == 'Nam' ? 'selected' : '' }}>Nam</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                            @endif
                            
                            <!-- Subscription Timer (for users only) -->
                            @if(!auth()->user()->isAdmin())
                                @php
                                    $subscription = auth()->user()->activeSubscription;
                                    $daysRemaining = auth()->user()->getSubscriptionDaysRemaining();
                                    $status = auth()->user()->getSubscriptionStatus();
                                @endphp
                                
                                @if($subscription && $status === 'active')
                                    <!-- Days Remaining Badge -->
                                    <div class="hidden sm:flex items-center space-x-2">
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
                                    
                                    <!-- Mobile Timer -->
                                    <div class="sm:hidden">
                                        <div class="flex items-center space-x-1 bg-gradient-to-r from-indigo-50 to-blue-50 px-2 py-1 rounded-full border border-indigo-200">
                                            <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
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
                            <div class="flex items-center space-x-2">
                                <!-- User name (hidden on mobile) -->
                                <span class="hidden sm:block text-sm font-medium text-gray-700">
                                    {{ auth()->user()->name }}
                                </span>
                                
                                <!-- Mobile Menu Button -->
                                <button type="button" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" onclick="toggleMobileMenu()">
                                    <span class="sr-only">Mở menu</span>
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>
                                
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
                            Phiếu cược
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
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Flash Messages -->
            @if(session('success'))
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
            @endif
            
            
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
    </div>
    
    @stack('scripts')
    
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
