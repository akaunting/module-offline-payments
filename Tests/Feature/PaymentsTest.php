<?php

namespace Modules\OfflinePayments\Tests\Feature;

use Tests\Feature\PaymentTestCase;

class PaymentsTest extends PaymentTestCase
{
    public $alias = 'offline-payments';

    // The method updateSetting() adds, shown to customers. The cash and bank transfer
    // ones the company starts with are not, so a customer can not pay with them.
    public $payment_request = [
        'payment_method'    => 'offline-payments.offline_payments.3',
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

    public function testItShouldNotPayWithAPaymentMethodNotShownToCustomers()
    {
        $this->updateSetting();

        $this->createInvoice();

        // The cash method the company starts with is for the admins only
        $this->post($this->getConfirmUrl(), ['payment_method' => 'offline-payments.cash.1'])
            ->assertOk()
            ->assertJson(['success' => false, 'error' => true]);

        $this->assertDatabaseMissing('transactions', [
            'document_id' => $this->invoice->id,
        ]);
    }

    public function testItShouldNotPayACancelledOrPaidInvoice()
    {
        $this->updateSetting();

        foreach (['cancelled', 'paid'] as $status) {
            $this->createInvoice();

            $this->invoice->status = $status;
            $this->invoice->save();

            $this->post($this->getConfirmUrl(), $this->payment_request)
                ->assertOk()
                ->assertJson(['success' => false, 'error' => true]);

            $this->assertSame($status, $this->invoice->fresh()->status);

            $this->assertDatabaseMissing('transactions', [
                'document_id' => $this->invoice->id,
            ]);
        }
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
