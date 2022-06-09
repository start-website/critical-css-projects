<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Crit_tokens;

class CheckCorsCriticalCss
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
        $domain = $request->input('domain');
        $is_table_domain = Crit_tokens::where('domain', $domain)->first();

        if (!$is_table_domain) {
            return response()->json('Domain not found', 200);
        }

        return $next($request)->header('Access-Control-Allow-Origin', $request->url());
    }
}
