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

    public function show(Document $invoice, PaymentRequest $request)
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
            event(new PaymentReceived($invoice, $request));

            $message = trans('messages.success.added', ['type' => trans_choice('general.payments', 1)]);

            $response = [
                'success' => true,
                'error' => false,
                'message' => $message,
                'data' => false,
            ];
        } catch(\Exception $e) {
            $message = $e->getMessage();

            $response = [
                'success' => false,
                'error' => true,
                'message' => $message,
                'data' => false,
            ];
        }

        return response()->json($response);
    }
}
