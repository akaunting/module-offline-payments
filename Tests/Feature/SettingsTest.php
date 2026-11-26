<?php

namespace Modules\OfflinePayments\Tests\Feature;

use App\Jobs\Auth\CreateUser;
use App\Models\Auth\Permission;
use Modules\OfflinePayments\Jobs\CreatePaymentMethod;
use Tests\Feature\FeatureTestCase;

class SettingsTest extends FeatureTestCase
{
    public function testItShouldSeeOfflinePaymentsSettingsListPage()
    {
        $this->loginAs()
            ->get(route('offline-payments.settings.edit'))
            ->assertOk()
            ->assertSeeText(trans('offline-payments::general.payment_gateways'))
            ->assertSeeText(trans('offline-payments::general.description'));
    }

    public function testItShouldSeeOfflinePaymentsSettingsCreatePage()
    {
        $this->loginAs()
            ->get(route('offline-payments.settings.edit'))
            ->assertOk()
            ->assertSeeText(trans('offline-payments::general.add_new'));
    }

    public function testItShouldCreateOfflinePaymentsSettings()
    {
        $request = $this->getRequest();

        $message = trans('messages.success.added', ['type' => $request['name']]);

        $this->loginAs()
            ->post(route('offline-payments.settings.update'), $request)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => $message,
            ]);
    }

    public function testItShouldSeeOfflinePaymentsSettingsUpdatePage()
    {
        $payment_method = $this->dispatch(new CreatePaymentMethod($this->getRequest()));

        $this->loginAs()
            ->post(route('offline-payments.settings.get', ['code' => $payment_method['code']]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => $payment_method['name'],
                ],
            ]);
    }

    public function testItShouldNotGetThePaymentMethodWithoutTheSettingsPermission()
    {
        $payment_method = $this->dispatch(new CreatePaymentMethod($this->getRequest()));

        // Plain models, as in the core tests, with the panel permission only
        $role = role_model_class()::create([
            'name' => 'panel-reader',
            'display_name' => 'Panel Reader',
            'description' => 'Panel Reader',
        ]);

        $role->attachPermission(Permission::firstOrCreate(['name' => 'read-admin-panel'], [
            'display_name' => 'read-admin-panel',
            'description' => 'read-admin-panel',
        ]));

        $reader = $this->dispatch(new CreateUser(array_merge(user_model_class()::factory()->raw(), [
            'enabled' => 1,
            'companies' => [$this->company->id],
            'roles' => [$role->id],
        ])));

        $this->loginAs($reader)
            ->withExceptionHandling()
            ->post(route('offline-payments.settings.get', ['code' => $payment_method['code']]))
            ->assertForbidden();
    }

    public function testItShouldEscapeTheNameOfThePaymentMethodInTheMessages()
    {
        $request = $this->getRequest();
        $request['name'] = '<img src=x onerror=alert(1)>';

        $this->loginAs()
            ->post(route('offline-payments.settings.update'), $request)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => trans('messages.success.added', ['type' => escape_html($request['name'])]),
            ]);
    }

    public function testItShouldUpdateOfflinePaymentsSettings()
    {
        $request = $this->getRequest();

        $payment_method = $this->dispatch(new CreatePaymentMethod($request));

        $request['name'] = $this->faker->text(10);
        $request['update_code'] = $payment_method['code'];

        $message = trans('messages.success.updated', ['type' => $request['name']]);

        $this->loginAs()
            ->post(route('offline-payments.settings.update'), $request)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => $message,
            ]);
    }

    public function testItShouldDeleteAccount()
    {
        $payment_method = $this->dispatch(new CreatePaymentMethod($this->getRequest()));

        $message = trans('messages.success.deleted', ['type' => $payment_method['name']]);

        $this->loginAs()
            ->delete(route('offline-payments.settings.delete', ['code' => $payment_method['code']]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => $message,
            ]);
    }

    public function getRequest()
    {
        return [
            'name' => $this->faker->text(10),
            'code' => 'offline-payments.cash.99',
            'customer' => 1,
            'order' => 1,
            'description' => $this->faker->paragraph,
        ];
    }
}
