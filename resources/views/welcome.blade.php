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

    <div class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 border-b border-gray-200">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 sm:py-28">

            <div class="text-center">   
                <!-- Badge -->

                <div class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-green-50 to-emerald-50 rounded-full mb-8 border border-green-200/60 shadow-sm">

                    <svg class="w-4 h-4 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">

                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>

                    </svg>

                    <span class="text-green-700 text-sm font-semibold">Hệ thống tính toán tự động #1</span>

                </div>



                <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold text-gray-900 mb-6 leading-tight tracking-tight">

                    Hệ thống Quản lý<br />

                    <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Bảng Tính Số Thông Minh</span>

                </h1>



                <p class="text-lg sm:text-xl text-gray-600 mb-8 max-w-3xl mx-auto leading-relaxed">

                    Tự động hóa 100% việc tính toán thắng/thua, quản lý phiếu cược và khách hàng. Chính xác, nhanh chóng, dễ dàng.

                </p>

            </div>

        </div>

    </div>

    <!-- Overview & Contact Section -->
    <div class="bg-white border-t border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
                <div class="space-y-6">
                    <div class="inline-block px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm font-semibold">
                        Vì sao chọn Keki SaaS
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 leading-tight">
                        Tự động hóa toàn bộ quy trình<br>
                        <span class="text-blue-600">từ nhập cược tới thống kê</span>
                    </h2>
                    <p class="text-lg text-gray-600 leading-relaxed">
                        Hệ thống được xây dựng riêng cho nhu cầu quản lý bảng tính số: nhập tin nhắn tự nhiên, phân tích chính xác cho cả 3 miền, tổng hợp số liệu và chăm sóc khách hàng trong một giao diện thống nhất.
                    </p>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="p-5 rounded-xl border border-blue-100 bg-blue-50/50">
                            <h3 class="text-sm font-semibold text-blue-700 uppercase tracking-wide mb-2">Parser thông minh</h3>
                            <p class="text-sm text-gray-600">Hiểu đúng cú pháp thực tế, tự động tách loại cược, số và tiền.</p>
                        </div>
                        <div class="p-5 rounded-xl border border-emerald-100 bg-emerald-50/40">
                            <h3 class="text-sm font-semibold text-emerald-700 uppercase tracking-wide mb-2">Tính toán 3 miền</h3>
                            <p class="text-sm text-gray-600">Công thức chuẩn Bắc - Trung - Nam, tự động xác định đài theo ngày.</p>
                        </div>
                        <div class="p-5 rounded-xl border border-violet-100 bg-violet-50/40">
                            <h3 class="text-sm font-semibold text-violet-700 uppercase tracking-wide mb-2">Khách hàng & bảng giá</h3>
                            <p class="text-sm text-gray-600">Theo dõi lịch sử cược, thiết lập tỷ lệ mua & trả thưởng riêng từng khách.</p>
                        </div>
                        <div class="p-5 rounded-xl border border-slate-200 bg-slate-50">
                            <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide mb-2">Báo cáo trực quan</h3>
                            <p class="text-sm text-gray-600">Tổng hợp lời/lỗ, cập nhật theo ngày/tháng, tối ưu cho mọi thiết bị.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-6 border-t border-gray-200">
                        <div class="text-center">
                            <div class="text-3xl font-extrabold text-blue-600">100%</div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Tự động</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-extrabold text-emerald-600">10+</div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Loại cược</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-extrabold text-indigo-600">3</div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Miền hỗ trợ</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-extrabold text-slate-800">24/7</div>
                            <div class="text-xs text-gray-500 uppercase tracking-wide">Hoạt động</div>
                        </div>
                    </div>
                </div>
                <div class="lg:pl-10">
                    <div class="bg-white rounded-2xl border border-blue-100 shadow-xl shadow-blue-500/10 p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9.305 15.417l-.395 5.561c.564 0 .806-.242 1.096-.532l2.633-2.526 5.461 4c1.002.553 1.716.263 1.989-.928l3.606-16.889.001-.001c.319-1.487-.538-2.069-1.51-1.707L1.48 9.64c-1.45.563-1.428 1.371-.247 1.734l5.61 1.752L18.94 6.4c.636-.42 1.214-.187.738.233"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Liên hệ nhanh</h3>
                                <p class="text-sm text-gray-500 mt-0.5">Tư vấn & demo ngay trong ngày</p>
                            </div>
                        </div>
                        <div class="bg-gradient-to-r from-sky-50 to-blue-50 rounded-xl p-5 border border-blue-100">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-gray-600">Telegram</span>
                                <span class="text-xs px-2.5 py-1 bg-blue-100 text-blue-700 rounded-full font-medium">Khuyến nghị</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-sky-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M9.305 15.417l-.395 5.561c.564 0 .806-.242 1.096-.532l2.633-2.526 5.461 4c1.002.553 1.716.263 1.989-.928l3.606-16.889.001-.001c.319-1.487-.538-2.069-1.51-1.707L1.48 9.64c-1.45.563-1.428 1.371-.247 1.734l5.61 1.752L18.94 6.4c.636-.42 1.214-.187.738.233"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs text-gray-500 mb-1">Username</div>
                                    <div class="text-lg font-bold text-gray-900 truncate">@mikesmith9z</div>
                                </div>
                            </div>
                            <a href="https://t.me/mikesmith9z"
                               target="_blank"
                               rel="noopener"
                               class="mt-5 inline-flex items-center justify-center w-full px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold rounded-lg shadow-sm shadow-blue-500/20 hover:from-blue-700 hover:to-indigo-700 transition-colors">
                                Mở Telegram
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14"/>
                                </svg>
                            </a>
                        </div>
                        <p class="mt-5 text-xs text-gray-500 leading-relaxed">
                            Chúng tôi đồng hành từ khâu setup, nhập dữ liệu ban đầu cho tới tối ưu quy trình vận hành mỗi ngày.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
 

    <!-- Features Section -->

    <div id="features" class="hidden">

        <div class="text-center mb-12">
            <div class="inline-block px-4 py-2 bg-blue-100 text-blue-700 rounded-lg text-sm font-semibold mb-4">Tính năng nổi bật</div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-3 tracking-tight">Tất cả những gì bạn cần</h2>
            <p class="text-base sm:text-lg text-gray-600 max-w-2xl mx-auto">Tập trung vào tính chính xác, tốc độ và sự đơn giản trong sử dụng hàng ngày.</p>
        </div>

 

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Feature 1 -->

            <div class="bg-white rounded-xl border border-blue-100 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Parser thông minh</h3>
                <p class="text-sm text-gray-600">Nhập tự nhiên như tin nhắn, hệ thống tự động hiểu và xử lý chính xác.</p>
            </div>

 

            <!-- Feature 2 -->

            <div class="bg-white rounded-xl border border-emerald-100 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-emerald-600 to-green-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Đầy đủ loại cược</h3>
                <p class="text-sm text-gray-600">Bao lô, Đá xiên, Đầu/Đuôi, Xỉu chủ, Kéo hàng đơn vị…</p>
            </div>



            <!-- Feature 3 -->

            <div class="bg-white rounded-xl border border-indigo-100 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Hỗ trợ cả 3 miền</h3>
                <p class="text-sm text-gray-600">Công thức riêng cho Bắc/Trung/Nam. Tự xác định đài chính theo ngày.</p>
            </div>



            <!-- Feature 4 -->

            <div class="bg-white rounded-xl border border-violet-100 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-violet-600 to-fuchsia-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Quản lý khách hàng</h3>
                <p class="text-sm text-gray-600">Hồ sơ, lịch sử cược, số dư và thống kê chi tiết cho từng khách.</p>
            </div>



            <!-- Feature 5 -->

            <div class="bg-white rounded-xl border border-amber-100 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Bảng giá linh hoạt</h3>
                <p class="text-sm text-gray-600">Thiết lập tỷ lệ mua và trả thưởng theo từng loại cược cho từng khách.</p>
            </div>



            <!-- Feature 6 -->

            <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm hover:shadow-md transition-shadow">
                <div class="w-12 h-12 rounded-lg bg-slate-800 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Responsive Design</h3>
                <p class="text-sm text-gray-600">Tối ưu cho mobile, tablet, desktop. Làm việc mọi lúc, mọi nơi.</p>
            </div>

        </div>

    </div>

 

    <!-- Parser Demo Section -->

    <div class="bg-white py-16 sm:py-20">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-12">

                <div class="inline-block px-4 py-2 bg-emerald-100 text-emerald-700 rounded-lg text-sm font-semibold mb-4">
                    Demo trực quan
                </div>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-3 tracking-tight">
                    Nhập tự nhiên như tin nhắn
                </h2>
                <p class="text-base sm:text-lg text-gray-600 max-w-2xl mx-auto">
                    Không cần form phức tạp — chỉ việc gõ như khi nhắn tin, hệ thống tự hiểu.
                </p>
            </div>

 

            <div class="max-w-4xl mx-auto">

                <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-200">

                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">

                        <div class="text-gray-800 font-semibold text-sm">Parser Demo</div>

                        <div class="text-xs text-gray-500">Thử ví dụ thực tế</div>

                    </div>

                    <div class="p-6">

                        <div class="space-y-5 mb-6">

                            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">

                                <div class="text-xs text-gray-600 mb-2 font-medium">Input</div>

                                <div class="font-mono text-sm sm:text-base text-gray-900">23 12 49 20 dd10n 293 120 lo 20n</div>

                            </div>

                            <div class="flex justify-center">

                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>

                                </svg>

                            </div>

                            <div class="rounded-xl p-4 border border-blue-200 bg-gradient-to-br from-blue-50 to-indigo-50">

                                <div class="text-xs text-blue-700 font-semibold mb-3">Output: 10 cược</div>

                                <div class="space-y-2 text-xs sm:text-sm">

                                    <div class="flex items-center justify-between bg-white/80 backdrop-blur rounded px-3 py-2">

                                        <span class="text-gray-700">Đầu/Đuôi (23, 12, 49, 20)</span>

                                        <span class="font-medium text-blue-600">10,000đ</span>

                                    </div>

                                    <div class="flex items-center justify-between bg-white/80 backdrop-blur rounded px-3 py-2">

                                        <span class="text-gray-700">Bao lô 3 số (293, 120)</span>

                                        <span class="font-medium text-blue-600">20,000đ</span>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <div class="flex items-start gap-3 bg-emerald-50 rounded-xl p-4 border border-emerald-200">

                            <svg class="w-5 h-5 text-emerald-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">

                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>

                            </svg>

                            <div class="text-xs sm:text-sm text-gray-700">

                                <span class="font-semibold">Cú pháp linh hoạt:</span> hỗ trợ: "2dai 11 22 dx 5n", "tg ag 23 lo 10n", "01 keo 09 dd 10n"...

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

    <div class="hidden">

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

 


 

    <!-- Footer -->

    <div class="bg-gradient-to-br from-gray-900 to-gray-800 border-t border-gray-700">

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

            <div class="text-center">

                <div class="flex items-center justify-center mb-4">

                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center mr-3">

                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>

                        </svg>

                    </div>

                    <span class="text-white font-bold text-lg">Keki SaaS</span>

                </div>

                <p class="text-gray-400 mb-6">© {{ date('Y') }} Keki SaaS. Hệ thống quản lý bảng tính số thông minh.</p>

                <div class="flex items-center justify-center gap-4 pt-4 border-t border-gray-700/50">

                    <div class="flex items-center gap-2 text-gray-300 hover:text-white transition-colors">

                        <svg class="w-5 h-5 text-sky-400" fill="currentColor" viewBox="0 0 24 24">

                            <path d="M9.305 15.417l-.395 5.561c.564 0 .806-.242 1.096-.532l2.633-2.526 5.461 4c1.002.553 1.716.263 1.989-.928l3.606-16.889.001-.001c.319-1.487-.538-2.069-1.51-1.707L1.48 9.64c-1.45.563-1.428 1.371-.247 1.734l5.61 1.752L18.94 6.4c.636-.42 1.214-.187.738.233"/>

                        </svg>

                        <span class="font-medium">@mikesmith9z</span>

                    </div>

                </div>

            </div>

        </div>

    </div>

</body>

</html>