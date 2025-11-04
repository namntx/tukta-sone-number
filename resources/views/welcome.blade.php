<!DOCTYPE html>

<html lang="vi" class="h-full scroll-smooth">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Keki SaaS - Hệ thống Quản lý Bảng Tính Số Thông Minh</title>

 

    <!-- Fonts -->

    <link rel="preconnect" href="https://fonts.bunny.net">

    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />

 

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

 

    <style>

        @keyframes float {

            0%, 100% { transform: translateY(0px); }

            50% { transform: translateY(-20px); }

        }

        @keyframes pulse-glow {

            0%, 100% { box-shadow: 0 0 20px rgba(147, 51, 234, 0.5); }

            50% { box-shadow: 0 0 40px rgba(147, 51, 234, 0.8); }

        }

        @keyframes gradient-shift {

            0% { background-position: 0% 50%; }

            50% { background-position: 100% 50%; }

            100% { background-position: 0% 50%; }

        }

        .animate-float { animation: float 6s ease-in-out infinite; }

        .animate-float-delayed { animation: float 6s ease-in-out 2s infinite; }

        .animate-pulse-glow { animation: pulse-glow 3s ease-in-out infinite; }

        .gradient-animate {

            background-size: 200% 200%;

            animation: gradient-shift 8s ease infinite;

        }

    </style>

</head>

