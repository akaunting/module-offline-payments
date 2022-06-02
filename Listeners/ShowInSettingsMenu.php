<?php

namespace Modules\OfflinePayments\Listeners;

use App\Events\Menu\SettingsCreated as Event;
use App\Traits\Modules;
use App\Traits\Permissions;

class ShowInSettingsMenu
{
    use Modules, Permissions;

    /**
     * Handle the event.
     *
     * @param  Event $event
     * @return void
     */
    public function handle(Event $event)
    {
        if (!$this->moduleIsEnabled('offline-payments')) {
            return;
        }

        $title = trans('offline-payments::general.name');

        if ($this->canAccessMenuItem($title, 'read-offline-payments-settings')) {
            $event->menu->route('offline-payments.settings.edit', $title, [], 100, ['icon' => 'credit_card', 'search_keywords' => trans('offline-payments::general.description')]);
        }
    }
}
