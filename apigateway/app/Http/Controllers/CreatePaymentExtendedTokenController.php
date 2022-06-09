<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use YooKassa\Client;

class CreatePaymentExtendedTokenController extends Controller
{
    protected $validated_data;

    public function action(Request $request)
    {
        $this->validated_data = $request->validate([
            'domain' => ['bail', 'required', 'string'],
            'email' => 'bail|required|email',
        ]);

        $metadata = [
            'domain' => $this->validated_data['domain'],
            'email' => $this->validated_data['email'],
            'tariff' => 'extended'
        ];

        $id = '908522';
        $key = 'live_-ikJVZLsfwbSyVE1vNVFBOf-DfSGFHFIYfL0Ghjzgeg';
        $idempotenceKey = uniqid('', true);

        $client = new Client();
        $client->setAuth($id, $key);
        $payment = $client->createPayment(
            array(
                'amount' => array(
                    'value' => 1499.0,
                    'currency' => 'RUB',
                ),
                'confirmation' => array(
                    'type' => 'redirect',
                    'return_url' => 'https://criticalcss.ru/get-extended-token',
                ),
                'capture' => true,
                'description' => 'Токен (тариф расширенный)',
                'metadata' => $metadata,
            ),
            $idempotenceKey
        );

        return response()->json($payment, 200);
    }
}