<body class="h-full bg-white antialiased">

    <!-- Hero Section -->

    <div class="relative bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-800 overflow-hidden">

        <!-- Animated Background Elements -->

        <div class="absolute inset-0 opacity-20">

            <div class="absolute top-20 left-10 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-float"></div>

            <div class="absolute top-40 right-10 w-72 h-72 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl animate-float-delayed"></div>

            <div class="absolute bottom-20 left-1/3 w-72 h-72 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl animate-float"></div>

        </div>

 

        <!-- Grid Pattern -->

        <div class="absolute inset-0 opacity-10">

            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>

        </div>

 

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 sm:pt-28 pb-24 sm:pb-40">

            <div class="text-center">

                <!-- Icon with glow effect -->

                <div class="inline-flex items-center justify-center w-24 h-24 bg-white/20 backdrop-blur-sm rounded-3xl mb-8 animate-pulse-glow">

                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>

                    </svg>

                </div>

 

                <!-- Badge -->

                <div class="inline-flex items-center px-4 py-2 bg-white/10 backdrop-blur-md rounded-full mb-6 border border-white/20">

                    <svg class="w-4 h-4 text-green-300 mr-2" fill="currentColor" viewBox="0 0 20 20">

                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>

                    </svg>

                    <span class="text-white text-sm font-medium">Hệ thống tính toán tự động #1</span>

                </div>

 

                <h1 class="text-4xl sm:text-5xl md:text-7xl font-extrabold text-white mb-8 leading-tight">

                    Hệ thống Quản lý<br />

                    <span class="bg-gradient-to-r from-yellow-300 via-orange-300 to-pink-300 bg-clip-text text-transparent gradient-animate">

                        Bảng Tính Số Thông Minh

                    </span>

                </h1>

 

                <p class="text-xl sm:text-2xl text-purple-100 mb-12 max-w-3xl mx-auto leading-relaxed font-medium">

                    Tự động hóa 100% việc tính toán thắng/thua, quản lý phiếu cược và khách hàng.

                    <span class="text-yellow-300">Chính xác, nhanh chóng, dễ dàng.</span>

                </p>

 

                @guest

                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">

                    <a href="{{ route('login') }}"

                       class="group inline-flex items-center justify-center px-10 py-4 bg-white text-purple-600 text-lg font-bold rounded-2xl hover:bg-gray-50 transition-all shadow-2xl hover:shadow-3xl hover:scale-105 hover:-translate-y-1">

                        Đăng nhập ngay

                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>

                        </svg>

                    </a>

                    <a href="#features"

                       class="inline-flex items-center justify-center px-10 py-4 bg-white/10 backdrop-blur-sm text-white text-lg font-semibold rounded-2xl hover:bg-white/20 transition-all border-2 border-white/30">

                        Tìm hiểu thêm

                    </a>

                </div>

                @else

                <div class="flex flex-col sm:flex-row gap-4 justify-center">

                    <a href="{{ route('user.dashboard') }}"

                       class="group inline-flex items-center justify-center px-10 py-4 bg-white text-purple-600 text-lg font-bold rounded-2xl hover:bg-gray-50 transition-all shadow-2xl hover:shadow-3xl hover:scale-105 hover:-translate-y-1">

                        Vào hệ thống

                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>

                        </svg>

                    </a>

                </div>

                @endguest

            </div>

        </div>

 

        <!-- Wave separator -->

        <div class="absolute bottom-0 left-0 right-0">

            <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">

                <path d="M0 120L60 110C120 100 240 80 360 70C480 60 600 60 720 65C840 70 960 80 1080 85C1200 90 1320 90 1380 90L1440 90V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="white"/>

            </svg>

        </div>

    </div>

 

    <!-- Stats Section -->

    <div class="bg-white py-12 sm:py-16">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">

                <div class="text-center">

                    <div class="text-4xl sm:text-5xl font-extrabold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent mb-2">100%</div>

                    <div class="text-gray-600 font-medium">Tự động tính toán</div>

                </div>

                <div class="text-center">

                    <div class="text-4xl sm:text-5xl font-extrabold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">10+</div>

                    <div class="text-gray-600 font-medium">Loại cược hỗ trợ</div>

                </div>

                <div class="text-center">

                    <div class="text-4xl sm:text-5xl font-extrabold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent mb-2">3</div>

                    <div class="text-gray-600 font-medium">Miền Bắc/Trung/Nam</div>

                </div>

                <div class="text-center">

                    <div class="text-4xl sm:text-5xl font-extrabold bg-gradient-to-r from-pink-600 to-rose-600 bg-clip-text text-transparent mb-2">24/7</div>

                    <div class="text-gray-600 font-medium">Hoạt động liên tục</div>

                </div>

            </div>

        </div>

    </div>

 

    <!-- Features Section -->

    <div id="features" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-32">

        <div class="text-center mb-20">

            <div class="inline-block px-4 py-2 bg-purple-100 text-purple-600 rounded-full text-sm font-semibold mb-4">

                ✨ TÍNH NĂNG NỔI BẬT

            </div>

            <h2 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mb-6">

                Tất cả những gì bạn cần

            </h2>

            <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">

                Hệ thống được thiết kế để tối ưu hóa hoàn toàn quy trình quản lý bảng tính số của bạn

            </p>

        </div>

 

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-10">

            <!-- Feature 1 -->

            <div class="group relative bg-white rounded-3xl border-2 border-gray-200 p-8 hover:shadow-2xl hover:border-purple-300 hover:-translate-y-2 transition-all duration-300">

                <div class="absolute top-0 right-0 w-32 h-32 bg-purple-100 rounded-full -mr-16 -mt-16 opacity-0 group-hover:opacity-100 transition-opacity blur-3xl"></div>

                <div class="relative">

                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg group-hover:scale-110 transition-transform">

                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>

                        </svg>

                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Parser thông minh</h3>

                    <p class="text-gray-600 leading-relaxed mb-4">

                        Nhập tự nhiên như tin nhắn: "23 12 lo 10n" hoặc "2dai 11 22 dx 5n". Hệ thống tự động hiểu và xử lý.

                    </p>

                    <div class="inline-flex items-center text-purple-600 font-semibold text-sm">

                        <span>Tìm hiểu thêm</span>

                        <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>

                        </svg>

                    </div>

                </div>

            </div>

 

            <!-- Feature 2 -->

            <div class="group relative bg-white rounded-3xl border-2 border-gray-200 p-8 hover:shadow-2xl hover:border-blue-300 hover:-translate-y-2 transition-all duration-300">

                <div class="absolute top-0 right-0 w-32 h-32 bg-blue-100 rounded-full -mr-16 -mt-16 opacity-0 group-hover:opacity-100 transition-opacity blur-3xl"></div>

                <div class="relative">

                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg group-hover:scale-110 transition-transform">

                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>

                        </svg>

                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Đầy đủ loại cược</h3>

                    <p class="text-gray-600 leading-relaxed mb-4">

                        Bao lô 2-3-4 số, Đầu, Đuôi, Đầu Đuôi, Xiên, Đá thẳng, Đá xiên, Xỉu chủ, Kéo hàng đơn vị và nhiều hơn nữa.

                    </p>

                    <div class="flex flex-wrap gap-2">

                        <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-xs font-semibold">Bao lô</span>

                        <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-xs font-semibold">Đá xiên</span>

                        <span class="px-3 py-1 bg-blue-50 text-blue-600 rounded-full text-xs font-semibold">Đầu Đuôi</span>

                    </div>

                </div>

            </div>

 

            <!-- Feature 3 -->

            <div class="group relative bg-white rounded-3xl border-2 border-gray-200 p-8 hover:shadow-2xl hover:border-green-300 hover:-translate-y-2 transition-all duration-300">

                <div class="absolute top-0 right-0 w-32 h-32 bg-green-100 rounded-full -mr-16 -mt-16 opacity-0 group-hover:opacity-100 transition-opacity blur-3xl"></div>

                <div class="relative">

                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg group-hover:scale-110 transition-transform">

                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>

                        </svg>

                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Hỗ trợ cả 3 miền</h3>

                    <p class="text-gray-600 leading-relaxed mb-4">

                        Tính toán tự động cho Miền Bắc, Miền Trung và Miền Nam với công thức riêng cho từng miền.

                    </p>

                    <div class="space-y-2">

                        <div class="flex items-center text-sm text-gray-600">

                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">

                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>

                            </svg>

                            Auto-resolve đài chính theo ngày

                        </div>

                    </div>

                </div>

            </div>

 

            <!-- Feature 4 -->

            <div class="group relative bg-white rounded-3xl border-2 border-gray-200 p-8 hover:shadow-2xl hover:border-indigo-300 hover:-translate-y-2 transition-all duration-300">

                <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-100 rounded-full -mr-16 -mt-16 opacity-0 group-hover:opacity-100 transition-opacity blur-3xl"></div>

                <div class="relative">

                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg group-hover:scale-110 transition-transform">

                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>

                        </svg>

                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Quản lý khách hàng</h3>

                    <p class="text-gray-600 leading-relaxed mb-4">

                        Quản lý thông tin, lịch sử cược, số dư và thống kê chi tiết cho từng khách hàng.

                    </p>

                    <div class="flex items-center justify-between text-sm">

                        <span class="text-gray-500">Thống kê chi tiết</span>

                        <svg class="w-5 h-5 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">

                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>

                        </svg>

                    </div>

                </div>

            </div>

 

            <!-- Feature 5 -->

            <div class="group relative bg-white rounded-3xl border-2 border-gray-200 p-8 hover:shadow-2xl hover:border-yellow-300 hover:-translate-y-2 transition-all duration-300">

                <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-100 rounded-full -mr-16 -mt-16 opacity-0 group-hover:opacity-100 transition-opacity blur-3xl"></div>

                <div class="relative">

                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl flex items-center justify-center mb-6 shadow-lg group-hover:scale-110 transition-transform">

                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>

                        </svg>

                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Bảng giá linh hoạt</h3>

                    <p class="text-gray-600 leading-relaxed mb-4">

                        Thiết lập bảng giá riêng cho từng khách hàng: tỷ lệ mua, tỷ lệ trả thưởng theo từng loại cược.

                    </p>

                    <div class="flex items-center text-yellow-600 font-semibold text-sm">

                        <svg class="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">

                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path>

                        </svg>

                        Tùy chỉnh không giới hạn

                    </div>

                </div>

            </div>

 

            <!-- Feature 6 -->

            <div class="group relative bg-white rounded-3xl border-2 border-gray-200 p-8 hover:shadow-2xl hover:border-pink-300 hover:-translate-y-2 transition-all duration-300">

                <div class="absolute top-0 right-0 w-32 h-32 bg-pink-100 rounded-full -mr-16 -mt-16 opacity-0 group-hover:opacity-100 transition-opacity blur-3xl"></div>

                <div class="relative">

                    <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-rose-500 rounded-2xl flex items-center justify-center mb-6 shadow-lg group-hover:scale-110 transition-transform">

                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>

                        </svg>

                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Responsive Design</h3>

                    <p class="text-gray-600 leading-relaxed mb-4">

                        Giao diện tối ưu cho mobile, tablet và desktop. Làm việc mọi lúc, mọi nơi trên mọi thiết bị.

                    </p>

                    <div class="flex gap-2">

                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">

                            <path d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z"></path>

                        </svg>

                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">

                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>

                        </svg>

                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">

                            <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"></path>

                        </svg>

                    </div>

                </div>

            </div>

        </div>

    </div>

 

    <!-- Parser Demo Section -->

    <div class="bg-gradient-to-br from-gray-50 to-purple-50 py-20 sm:py-32">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-16">

                <div class="inline-block px-4 py-2 bg-purple-100 text-purple-600 rounded-full text-sm font-semibold mb-4">

                    🚀 DEMO TRỰC QUAN

                </div>

                <h2 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mb-6">

                    Nhập tự nhiên như tin nhắn

                </h2>

                <p class="text-xl text-gray-600 max-w-3xl mx-auto">

                    Không cần form phức tạp. Chỉ cần gõ như bạn nhắn tin cho khách hàng!

                </p>

            </div>

 

            <div class="max-w-4xl mx-auto">

                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border-2 border-gray-200">

                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 flex items-center">

                        <div class="flex gap-2 mr-4">

                            <div class="w-3 h-3 bg-red-400 rounded-full"></div>

                            <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>

                            <div class="w-3 h-3 bg-green-400 rounded-full"></div>

                        </div>

                        <div class="text-white font-semibold">Parser Demo</div>

                    </div>

                    <div class="p-8">

                        <div class="space-y-4 mb-6">

                            <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200">

                                <div class="text-sm text-gray-500 mb-2">Input:</div>

                                <div class="font-mono text-lg text-gray-900">23 12 49 20 dd10n 293 120 lo 20n</div>

                            </div>

                            <div class="flex justify-center">

                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>

                                </svg>

                            </div>

                            <div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-2xl p-4 border-2 border-purple-200">

                                <div class="text-sm text-purple-700 font-semibold mb-3">Output: 10 cược tự động</div>

                                <div class="space-y-2 text-sm">

                                    <div class="flex items-center justify-between bg-white rounded-lg px-3 py-2">

                                        <span class="text-gray-700">4 số đầu đuôi (23, 12, 49, 20)</span>

                                        <span class="font-semibold text-purple-600">10,000đ</span>

                                    </div>

                                    <div class="flex items-center justify-between bg-white rounded-lg px-3 py-2">

                                        <span class="text-gray-700">2 số bao lô 3 số (293, 120)</span>

                                        <span class="font-semibold text-purple-600">20,000đ</span>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="flex items-start gap-3 bg-blue-50 rounded-xl p-4 border border-blue-200">

                            <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">

                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>

                            </svg>

                            <div class="text-sm text-blue-900">

                                <strong>Cú pháp linh hoạt:</strong> Hỗ trợ nhiều format: "2dai 11 22 dx 5n", "tg ag 23 lo 10n", "01 keo 09 dd 10n"...

                            </div>

                        </div>

                    </div>

                </div>

            </div>
        </div>
    </div>

        </div>

    </div>

 

    <!-- How it works -->

    <div class="bg-white py-20 sm:py-32">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-20">

                <div class="inline-block px-4 py-2 bg-green-100 text-green-600 rounded-full text-sm font-semibold mb-4">

                    ⚡ QUY TRÌNH ĐƠN GIẢN

                </div>

                <h2 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mb-6">Chỉ 3 bước để bắt đầu</h2>

                <p class="text-xl text-gray-600 max-w-2xl mx-auto">Từ nhập phiếu đến tính toán kết quả chỉ trong vài phút</p>

            </div>

 

            <div class="grid grid-cols-1 md:grid-cols-3 gap-12">

                <!-- Step 1 -->

                <div class="text-center relative group">

                    <div class="mx-auto w-24 h-24 bg-gradient-to-br from-purple-500 to-purple-600 rounded-3xl flex items-center justify-center mb-6 shadow-xl group-hover:scale-110 transition-transform">

                        <span class="text-4xl font-bold text-white">1</span>

                    </div>

                    <div class="absolute top-12 left-1/2 transform translate-x-12 hidden md:block">

                        <svg class="w-10 h-10 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>

                        </svg>

                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Nhập phiếu cược</h3>

                    <p class="text-gray-600 leading-relaxed text-lg">

                        Nhập tự nhiên như tin nhắn, hệ thống tự động parse và lưu trữ với độ chính xác 100%

                    </p>

                </div>

 

                <!-- Step 2 -->

                <div class="text-center relative group">

                    <div class="mx-auto w-24 h-24 bg-gradient-to-br from-blue-500 to-blue-600 rounded-3xl flex items-center justify-center mb-6 shadow-xl group-hover:scale-110 transition-transform">

                        <span class="text-4xl font-bold text-white">2</span>

                    </div>

                    <div class="absolute top-12 left-1/2 transform translate-x-12 hidden md:block">

                        <svg class="w-10 h-10 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>

                        </svg>

                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Lấy kết quả XSKT</h3>

                    <p class="text-gray-600 leading-relaxed text-lg">

                        Tự động lấy kết quả từ nhiều nguồn cho cả 3 miền, chỉ với một cú click

                    </p>

                </div>

 

                <!-- Step 3 -->

                <div class="text-center group">

                    <div class="mx-auto w-24 h-24 bg-gradient-to-br from-green-500 to-green-600 rounded-3xl flex items-center justify-center mb-6 shadow-xl group-hover:scale-110 transition-transform">

                        <span class="text-4xl font-bold text-white">3</span>

                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Tính toán tự động</h3>

                    <p class="text-gray-600 leading-relaxed text-lg">

                        Hệ thống tự động so khớp, tính thắng/thua và cập nhật số dư khách hàng

                    </p>

                </div>

            </div>

 

            <!-- Additional info -->

            <div class="mt-16 text-center">

                <div class="inline-flex items-center gap-2 bg-green-50 text-green-700 px-6 py-3 rounded-full border-2 border-green-200">

                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">

                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>

                    </svg>

                    <span class="font-semibold">Tiết kiệm 90% thời gian so với tính thủ công</span>

                </div>

            </div>

        </div>

    </div>

 

    <!-- CTA Section -->

    @guest

    <div class="relative bg-gradient-to-r from-purple-600 via-purple-700 to-indigo-800 overflow-hidden">

        <div class="absolute inset-0 opacity-20">

            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>

        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28">

            <div class="text-center">

                <h2 class="text-4xl sm:text-5xl font-extrabold text-white mb-6">

                    Sẵn sàng bắt đầu?

                </h2>

                <p class="text-xl sm:text-2xl text-purple-100 mb-10 max-w-3xl mx-auto leading-relaxed">

                    Đăng nhập để trải nghiệm hệ thống quản lý bảng tính số thông minh và tiết kiệm thời gian ngay hôm nay

                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">

                    <a href="{{ route('login') }}"

                       class="group inline-flex items-center justify-center px-12 py-5 bg-white text-purple-600 text-xl font-bold rounded-2xl hover:bg-gray-50 transition-all shadow-2xl hover:shadow-3xl hover:scale-105 hover:-translate-y-1">

                        Đăng nhập ngay

                        <svg class="w-6 h-6 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>

                        </svg>

                    </a>

                </div>

                <p class="mt-6 text-purple-200 text-sm">

                    🔒 An toàn · ⚡ Nhanh chóng · ✨ Dễ sử dụng

                </p>

            </div>

        </div>

    </div>

    @endguest

 

    <!-- Footer -->

    <div class="bg-gray-900 text-gray-400 py-12">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center">

                <div class="flex items-center justify-center mb-4">

                    <svg class="w-8 h-8 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>

                    </svg>

                    <span class="text-white font-bold text-xl">Keki SaaS</span>

                </div>

                <p class="text-sm">© {{ date('Y') }} Keki SaaS. Hệ thống quản lý bảng tính số thông minh.</p>

            </div>

        </div>

    </div>

</body>

</html>