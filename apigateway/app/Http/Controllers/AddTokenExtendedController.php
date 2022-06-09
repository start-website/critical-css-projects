<?php

namespace App\Http\Controllers;

use App\Models\Crit_tokens;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResponseToken;

class AddTokenExtendedController extends Controller
{
    protected $valid = true;
    protected $response = '';
    protected $data;
    private $domain_name;
    private $token;
    private $date_expiration;
    private $order_number = 100;
    public $tariff;

    public function action($data) 
    {
        $this->data = $data;

        $this->addtokenDB($this->data);

        $response_arr = [
            'added' => 1,
            'domain' => $this->domain_name,
            'token' => $this->token,
            'valid_date' => $this->date_expiration,
            'title' => 'Получение токена по тарифу «Расширенный» от сервиса criticalcss.ru',
            'subject' => 'Получение токена по тарифу «Расширенный» от сервиса criticalcss.ru',
            'order_number' => $this->order_number,
            'tariff' => $this->tariff
        ];

        $this->sendEmail($this->data['email'], $response_arr);
    }

    public function addtokenDB($data)
    {
        $this->tariff = 'Расширенный';
        $this->domain_name = $data['domain'];
        $email = $data['email'];
        $this->token = bin2hex(random_bytes(52));
        $date_activation = date('Y-m-d');
        $this->date_expiration = date('Y-m-d', strtotime('+1 year'));
        $is_domain_exist = Crit_tokens::where('domain', $this->domain_name)->first();
        $order_numbers = Crit_tokens::pluck('order_number');
        $order_numbers_arr = [];

        foreach ($order_numbers as $order_number) {
            array_push($order_numbers_arr, $order_number);
        }

        $last_order_number = max($order_numbers_arr);

        if ($last_order_number) {
            $this->order_number = $last_order_number + 1;
        }

        if (!$is_domain_exist) {
            $tokens = new Crit_tokens;
            $tokens->domain = $this->domain_name;
            $tokens->email = $email;
            $tokens->token = $this->token;
            $tokens->tariff = $this->tariff;
            $tokens->order_number = $this->order_number;
            $tokens['date_activation'] = $date_activation;
            $tokens['date_expiration'] = $this->date_expiration;
            $tokens->save();
        } else {
            $columns = [
                'email' => $email,
                'tariff' => $this->tariff,
                'date_expiration' => $this->date_expiration
            ];

            Crit_tokens::where('domain', $this->domain_name)->update($columns);
        }

        return true;
    }

    public function sendEmail($to, $params = array())
    {
        Mail::to($to)->send(new ResponseToken($params));
    }
}
