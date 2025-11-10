<!DOCTYPE html>

<html lang="vi" class="h-full scroll-smooth">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Keki SaaS - Hệ thống Quản lý Bảng Tính Số Thông Minh</title>


    <!-- SEO Meta Tags -->
    <meta name="description" content="Hệ thống quản lý bảng tính số thông minh. Tự động hóa 100% việc tính toán thắng/thua, quản lý phiếu cược và khách hàng. Hỗ trợ đầy đủ 3 miền Bắc, Trung, Nam.">
    <meta name="keywords" content="quản lý bảng tính số, hệ thống tính toán tự động, quản lý phiếu cược, xổ số 3 miền, phần mềm quản lý lô đề">
    <meta name="author" content="Keki SaaS">
    <meta name="robots" content="index, follow">

    <!-- Open Graph Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Keki SaaS - Hệ thống Quản lý Bảng Tính Số Thông Minh">
    <meta property="og:description" content="Tự động hóa 100% việc tính toán thắng/thua, quản lý phiếu cược và khách hàng. Chính xác, nhanh chóng, dễ sử dụng.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:site_name" content="Keki SaaS">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Keki SaaS - Hệ thống Quản lý Bảng Tính Số Thông Minh">
    <meta name="twitter:description" content="Tự động hóa 100% việc tính toán thắng/thua, quản lý phiếu cược và khách hàng.">

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url('/') }}">

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

</head>

