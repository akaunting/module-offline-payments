<?php

namespace Modules\OfflinePayments\Tests\Feature;

use Tests\Feature\PaymentTestCase;

class PaymentsTest extends PaymentTestCase
{
    public $alias = 'offline-payments';

    public $payment_request = [
        'payment_method'    => 'offline-payments.cash.99',
        'type'              => 'income',
    ];

    public $setting_request = [
        'name'          => 'offline payments',
        'code'          => 'offline-payments.cash.99',
        'customer'      => 1,
        'order'         => 1,
        'description'   => 'description',
    ];

    public function testItShouldPayFromSigned()
    {
        $this->assertPaymentFromSigned();
    }

    public function testItShouldPayFromPortal()
    {
        $this->assertPaymentFromPortal();
    }
}
