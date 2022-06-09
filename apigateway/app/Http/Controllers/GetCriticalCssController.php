<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class GetCriticalCssController extends Controller
{
    /**
     * Получить критические стили сайта из микросервиса critical_css.
     */
    public function action(Request $request)
    {
        // return response()->json([
        //     'critical_css' => 'sdfsfsdfsdfsdfs',
        //     'state' => 'CA',
        // ]);

        $url = 'https://api.criticalcss.ru/generator-css/';

        $headers = [
            'Content-Type' => 'application/json',
        ];

        $post_data = [
            'site_url' => $request['site_url'],
            'site_width' => $request['site_width'],
            'site_height' => $request['site_height'],
            'ignore_atrule' => $request['ignore_atrule'],
            'ignore_rule' => $request['ignore_rule'],
            'ignore_decl' => $request['ignore_decl'],
            'rebase_from' => $request['rebase_from'],
            'rebase_to' => $request['rebase_to'],
            'cms' => $request['cms'],
            'apigateway_token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJuYW1lIjoiQVBJR0FURVdBWSIsImlhdCI6MTUxNjIzOTAyMn0.web-rfD4ID9kLLKbIIWkJnpSIkQ_aH2C3MYZ0ozD1jU'
        ];

        //Log::debug([$url, $headers, $post_data]);

        $response = Http::withHeaders($headers)->timeout(190)->post($url, $post_data);

        if ($response->successful()) {
            return $response;
        }

        // Определить, имеет ли ответ код состояния >= 400...
        if ($response->failed()) {
            return response($response, 422);
        }

        // Определить, имеет ли ответ код состояния 400 ..
        if ($response->clientError()) {
            return response()->json('CSS generator microservice - Error 400', 400);
        }

        // Определить, имеет ли ответ код состояния 500 ...
        if ($response->serverError()) {
            return response()->json('CSS generator microservice - Error 500', 500);
        }
    }
}
