<?php

namespace Modules\OfflinePayments\Tests\Feature;

use App\Jobs\Auth\CreateUser;
use App\Models\Auth\Permission;
use Modules\OfflinePayments\Jobs\CreatePaymentMethod;
use Modules\OfflinePayments\Jobs\DeletePaymentMethod;
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

    public function testItShouldShowTheTitleOfThePaymentMethod()
    {
        $payment_method = $this->dispatch(new CreatePaymentMethod($this->getRequest()));

        $this->loginAs()
            ->post(route('offline-payments.settings.get', ['code' => $payment_method['code']]))
            ->assertOk()
            ->assertJson([
                'data' => [
                    'title' => trans('offline-payments::general.edit', ['method' => $payment_method['name']]),
                ],
            ]);

        foreach (['id-ID', 'ja-JP', 'nb-NO'] as $locale) {
            $this->assertStringContainsString('Cash', trans('offline-payments::general.edit', ['method' => 'Cash'], $locale));
        }
    }

    public function testItShouldGiveEveryPaymentMethodItsOwnCode()
    {
        // Names the slug can not write
        $arabic = $this->dispatch(new CreatePaymentMethod(['name' => 'نقدا'] + $this->getRequest()));
        $japanese = $this->dispatch(new CreatePaymentMethod(['name' => '現金'] + $this->getRequest()));

        $this->assertNotSame($arabic['code'], $japanese['code']);
        $this->assertStringNotContainsString('..', $arabic['code']);

        // A code freed by a deleted method is not given to a new one that is already in use
        $card = $this->dispatch(new CreatePaymentMethod(['name' => 'Card'] + $this->getRequest()));

        $this->dispatch(new DeletePaymentMethod(['code' => 'offline-payments.bank_transfer.2']));

        $other_card = $this->dispatch(new CreatePaymentMethod(['name' => 'Card!'] + $this->getRequest()));

        $this->assertNotSame($card['code'], $other_card['code']);

        $codes = array_column(json_decode(setting('offline-payments.methods'), true), 'code');

        $this->assertSame($codes, array_values(array_unique($codes)));

        // The same name again is the method that is there
        $this->assertSame($card['code'], $this->dispatch(new CreatePaymentMethod(['name' => 'card'] + $this->getRequest()))['code']);
    }

    public function testItShouldKeepTheMethodsAsAListWhenOneIsDeleted()
    {
        // The one in the middle, after the cash and before the new one
        $this->dispatch(new CreatePaymentMethod($this->getRequest()));

        $this->dispatch(new DeletePaymentMethod(['code' => 'offline-payments.bank_transfer.2']));

        $this->assertStringStartsWith('[', setting('offline-payments.methods'));
        $this->assertSame([0, 1], array_keys(json_decode(setting('offline-payments.methods'), true)));
    }

    public function testItShouldNotDeleteTheDefaultPaymentMethod()
    {
        setting(['default.payment_method' => 'offline-payments.cash.1'])->save();

        $this->loginAs()
            ->delete(route('offline-payments.settings.delete', ['code' => 'offline-payments.cash.1']))
            ->assertOk()
            ->assertJson(['success' => false]);

        $this->assertContains('offline-payments.cash.1', array_column(json_decode(setting('offline-payments.methods'), true), 'code'));
    }

    public function testItShouldWorkWithoutPaymentMethods()
    {
        setting(['offline-payments.methods' => ''])->save();

        $this->loginAs()
            ->get(route('offline-payments.settings.edit'))
            ->assertOk();

        $this->loginAs()
            ->post(route('offline-payments.settings.get', ['code' => 'offline-payments.cash.1']))
            ->assertOk()
            ->assertJson(['success' => true, 'data' => []]);

        $payment_method = $this->dispatch(new CreatePaymentMethod($this->getRequest()));

        $this->assertSame([$payment_method['code']], array_column(json_decode(setting('offline-payments.methods'), true), 'code'));
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
