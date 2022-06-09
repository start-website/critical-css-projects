<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Crit_tokens;

class EnsureTokenIsValid
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
        $site_url = $request->input('site_url');
        $site_url_domain = parse_url($site_url, PHP_URL_HOST);
        $token_user = $request->input('token');
        $token_table = Crit_tokens::where('domain', $domain)->value('token');
        $tariff = Crit_tokens::where('domain', $domain)->value('tariff');
        $date_expiration_table = Crit_tokens::where('domain', $domain)->value('date_expiration');
        $now_date = date('Y-m-d');

        if (!preg_match("/^(\w+?\.|)$domain/", $site_url_domain, $domain_matches)) {
            return response()->json('The domain does not match the passed URL', 200);
        }

        if ($tariff === 'Базовый') {
            $sub_domain = $domain_matches[1];

            if ($sub_domain !== 'www.' || $sub_domain !== '') {
                return response()->json('Subdomains are not allowed on the "Basic" plan', 200);
            }
        }

        if ($token_user !== $token_table) {
            return response()->json('Token does not match domain', 200);
        }

        if ($now_date > $date_expiration_table) {
            return response()->json('Token expired', 200);
        }

        return $next($request);
    }
}