<body class="h-full bg-gray-50 antialiased">

    <!-- Hero Section -->

    <div class="bg-white border-b border-gray-200">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">

            <div class="text-center">

                <!-- Icon -->

                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-xl mb-6">

                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>

                    </svg>

                </div>



                <!-- Badge -->

                <div class="inline-flex items-center px-4 py-2 bg-green-50 rounded-full mb-6 border border-green-200">

                    <svg class="w-4 h-4 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">

                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>

                    </svg>

                    <span class="text-green-700 text-sm font-medium">Hệ thống tính toán tự động #1</span>

                </div>



                <h1 class="text-3xl sm:text-4xl md:text-5xl font-bold text-gray-900 mb-6 leading-tight">

                    Hệ thống Quản lý<br />

                    Bảng Tính Số Thông Minh

                </h1>



                <p class="text-lg sm:text-xl text-gray-600 mb-10 max-w-3xl mx-auto leading-relaxed">

                    Tự động hóa 100% việc tính toán thắng/thua, quản lý phiếu cược và khách hàng. Chính xác, nhanh chóng, dễ dàng.

                </p>



                @guest

                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">

                    <a href="{{ route('login') }}"

                       class="inline-flex items-center justify-center px-8 py-3 bg-blue-600 text-white text-base font-semibold rounded-lg hover:bg-blue-700 transition-colors shadow-sm">

                        Đăng nhập ngay

                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>

                        </svg>

                    </a>

                    <a href="#features"

                       class="inline-flex items-center justify-center px-8 py-3 bg-white text-gray-700 text-base font-semibold rounded-lg hover:bg-gray-50 transition-colors border border-gray-300">

                        Tìm hiểu thêm

                    </a>

                </div>

                @else

                <div class="flex flex-col sm:flex-row gap-4 justify-center">

                    <a href="{{ route('user.dashboard') }}"

                       class="inline-flex items-center justify-center px-8 py-3 bg-blue-600 text-white text-base font-semibold rounded-lg hover:bg-blue-700 transition-colors shadow-sm">

                        Vào hệ thống

                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>

                        </svg>

                    </a>

                </div>

                @endguest

            </div>

        </div>

    </div>

 

    <!-- Stats Section -->

    <div class="py-12 sm:py-16">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">

                <div class="text-center">

                    <div class="text-3xl sm:text-4xl font-bold text-blue-600 mb-2">100%</div>

                    <div class="text-sm sm:text-base text-gray-600">Tự động tính toán</div>

                </div>

                <div class="text-center">

                    <div class="text-3xl sm:text-4xl font-bold text-green-600 mb-2">10+</div>

                    <div class="text-sm sm:text-base text-gray-600">Loại cược hỗ trợ</div>

                </div>

                <div class="text-center">

                    <div class="text-3xl sm:text-4xl font-bold text-indigo-600 mb-2">3</div>

                    <div class="text-sm sm:text-base text-gray-600">Miền Bắc/Trung/Nam</div>

                </div>

                <div class="text-center">

                    <div class="text-3xl sm:text-4xl font-bold text-gray-900 mb-2">24/7</div>

                    <div class="text-sm sm:text-base text-gray-600">Hoạt động liên tục</div>

                </div>

            </div>

        </div>

    </div>

 

    <!-- Features Section -->

    <div id="features" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">

        <div class="text-center mb-12">

            <div class="inline-block px-4 py-2 bg-blue-50 text-blue-700 rounded-full text-sm font-medium mb-4">

                Tính năng nổi bật

            </div>

            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">

                Tất cả những gì bạn cần

            </h2>

            <p class="text-base sm:text-lg text-gray-600 max-w-3xl mx-auto">

                Hệ thống được thiết kế để tối ưu hóa hoàn toàn quy trình quản lý bảng tính số của bạn

            </p>

        </div>

 

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Feature 1 -->

            <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">

                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mb-4">

                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>

                    </svg>

                </div>

                <h3 class="text-lg font-semibold text-gray-900 mb-3">Parser thông minh</h3>

                <p class="text-sm text-gray-600 leading-relaxed">

                    Nhập tự nhiên như tin nhắn: "23 12 lo 10n" hoặc "2dai 11 22 dx 5n". Hệ thống tự động hiểu và xử lý.

                </p>

            </div>

 

            <!-- Feature 2 -->

            <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">

                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mb-4">

                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>

                    </svg>

                </div>

                <h3 class="text-lg font-semibold text-gray-900 mb-3">Đầy đủ loại cược</h3>

                <p class="text-sm text-gray-600 leading-relaxed mb-3">

                    Bao lô 2-3-4 số, Đầu, Đuôi, Đầu Đuôi, Xiên, Đá thẳng, Đá xiên, Xỉu chủ, Kéo hàng đơn vị và nhiều hơn nữa.

                </p>

                <div class="flex flex-wrap gap-2">

                    <span class="px-2 py-1 bg-green-50 text-green-700 rounded text-xs font-medium">Bao lô</span>

                    <span class="px-2 py-1 bg-green-50 text-green-700 rounded text-xs font-medium">Đá xiên</span>

                    <span class="px-2 py-1 bg-green-50 text-green-700 rounded text-xs font-medium">Đầu Đuôi</span>

                </div>

            </div>



            <!-- Feature 3 -->

            <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">

                <div class="w-12 h-12 bg-indigo-600 rounded-lg flex items-center justify-center mb-4">

                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>

                    </svg>

                </div>

                <h3 class="text-lg font-semibold text-gray-900 mb-3">Hỗ trợ cả 3 miền</h3>

                <p class="text-sm text-gray-600 leading-relaxed mb-3">

                    Tính toán tự động cho Miền Bắc, Miền Trung và Miền Nam với công thức riêng cho từng miền.

                </p>

                <div class="flex items-center text-sm text-gray-600">

                    <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">

                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>

                    </svg>

                    Auto-resolve đài chính theo ngày

                </div>

            </div>



            <!-- Feature 4 -->

            <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">

                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mb-4">

                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>

                    </svg>

                </div>

                <h3 class="text-lg font-semibold text-gray-900 mb-3">Quản lý khách hàng</h3>

                <p class="text-sm text-gray-600 leading-relaxed">

                    Quản lý thông tin, lịch sử cược, số dư và thống kê chi tiết cho từng khách hàng.

                </p>

            </div>



            <!-- Feature 5 -->

            <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">

                <div class="w-12 h-12 bg-yellow-500 rounded-lg flex items-center justify-center mb-4">

                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>

                    </svg>

                </div>

                <h3 class="text-lg font-semibold text-gray-900 mb-3">Bảng giá linh hoạt</h3>

                <p class="text-sm text-gray-600 leading-relaxed">

                    Thiết lập bảng giá riêng cho từng khách hàng: tỷ lệ mua, tỷ lệ trả thưởng theo từng loại cược.

                </p>

            </div>



            <!-- Feature 6 -->

            <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">

                <div class="w-12 h-12 bg-gray-700 rounded-lg flex items-center justify-center mb-4">

                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>

                    </svg>

                </div>

                <h3 class="text-lg font-semibold text-gray-900 mb-3">Responsive Design</h3>

                <p class="text-sm text-gray-600 leading-relaxed mb-3">

                    Giao diện tối ưu cho mobile, tablet và desktop. Làm việc mọi lúc, mọi nơi trên mọi thiết bị.

                </p>

                <div class="flex gap-2">

                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">

                        <path d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z"></path>

                    </svg>

                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">

                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"></path>

                    </svg>

                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">

                        <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"></path>

                    </svg>

                </div>

            </div>

        </div>

    </div>

 

    <!-- Parser Demo Section -->

    <div class="bg-white py-16 sm:py-20">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-12">

                <div class="inline-block px-4 py-2 bg-green-50 text-green-700 rounded-full text-sm font-medium mb-4">

                    Demo trực quan

                </div>

                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">

                    Nhập tự nhiên như tin nhắn

                </h2>

                <p class="text-base sm:text-lg text-gray-600 max-w-3xl mx-auto">

                    Không cần form phức tạp. Chỉ cần gõ như bạn nhắn tin cho khách hàng!

                </p>

            </div>

 

            <div class="max-w-4xl mx-auto">

                <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">

                    <div class="bg-gray-100 px-6 py-3 border-b border-gray-200">

                        <div class="text-gray-700 font-medium text-sm">Parser Demo</div>

                    </div>

                    <div class="p-6">

                        <div class="space-y-4 mb-6">

                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">

                                <div class="text-xs text-gray-600 mb-2 font-medium">Input:</div>

                                <div class="font-mono text-sm sm:text-base text-gray-900">23 12 49 20 dd10n 293 120 lo 20n</div>

                            </div>

                            <div class="flex justify-center">

                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>

                                </svg>

                            </div>

                            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">

                                <div class="text-xs text-blue-700 font-medium mb-3">Output: 10 cược tự động</div>

                                <div class="space-y-2 text-xs sm:text-sm">

                                    <div class="flex items-center justify-between bg-white rounded px-3 py-2">

                                        <span class="text-gray-700">4 số đầu đuôi (23, 12, 49, 20)</span>

                                        <span class="font-medium text-blue-600">10,000đ</span>

                                    </div>

                                    <div class="flex items-center justify-between bg-white rounded px-3 py-2">

                                        <span class="text-gray-700">2 số bao lô 3 số (293, 120)</span>

                                        <span class="font-medium text-blue-600">20,000đ</span>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="flex items-start gap-3 bg-green-50 rounded-lg p-4 border border-green-200">

                            <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">

                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>

                            </svg>

                            <div class="text-xs sm:text-sm text-gray-700">

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

    <div class="py-16 sm:py-20">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-12">

                <div class="inline-block px-4 py-2 bg-indigo-50 text-indigo-700 rounded-full text-sm font-medium mb-4">

                    Quy trình đơn giản

                </div>

                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">Chỉ 3 bước để bắt đầu</h2>

                <p class="text-base sm:text-lg text-gray-600 max-w-2xl mx-auto">Từ nhập phiếu đến tính toán kết quả chỉ trong vài phút</p>

            </div>



            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                <!-- Step 1 -->

                <div class="text-center">

                    <div class="mx-auto w-16 h-16 bg-blue-600 rounded-lg flex items-center justify-center mb-4">

                        <span class="text-2xl font-bold text-white">1</span>

                    </div>

                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Nhập phiếu cược</h3>

                    <p class="text-sm text-gray-600 leading-relaxed">

                        Nhập tự nhiên như tin nhắn, hệ thống tự động parse và lưu trữ với độ chính xác 100%

                    </p>

                </div>



                <!-- Step 2 -->

                <div class="text-center">

                    <div class="mx-auto w-16 h-16 bg-green-600 rounded-lg flex items-center justify-center mb-4">

                        <span class="text-2xl font-bold text-white">2</span>

                    </div>

                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Lấy kết quả XSKT</h3>

                    <p class="text-sm text-gray-600 leading-relaxed">

                        Tự động lấy kết quả từ nhiều nguồn cho cả 3 miền, chỉ với một cú click

                    </p>

                </div>



                <!-- Step 3 -->

                <div class="text-center">

                    <div class="mx-auto w-16 h-16 bg-indigo-600 rounded-lg flex items-center justify-center mb-4">

                        <span class="text-2xl font-bold text-white">3</span>

                    </div>

                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Tính toán tự động</h3>

                    <p class="text-sm text-gray-600 leading-relaxed">

                        Hệ thống tự động so khớp, tính thắng/thua và cập nhật số dư khách hàng

                    </p>

                </div>

            </div>



            <!-- Additional info -->

            <div class="mt-12 text-center">

                <div class="inline-flex items-center gap-2 bg-green-50 text-green-700 px-5 py-2.5 rounded-lg border border-green-200 text-sm">

                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">

                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>

                    </svg>

                    <span class="font-medium">Tiết kiệm 90% thời gian so với tính thủ công</span>

                </div>

            </div>

        </div>

    </div>

 

    <!-- CTA Section -->

    @guest

    <div class="bg-blue-600">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">

            <div class="text-center">

                <h2 class="text-2xl sm:text-3xl font-bold text-white mb-4">

                    Sẵn sàng bắt đầu?

                </h2>

                <p class="text-base sm:text-lg text-blue-100 mb-8 max-w-3xl mx-auto">

                    Đăng nhập để trải nghiệm hệ thống quản lý bảng tính số thông minh và tiết kiệm thời gian ngay hôm nay

                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">

                    <a href="{{ route('login') }}"

                       class="inline-flex items-center justify-center px-8 py-3 bg-white text-blue-600 text-base font-semibold rounded-lg hover:bg-gray-50 transition-colors shadow-sm">

                        Đăng nhập ngay

                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>

                        </svg>

                    </a>

                </div>

                <p class="mt-6 text-blue-100 text-sm">

                    An toàn · Nhanh chóng · Dễ sử dụng

                </p>

            </div>

        </div>

    </div>

    @endguest

 

    <!-- Footer -->

    <div class="bg-white border-t border-gray-200">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <div class="text-center">

                <div class="flex items-center justify-center mb-3">

                    <svg class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>

                    </svg>

                    <span class="text-gray-900 font-semibold text-base">Keki SaaS</span>

                </div>

                <p class="text-sm text-gray-600">© {{ date('Y') }} Keki SaaS. Hệ thống quản lý bảng tính số thông minh.</p>

            </div>

        </div>

    </div>

</body>

</html>