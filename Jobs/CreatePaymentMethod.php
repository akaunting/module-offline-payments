<?php

namespace Modules\OfflinePayments\Jobs;

use App\Abstracts\Job;
use App\Utilities\Modules;
use Illuminate\Support\Str;

class CreatePaymentMethod extends Job
{
    protected $request;

    /**
     * Create a new job instance.
     *
     * @param  $request
     */
    public function __construct($request)
    {
        $this->request = $this->getRequestInstance($request);
    }

    /**
     * Execute the job.
     *
     * @return array
     */
    public function handle()
    {
        $methods = json_decode(setting('offline-payments.methods'), true) ?: [];

        $name = (string) $this->request->get('name');

        // A method with the same name is already there
        foreach ($methods as $method) {
            if (Str::lower(trim($method['name'])) === Str::lower(trim($name))) {
                return $method;
            }
        }

        // A name in a script the slug can not write, like Arabic or Japanese, leaves it empty
        $slug = Str::slug($name, '_') ?: 'method';

        // Counted from the number of methods, and past the codes in use, which a deleted method frees
        $codes = array_column($methods, 'code');
        $number = count($methods) + 1;

        while (in_array('offline-payments.' . $slug . '.' . $number, $codes, true)) {
            $number++;
        }

        $code = 'offline-payments.' . $slug . '.' . $number;

        $payment_method = [
            'code' => $code,
            'name' => $this->request->get('name'),
            'customer' => $this->request->get('customer'),
            'order' => $this->request->get('order'),
            'description' => $this->request->get('description'),
        ];

        $methods[] = $payment_method;

        setting()->set('offline-payments.methods', json_encode($methods));

        setting()->save();

        Modules::clearPaymentMethodsCache();

        return $payment_method;
    }
}
