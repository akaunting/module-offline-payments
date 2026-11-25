<?php

namespace Modules\OfflinePayments\Tests\Feature;

use Tests\Feature\PaymentTestCase;

class PaymentsTest extends PaymentTestCase
{
    public $alias = 'offline-payments';

    // A payment method that is set up, the cash one the company starts with
    public $payment_request = [
        'payment_method'    => 'offline-payments.cash.1',
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

    public function testItShouldPayTheInvoiceAmountWhateverTheRequestSends()
    {
        $this->updateSetting();

        $this->createInvoice();

        // A tiny amount at a forged rate, as if it were the whole invoice
        $this->post($this->getConfirmUrl(), array_merge($this->payment_request, [
            'amount'        => '0.01',
            'currency_code' => 'EUR',
            'currency_rate' => '0.0000418043',
        ]))->assertOk();

        $this->assertPayment();

        $this->assertDatabaseMissing('transactions', [
            'document_id'   => $this->invoice->id,
            'currency_code' => 'EUR',
        ]);
    }

    public function testItShouldNotPayWithAPaymentMethodThatIsNotSetUp()
    {
        $this->updateSetting();

        $this->createInvoice();

        $this->post($this->getConfirmUrl(), array_merge($this->payment_request, [
            'payment_method' => 'offline-payments.unknown.1',
        ]))
            ->assertOk()
            ->assertJson(['success' => false, 'error' => true]);

        $this->assertNotEquals('paid', $this->invoice->fresh()->status);

        $this->assertDatabaseMissing('transactions', [
            'document_id' => $this->invoice->id,
        ]);
    }
}
