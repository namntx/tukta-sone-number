@extends('layouts.app')

@section('title', 'Restore dữ liệu - Keki SaaS')

@section('content')
<div class="pb-4">
    <!-- Header -->
    <div class="sticky top-14 z-10 bg-gray-50 border-b border-gray-200 -mx-3 px-3 py-2 mb-3">
        <div class="flex items-center justify-between">
            <h1 class="text-base font-semibold text-gray-900">Restore dữ liệu</h1>
            <a href="{{ route('user.backup-restore.index') }}" class="btn btn-secondary btn-sm btn-icon">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
        </div>
    </div>

    <!-- Errors -->
    @if ($errors->any())
    <div class="alert alert-error mb-3">
        <div>
            <div class="text-sm font-medium mb-1">Vui lòng kiểm tra lại:</div>
            <ul class="text-xs space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Khôi phục dữ liệu từ file backup</h2>
            <p class="text-sm text-gray-600 mb-4">
                Chọn file backup (JSON) để khôi phục dữ liệu. ID khách hàng sẽ được tự động thay đổi khi restore.
            </p>

            <form method="POST" action="{{ route('user.backup-restore.restore') }}" enctype="multipart/form-data">
                @csrf
                
                <div class="space-y-3">
                    <div>
                        <label for="backup_file" class="block text-sm font-medium text-gray-700 mb-2">
                            Chọn file backup (JSON):
                        </label>
                        <input type="file" 
                               id="backup_file" 
                               name="backup_file" 
                               accept=".json"
                               required
                               class="w-full text-sm">
                        <p class="mt-1 text-xs text-gray-500">
                            File tối đa 10MB, định dạng JSON
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('user.backup-restore.index') }}" class="btn btn-secondary flex-1">
                            Hủy
                        </a>
                        <button type="submit" class="btn btn-success flex-1">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            Restore dữ liệu
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Section -->
    <div class="card bg-blue-50 border-blue-200 mt-4">
        <div class="card-body">
            <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-semibold mb-1">Lưu ý:</p>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        <li>Khi restore, ID khách hàng sẽ được tự động tạo mới</li>
                        <li>Dữ liệu cược sẽ được liên kết với khách hàng mới</li>
                        <li>Số điện thoại có thể trùng lặp giữa các user</li>
                        <li>Thống kê (win/lose amounts) sẽ được reset về 0 khi restore</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

