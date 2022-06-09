<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GetCriticalCssController;
use App\Http\Controllers\AddTokenTestController;
use App\Http\Controllers\CreatePaymentBaseTokenController;
use App\Http\Controllers\CreatePaymentExtendedTokenController;
use App\Http\Controllers\NotificationPaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', function () {
    //Log::info('User failed to login.', ['id' => 'ssssssssssss']);
    
    return 'API';
    return view('welcome');
});


Route::middleware(['throttle:global'])->group(function () {
       
});

// Api для формы на сайте
Route::post('/critical-css-form', [GetCriticalCssController::class, 'action']);

// Api для post запросов со сторонних сайтов
Route::middleware(['throttle:generator_css', 'check.token'])->post('/critical-css', [GetCriticalCssController::class, 'action']);

// Api для выдачи токенов
Route::post('/add-token/test/', [AddTokenTestController::class, 'action']);

// Api для создания платежа
Route::post('/create-payment/base-token', [CreatePaymentBaseTokenController::class, 'action']);
Route::post('/create-payment/extended-token', [CreatePaymentExtendedTokenController::class, 'action']);

// Api для уведомлений о платеже
Route::post('/notification-payment', [NotificationPaymentController::class, 'action']);

