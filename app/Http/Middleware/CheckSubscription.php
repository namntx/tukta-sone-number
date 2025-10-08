<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Nếu user chưa đăng nhập, redirect về login
        if (!$user) {
            return redirect()->route('login');
        }

        // Nếu user là admin, cho phép truy cập
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Kiểm tra subscription
        if (!$user->hasActiveSubscription()) {
            // Redirect về trang subscription với thông báo
            return redirect()->route('user.subscription')
                ->with('error', 'Bạn cần có gói subscription để truy cập tính năng này.');
        }

        return $next($request);
    }
}