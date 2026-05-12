<?php

namespace App\Exceptions\MyPay\Webhook;

use App\Exceptions\MyPay\MyPayException;

class InvalidWebhookSignatureException extends MyPayException {}
