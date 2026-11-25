<?php

namespace Modules\OfflinePayments\Http\Controllers;

use App\Abstracts\Http\PaymentController;
use \App\Events\Document\PaymentReceived;
use App\Http\Requests\Portal\InvoicePayment as PaymentRequest;
use App\Models\Document\Document;
use App\Utilities\Modules;
use Illuminate\Http\Request;

class Payment extends PaymentController
{
    public $alias = 'offline-payments';

    public $type = 'redirect';

    public function show(Document $invoice, PaymentRequest $request, $cards = [])
    {
        $setting = [];

        $payment_methods = json_decode(setting('offline-payments.methods'), true);

        foreach ($payment_methods as $payment_method) {
            if ($payment_method['code'] == $request['payment_method']) {
                $setting = $payment_method;

                break;
            }
        }

        if (empty($setting)) {
            Modules::clearPaymentMethodsCache();

            return response()->json([
                'code' => '',
                'name' => 'N/A',
                'description' => '',
                'redirect' => redirect()->back()->getTargetUrl(),
                'html' => '',
            ]);
        }

        $confirm_url = $this->getConfirmUrl($invoice);

        $html = view('offline-payments::show', compact('setting', 'invoice', 'confirm_url'))->render();

        return response()->json([
            'code' => $setting['code'],
            'name' => $setting['name'],
            'description' => $setting['description'],
            'redirect' => false,
            'html' => $html,
        ]);
    }

    public function confirm(Document $invoice, Request $request)
    {
        try {
            // The confirm form only picks the payment method. The amount, currency,
            // rate, account and category are worked out from the invoice, not sent.
            $payment_method = $this->getPaymentMethodCode($request->get('payment_method'));

            event(new PaymentReceived($invoice, [
                'payment_method' => $payment_method,
                'type' => 'income',
            ]));

            $message = trans('messages.success.added', ['type' => trans_choice('general.payments', 1)]);

            flash($message)->success();

            $response = [
                'success' => true,
                'error' => false,
                'message' => $message,
                'data' => false,
                'redirect' => $this->getFinishUrl($invoice),
            ];
        } catch(\Exception $e) {
            $message = $e->getMessage();

            flash($message)->error()->important();

            $response = [
                'success' => false,
                'error' => true,
                'message' => $message,
                'data' => false,
                'redirect' => $this->getInvoiceUrl($invoice),
            ];
        }

        return response()->json($response);
    }

    /**
     * The code of a payment method set up in the settings of the app.
     *
     * @throws \Exception
     */
    protected function getPaymentMethodCode($code): string
    {
        $payment_methods = json_decode(setting('offline-payments.methods'), true) ?: [];

        if (! is_string($code) || ! in_array($code, array_column($payment_methods, 'code'), true)) {
            throw new \Exception(trans('validation.in', ['attribute' => trans_choice('general.payment_methods', 1)]));
        }

        return $code;
    }
}
