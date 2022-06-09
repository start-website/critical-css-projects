<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use YooKassa\Model\Notification\NotificationSucceeded;
use YooKassa\Model\Notification\NotificationWaitingForCapture;
use YooKassa\Model\NotificationEventType;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AddTokenBaseController;
use App\Http\Controllers\AddTokenExtendedController;

class NotificationPaymentController extends Controller
{
    public function action(Request $request)
    {
        //$source = file_get_contents('php://input');
        $source = $request->getContent();
        $request_body = json_decode($source, true);

        try {
            $notification = ($request_body['event'] === NotificationEventType::PAYMENT_SUCCEEDED) ? new NotificationSucceeded($request_body) : new NotificationWaitingForCapture($request_body);
        } catch (Exception $e) {
            Log::debug($e->getMessage());
        }

        $payment = $notification->getObject();
        $is_paid = $payment->status === 'succeeded';
        $tariff = $payment->metadata->tariff;
        
        if ($is_paid) {
            $data = [
                'domain' => $payment->metadata->domain,
                'email' => $payment->metadata->email,
            ];

            if ($tariff === 'base') {
                $add_token_base = new AddTokenBaseController;
                $add_token_base->action($data);
            }

            if ($tariff === 'extended') {
                $add_token_extended = new AddTokenExtendedController;
                $add_token_extended->action($data);
            }
        }

        return response('ok', 200);
    }
}
