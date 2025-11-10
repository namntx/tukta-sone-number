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
    
    
    @stack('styles')
</head>
<body class="h-full font-sans antialiased @auth @if(!auth()->user()->isAdmin()) pb-16 md:pb-0 @endif @endauth">
    <div class="min-h-full safe-bottom">
        <!-- Navigation Bar -->
        <nav class="bg-white sticky top-0 z-50 safe-top border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-3">
                <div class="flex justify-between items-center h-14">
                    <div class="flex items-center space-x-2">
                        <!-- Logo -->
                        <div class="flex-shrink-0">
                            <a href="{{ route('user.dashboard') }}" class="text-lg font-bold text-primary">
                                Keki
                            </a>
                        </div>

                        <!-- Date/Region Filters -->
                        @auth
                        @if(!auth()->user()->isAdmin())
                            <div class="flex items-center gap-1.5 flex-1 min-w-0 ml-2">
                                <form method="POST" action="{{ route('global-filters.update') }}" class="flex items-center gap-1.5 flex-1 min-w-0" id="global-filters-form">
                                    @csrf
                                    <input type="date"
                                            id="global_date"
                                            name="global_date"
                                            value="{{ $global_date }}"
                                            class="input-sm flex-1 text-xs"
                                            onchange="updateGlobalFilters()">
                                    <select id="global_region"
                                            name="global_region"
                                            class="input-sm flex-shrink-0 text-xs"
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
                        
                        <!-- Navigation Links (Desktop only) -->
                        @auth
                        <div class="hidden md:ml-4 md:flex md:space-x-4">
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.dashboard') }}"
                                   class="inline-flex items-center px-1 pt-1 text-xs font-medium text-gray-600 hover:text-gray-900">
                                    Admin
                                </a>
                                <a href="{{ route('admin.users.index') }}"
                                   class="inline-flex items-center px-1 pt-1 text-xs font-medium text-gray-600 hover:text-gray-900">
                                    Users
                                </a>
                                <a href="{{ route('admin.plans.index') }}"
                                   class="inline-flex items-center px-1 pt-1 text-xs font-medium text-gray-600 hover:text-gray-900">
                                    Plans
                                </a>
                            @else
                                <a href="{{ route('user.dashboard') }}"
                                   class="inline-flex items-center px-1 pt-1 text-xs font-medium {{ request()->routeIs('user.dashboard') ? 'text-primary' : 'text-gray-600 hover:text-gray-900' }}">
                                    Trang chủ
                                </a>
                                <a href="{{ route('user.customers.index') }}"
                                   class="inline-flex items-center px-1 pt-1 text-xs font-medium {{ request()->routeIs('user.customers*') ? 'text-primary' : 'text-gray-600 hover:text-gray-900' }}">
                                    Khách hàng
                                </a>
                                <a href="{{ route('user.betting-tickets.index') }}"
                                   class="inline-flex items-center px-1 pt-1 text-xs font-medium {{ request()->routeIs('user.betting-tickets*') ? 'text-primary' : 'text-gray-600 hover:text-gray-900' }}">
                                    Thống kê
                                </a>
                                <a href="{{ route('user.kqxs') }}"
                                   class="inline-flex items-center px-1 pt-1 text-xs font-medium {{ request()->routeIs('user.kqxs*') ? 'text-primary' : 'text-gray-600 hover:text-gray-900' }}">
                                    KQXS
                                </a>
                            @endif
                        </div>
                        @endauth                    
                    <!-- Right side -->
                    <div class="flex items-center space-x-1.5">
                        @auth
                            <!-- Subscription Badge -->
                            @if(!auth()->user()->isAdmin())
                                @php
                                    $subscription = auth()->user()->activeSubscription;
                                    $daysRemaining = auth()->user()->getSubscriptionDaysRemaining();
                                    $status = auth()->user()->getSubscriptionStatus();
                                @endphp

                                @if($subscription && $status === 'active')
                                    <div class="badge {{ $daysRemaining <= 7 ? 'badge-warning' : 'badge-primary' }} text-[10px]">
                                        {{ $daysRemaining }}d
                                    </div>
                                @endif
                            @endif

                            <!-- Logout -->
                            <form method="POST" action="{{ route('logout') }}" class="hidden md:inline">
                                @csrf
                                <button type="submit" class="text-xs text-gray-600 hover:text-gray-900">
                                    Thoát
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="text-xs font-medium text-primary hover:text-primary-dark">
                                Đăng nhập
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main content -->
        <main class="max-w-7xl mx-auto py-4 px-3 animate-fade-in">
            <!-- Flash Messages -->
            @if(session('success'))
                <div class="alert alert-success mb-4" role="alert">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <p class="text-sm">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error mb-4" role="alert">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <p class="text-sm">{{ session('error') }}</p>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning mb-4" role="alert">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"></path>
                    </svg>
                    <p class="text-sm">{{ session('warning') }}</p>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info mb-4" role="alert">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01"></path>
                    </svg>
                    <p class="text-sm">{{ session('info') }}</p>
                </div>
            @endif
            
            @yield('content')
        </main>
        
        <!-- Bottom Navigation (Mobile/Tablet Only) -->
        @auth
        @if(!auth()->user()->isAdmin())
        <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white safe-bottom z-50 border-t border-gray-200 no-print">
            <div class="flex justify-around items-center h-16 px-1">
                <!-- Dashboard -->
                <a href="{{ route('user.dashboard') }}"
                   class="flex flex-col items-center justify-center flex-1 gap-0.5 py-2 {{ request()->routeIs('user.dashboard') ? 'text-primary' : 'text-gray-500' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('user.dashboard') ? '2' : '1.5' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="text-[10px] font-medium">Trang chủ</span>
                </a>

                <!-- Customers -->
                <a href="{{ route('user.customers.index') }}"
                   class="flex flex-col items-center justify-center flex-1 gap-0.5 py-2 {{ request()->routeIs('user.customers*') ? 'text-primary' : 'text-gray-500' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('user.customers*') ? '2' : '1.5' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="text-[10px] font-medium">Khách hàng</span>
                </a>

                <!-- Betting Tickets -->
                <a href="{{ route('user.betting-tickets.index') }}"
                   class="flex flex-col items-center justify-center flex-1 gap-0.5 py-2 {{ request()->routeIs('user.betting-tickets*') ? 'text-primary' : 'text-gray-500' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('user.betting-tickets*') ? '2' : '1.5' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="text-[10px] font-medium">Thống kê</span>
                </a>

                <!-- KQXS -->
                <a href="{{ route('user.kqxs') }}"
                   class="flex flex-col items-center justify-center flex-1 gap-0.5 py-2 {{ request()->routeIs('user.kqxs*') ? 'text-primary' : 'text-gray-500' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="{{ request()->routeIs('user.kqxs*') ? '2' : '1.5' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="text-[10px] font-medium">KQXS</span>
                </a>
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
