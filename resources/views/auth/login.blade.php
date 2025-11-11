<!DOCTYPE html>
<html lang="vi" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đăng nhập - Keki SaaS</title>

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
<body class="h-full bg-gradient-to-br from-blue-50 via-white to-indigo-50 antialiased">
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">
            <!-- Logo & Header -->
            <div class="text-center mb-8">
                <!-- Logo -->
                <div class="flex items-center justify-center mb-6">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-3xl font-extrabold text-gray-900 mb-2 tracking-tight">
                    <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Keki SaaS</span>
                </h1>
                <p class="text-sm text-gray-600">Đăng nhập để tiếp tục sử dụng hệ thống</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-2xl border border-blue-100 shadow-xl shadow-blue-500/10 p-8">
                <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                    @csrf

                    <!-- Error Messages -->
                    @if ($errors->any())
                    <div class="alert alert-error rounded-xl" role="alert">
                        <svg class="w-5 h-5 flex-shrink-0 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <div class="text-sm font-semibold text-red-800 mb-1">Đăng nhập thất bại</div>
                            <ul class="text-xs text-red-700 space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>• {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @endif

                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <input id="email"
                               name="email"
                               type="email"
                               autocomplete="email"
                               required
                               value="{{ old('email') }}"
                               class="w-full px-4 py-2.5 text-sm rounded-lg border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors @error('email') border-red-300 @enderror"
                               placeholder="email@example.com">
                        @error('email')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Password Input -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Mật khẩu</label>
                        <input id="password"
                               name="password"
                               type="password"
                               autocomplete="current-password"
                               required
                               class="w-full px-4 py-2.5 text-sm rounded-lg border border-gray-300 bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors @error('password') border-red-300 @enderror"
                               placeholder="••••••••">
                        @error('password')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input id="remember-me"
                               name="remember"
                               type="checkbox"
                               class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded transition-colors">
                        <label for="remember-me" class="ml-2.5 block text-sm text-gray-700">
                            Ghi nhớ đăng nhập
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-5 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2 transition-all shadow-sm shadow-blue-500/20">
                        Đăng nhập
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center">
                <a href="/" class="inline-flex items-center text-sm text-gray-600 hover:text-primary transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Quay lại trang chủ
                </a>
            </div>
        </div>
    </div>
</body>
</html>
