<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Crit_tokens;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResponseToken;
use Illuminate\Support\Facades\Http;

class AddTokenTestController extends Controller
{
    protected $valid = true;
    protected $response = '';
    protected $validated_data;
    private $domain_name;
    private $token;
    private $date_expiration;
    private $order_number = 100;
    public $tariff;

    public function action(Request $request) 
    {
        $this->checkRecaptcha($request['g-recaptcha-response']);

        if (!$this->valid) {
            $response_arr = [
                'added' => 0,
                'response' => $this->response
            ];
    
            return response()->json($response_arr, 200);
        }

        $this->validated_data = $request->validate([
            'domain' => ['bail', 'required', 'string'],
            'email' => 'bail|required|email',
        ]);

        $this->addtokenDB($this->validated_data);

        if (!$this->valid) {
            $response_arr = [
                'added' => 0,
                'response' => $this->response
            ];
    
            return response()->json($response_arr, 200);
        }
        
        $response_arr = [
            'added' => 1,
            'domain' => $this->domain_name,
            'token' => $this->token,
            'valid_date' => $this->date_expiration,
            'title' => 'Получение токена по тарифу «Тестовый» от сервиса criticalcss.ru',
            'subject' => 'Получение токена по тарифу «Тестовый» от сервиса criticalcss.ru',
            'order_number' => $this->order_number,
            'tariff' => $this->tariff
        ];

        $this->sendEmail($this->validated_data['email'], $response_arr);

        return response()->json($response_arr, 200);
    }

    public function checkRecaptcha($recaptcha_response)
    {
        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
        $recaptcha_secret = '6LeeSMsfAAAAAND_d3_hDhd72NjeD6RFMuDwrhPf';

        if (!$recaptcha_response) {
            $this->valid = false;
            $this->response = 'Captcha not transferred';

            return false;
        }

        // $recaptcha_headers = [
        //     'Content-Type' => 'application/x-www-form-urlencoded',
        // ];
        // $recaptcha_post_data = [
        //     'secret' => $recaptcha_secret,
        //     'response' => $recaptcha_response
        // ];
        //$recaptcha_response = Http::withHeaders($recaptcha_headers)->post($recaptcha_url, $recaptcha_post_data);
        
        $recaptcha_response = file_get_contents("$recaptcha_url?secret=".$recaptcha_secret."&response=".$recaptcha_response);
        $recaptcha_response = json_decode($recaptcha_response);

        if ($recaptcha_response->success && $recaptcha_response->score >= 0.5) {
            $this->valid = true;
            return true;
        }

        $this->response = 'Captcha did not pass the test';
        $this->valid = false;

        return false;
    }

    public function addtokenDB($data)
    {
        $this->tariff = 'Тестовый';
        $this->domain_name = $data['domain'];
        $email = $data['email'];
        $this->token = bin2hex(random_bytes(52));
        $date_activation = date('Y-m-d');
        $this->date_expiration = date('Y-m-d', strtotime($date_activation . '+ 3 days'));
        $is_domain_exist = Crit_tokens::where('domain', $this->domain_name)->first();

        $order_numbers = Crit_tokens::pluck('order_number');
        $order_numbers_arr = [];

        foreach ($order_numbers as $order_number) {
            array_push($order_numbers_arr, (int) $order_number);
        }

        $last_order_number = max($order_numbers_arr);

        if ($last_order_number) {
            $this->order_number = $last_order_number + 1;
        }

        if ($is_domain_exist) {
            $this->valid = false;
            $this->response = 'A token has already been obtained for this domain';

            return false;
        }

        $tokens = new Crit_tokens;
        $tokens->domain = $this->domain_name;
        $tokens->email = $email;
        $tokens->token = $this->token;
        $tokens->tariff = $this->tariff;
        $tokens->order_number = $this->order_number;
        $tokens['date_activation'] = $date_activation;
        $tokens['date_expiration'] = $this->date_expiration;
        $tokens->save();

        return true;
    }

    public function sendEmail($to, $params = array())
    {
        Mail::to($to)->send(new ResponseToken($params));
    }
}
