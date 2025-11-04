<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Keki SaaS - Hệ thống Quản lý Bảng Tính Số</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
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
<body class="h-full bg-white antialiased">
    <!-- Hero Section -->
    <div class="relative bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-800 overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 sm:pt-24 pb-20 sm:pb-32">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl mb-6">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold text-white mb-6 leading-tight">
                    Hệ thống Quản lý<br />
                    <span class="bg-gradient-to-r from-yellow-300 to-orange-300 bg-clip-text text-transparent">Bảng Tính Số</span>
                </h1>
                <p class="text-lg sm:text-xl text-purple-100 mb-10 max-w-2xl mx-auto leading-relaxed">
                    Tự động hóa việc tính toán thắng/thua, quản lý phiếu cược và khách hàng một cách chính xác và nhanh chóng
                </p>
                @guest
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('login') }}" 
                       class="inline-flex items-center justify-center px-8 py-3.5 bg-white text-purple-600 text-base font-semibold rounded-xl hover:bg-gray-50 transition-all shadow-xl hover:shadow-2xl hover:scale-105">
                        Đăng nhập
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                </div>
                @else
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('user.dashboard') }}" 
                       class="inline-flex items-center justify-center px-8 py-3.5 bg-white text-purple-600 text-base font-semibold rounded-xl hover:bg-gray-50 transition-all shadow-xl hover:shadow-2xl hover:scale-105">
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

    <!-- Features Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">
        <div class="text-center mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Tính năng mạnh mẽ</h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">Hệ thống được thiết kế để tối ưu hóa quy trình quản lý bảng tính số</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="group relative bg-white rounded-2xl border border-gray-200 p-8 hover:shadow-xl hover:border-purple-300 transition-all duration-300">
                <div class="absolute top-0 right-0 w-32 h-32 bg-purple-100 rounded-full -mr-16 -mt-16 opacity-0 group-hover:opacity-100 transition-opacity blur-3xl"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Tự động tính toán</h3>
                    <p class="text-gray-600 leading-relaxed">
                        So khớp tự động với kết quả xổ số và tính toán thắng/thua chính xác cho mọi loại cược
                    </p>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="group relative bg-white rounded-2xl border border-gray-200 p-8 hover:shadow-xl hover:border-blue-300 transition-all duration-300">
                <div class="absolute top-0 right-0 w-32 h-32 bg-blue-100 rounded-full -mr-16 -mt-16 opacity-0 group-hover:opacity-100 transition-opacity blur-3xl"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Đa dạng loại cược</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Hỗ trợ đầy đủ: Bao lô, Đầu, Đuôi, Xiên, Đá thẳng, Đá xiên, Xỉu chủ và nhiều loại khác
                    </p>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="group relative bg-white rounded-2xl border border-gray-200 p-8 hover:shadow-xl hover:border-green-300 transition-all duration-300">
                <div class="absolute top-0 right-0 w-32 h-32 bg-green-100 rounded-full -mr-16 -mt-16 opacity-0 group-hover:opacity-100 transition-opacity blur-3xl"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Hỗ trợ 3 miền</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Tính toán tự động cho cả 3 miền: Miền Bắc, Miền Trung và Miền Nam với công thức riêng
                    </p>
                </div>
            </div>

            <!-- Feature 4 -->
            <div class="group relative bg-white rounded-2xl border border-gray-200 p-8 hover:shadow-xl hover:border-indigo-300 transition-all duration-300">
                <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-100 rounded-full -mr-16 -mt-16 opacity-0 group-hover:opacity-100 transition-opacity blur-3xl"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Quản lý khách hàng</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Quản lý thông tin khách hàng, bảng giá riêng và thống kê chi tiết theo ngày/tháng/năm
                    </p>
                </div>
            </div>

            <!-- Feature 5 -->
            <div class="group relative bg-white rounded-2xl border border-gray-200 p-8 hover:shadow-xl hover:border-yellow-300 transition-all duration-300">
                <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-100 rounded-full -mr-16 -mt-16 opacity-0 group-hover:opacity-100 transition-opacity blur-3xl"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Bảng giá linh hoạt</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Thiết lập bảng giá riêng cho từng khách hàng theo từng loại cược và miền một cách dễ dàng
                    </p>
                </div>
            </div>

            <!-- Feature 6 -->
            <div class="group relative bg-white rounded-2xl border border-gray-200 p-8 hover:shadow-xl hover:border-pink-300 transition-all duration-300">
                <div class="absolute top-0 right-0 w-32 h-32 bg-pink-100 rounded-full -mr-16 -mt-16 opacity-0 group-hover:opacity-100 transition-opacity blur-3xl"></div>
                <div class="relative">
                    <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-rose-500 rounded-xl flex items-center justify-center mb-6 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Mobile-first</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Giao diện tối ưu cho mobile và tablet, dễ dàng sử dụng mọi lúc mọi nơi trên mọi thiết bị
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- How it works -->
    <div class="bg-gradient-to-b from-gray-50 to-white py-16 sm:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Quy trình đơn giản</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">3 bước để bắt đầu sử dụng hệ thống</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 sm:gap-12">
                <div class="text-center relative">
                    <div class="mx-auto w-20 h-20 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <span class="text-3xl font-bold text-white">1</span>
                    </div>
                    <div class="absolute top-10 left-1/2 transform translate-x-12 hidden md:block">
                        <svg class="w-8 h-8 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Nhập phiếu cược</h3>
                    <p class="text-gray-600 leading-relaxed">Nhập thông tin phiếu cược từ khách hàng, hệ thống tự động parse và lưu trữ</p>
                </div>

                <div class="text-center relative">
                    <div class="mx-auto w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <span class="text-3xl font-bold text-white">2</span>
                    </div>
                    <div class="absolute top-10 left-1/2 transform translate-x-12 hidden md:block">
                        <svg class="w-8 h-8 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Lấy kết quả xổ số</h3>
                    <p class="text-gray-600 leading-relaxed">Tự động lấy kết quả xổ số từ nhiều nguồn cho cả 3 miền chỉ với một cú click</p>
                </div>

                <div class="text-center">
                    <div class="mx-auto w-20 h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg">
                        <span class="text-3xl font-bold text-white">3</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Tự động tính toán</h3>
                    <p class="text-gray-600 leading-relaxed">Hệ thống tự động so khớp và tính toán thắng/thua, cập nhật số dư khách hàng</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    @guest
    <div class="bg-gradient-to-r from-purple-600 via-purple-700 to-indigo-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <div class="text-center">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Sẵn sàng bắt đầu?</h2>
                <p class="text-lg sm:text-xl text-purple-100 mb-8 max-w-2xl mx-auto">Đăng nhập để trải nghiệm hệ thống quản lý bảng tính số ngay hôm nay</p>
                <a href="{{ route('login') }}" 
                   class="inline-flex items-center justify-center px-8 py-3.5 bg-white text-purple-600 text-base font-semibold rounded-xl hover:bg-gray-50 transition-all shadow-xl hover:shadow-2xl hover:scale-105">
                    Đăng nhập ngay
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    @endguest
</body>
</html>
