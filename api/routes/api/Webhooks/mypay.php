<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Store\MyPayWebhookController;

Route::post('/mypay', [MyPayWebhookController::class, 'receivePaymentConfirmation']);