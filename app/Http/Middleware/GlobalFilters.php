<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

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

        // Set default global region if not set
        if (!Session::has('global_region')) {
            Session::put('global_region', 'Bắc');
        }

        // Handle global filter updates
        if ($request->has('global_date')) {
            Session::put('global_date', $request->input('global_date'));
        }

        if ($request->has('global_region')) {
            Session::put('global_region', $request->input('global_region'));
        }

        // Make global filters available to all views
        view()->share([
            'global_date' => Session::get('global_date'),
            'global_region' => Session::get('global_region'),
        ]);

        return $next($request);
    }
}