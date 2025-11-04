<!DOCTYPE html>
<html lang="vi" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đăng nhập - Keki SaaS</title>
    
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
<body class="h-full bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-md">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-1">Đăng nhập</h1>
                <p class="text-sm text-gray-600">Nhập thông tin để tiếp tục</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                    @csrf

                    <!-- Error Messages -->
                    @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3">
                        <div class="text-sm font-medium text-red-800 mb-1">Đăng nhập thất bại</div>
                        <ul class="text-xs text-red-700 space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                        <input id="email" 
                               name="email" 
                               type="email" 
                               autocomplete="email" 
                               required 
                               value="{{ old('email') }}"
                               class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition @error('email') border-red-300 @enderror" 
                               placeholder="email@example.com">
                        @error('email')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password Input -->
                    <div>
                        <label for="password" class="block text-xs font-medium text-gray-700 mb-1">Mật khẩu</label>
                        <input id="password" 
                               name="password" 
                               type="password" 
                               autocomplete="current-password" 
                               required 
                               class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white focus:outline-none focus:ring-1 focus:ring-purple-500 focus:border-purple-500 transition @error('password') border-red-300 @enderror" 
                               placeholder="••••••••">
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center">
                        <input id="remember-me" 
                               name="remember" 
                               type="checkbox" 
                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-xs text-gray-700">
                            Ghi nhớ đăng nhập
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition shadow-sm">
                        Đăng nhập
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center">
                <a href="/" class="text-xs text-gray-600 hover:text-purple-600 transition">
                    ← Quay lại trang chủ
                </a>
            </div>
        </div>
    </div>
</body>
</html>
