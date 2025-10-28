<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Support\Region;

class GlobalFilters
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Set default global date if not set
        if (!Session::has('global_date')) {
            Session::put('global_date', date('Y-m-d'));
        }

        // Set default global region nếu chưa có (dùng key nội bộ)
        if (!Session::has('global_region')) {
            Session::put('global_region', 'nam'); // <- đổi từ 'Bắc' thành 'nam' mặc định
        }

        // Handle global filter updates
        if ($request->has('global_date')) {
            Session::put('global_date', $request->input('global_date'));
        }

         // Cập nhật từ query (không đụng rule cũ "filter không đổi session" nếu bạn đã áp)
        if ($request->has('global_region')) {
            $norm = Region::normalizeKey($request->input('global_region'));
            Session::put('global_region', $norm);
        } else {
            // Nếu đã có sẵn nhưng là dạng “Bắc/Trung/Nam” thì chuẩn hoá 1 lần
            $curr = Session::get('global_region');
            Session::put('global_region', Region::normalizeKey($curr));
        }

        // Share ra view cả key & label
        $gr = Session::get('global_region', 'nam');
        view()->share([
            'global_date'   => Session::get('global_date'),
            'global_region' => $gr,
            'global_region_label' => \App\Support\Region::label($gr),
        ]);

        return $next($request);
    }
}