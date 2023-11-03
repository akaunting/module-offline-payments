<x-layouts.admin>
    <x-slot name="title">{{ trans('offline-payments::general.name') }}</x-slot>

    <x-slot name="favorite"
        title="{{ trans('offline-payments::general.name') }}"
        icon="credit_card"
        route="offline-payments.settings.edit"
    ></x-slot>

    <x-slot name="content">
        <x-show.container>
            <x-show.content class="flex flex-col-reverse lg:flex-row mt-5 sm:mt-12 gap-y-4" override="class">
                <x-show.content.left>
                    <x-form id="offline-payment" method="POST" route="offline-payments.settings.update">
                        <x-form.section>
                            <x-slot name="head">
                                <x-form.section.head title="{{ trans('general.add_new') }}" description="{{ trans('offline-payments::general.description') }}" />
                            </x-slot>
        
                            <x-slot name="body">
                                <x-form.group.text name="name" label="{{ trans('general.name') }}" form-group-class="sm:col-span-6" />
        
                                <x-form.group.text name="order" label="{{ trans('offline-payments::general.form.order') }}" form-group-class="sm:col-span-6" not-required />
        
                                <x-form.group.textarea name="description" label="{{ trans('general.description') }}" form-group-class="sm:col-span-6" row="3" enable-v-model="true" not-required />
        
                                <x-form.group.toggle name="customer" label="{{ trans('offline-payments::general.form.customer') }}" :value="false" />
        
                                <x-form.input.hidden name="update_code" />
                            </x-slot>
        
                            <x-slot name="foot">
                                <x-form.buttons :cancel="url()->previous()" />
                            </x-slot>
                        </x-form.section>
                    </x-form>
                </x-show.content.left>
                
                <x-show.content.right>
                    <x-form.section.head title="{{ trans('offline-payments::general.payment_gateways') }}" description="{{ trans('offline-payments::general.gateways_description') }}" />

                    <x-table>
                        <x-table.thead>
                            <x-table.tr class="flex items-center px-1">
                                <x-table.th class="w-3/12 sm:w-3/12">
                                    {{ trans('general.name') }}
                                </x-table.th>
            
                                <x-table.th class="w-4/12 sm:w-5/12">
                                    {{ trans('general.description') }}
                                </x-table.th>
            
                                <x-table.th class="w-2/12 sm:w-2/12">
                                    {{ trans('offline-payments::general.form.order') }}
                                </x-table.th>
            
                                <x-table.th class="w-3/12 sm:w-2/12">
                                    {{ trans('general.actions') }}
                                </x-table.th>
                            </x-table.tr>
                        </x-table.thead>
            
                        <x-table.tbody>
                            @foreach($methods as $item)
                                <x-table.tr id="method-{{ $item->code }}">
                                    <x-table.th class="w-3/12 sm:w-3/12">
                                        {{ $item->name }}
                                    </x-table.th>
            
                                    <x-table.th class="w-4/12 sm:w-5/12">
                                        {{ $item->description ?? trans('general.na') }}
                                    </x-table.th>
            
                                    <x-table.th class="w-2/12 sm:w-2/12">
                                        {{ $item->order }}
                                    </x-table.th>
            
                                    <x-table.th class="w-3/12 sm:w-2/12">
                                        <div class="ltr:right-8 rtl:left-8 flex items-center">
                                            @can('update-offline-payments-settings')
                                                <x-button
                                                    type="button"
                                                    id="edit-{{ $item->code }}"
                                                    data-code="{{ $item->code }}"
                                                    class="relative bg-white hover:bg-gray-100 border py-0.5 px-1 cursor-pointer index-actions group"
                                                    override="class"
                                                    @click="onEdit('{{ $item->code }}')">
                                                    <span class="material-icons-outlined text-purple text-lg">edit</span>
                                                    <div class="inline-block absolute invisible z-20 py-1 px-2 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 shadow-sm opacity-0 tooltip-content -top-10 -left-2" data-tooltip-placement="top">
                                                        <span>{{ trans('general.edit') }}</span>
                                                        <div class="absolute w-2 h-2 -bottom-1 before:content-[' '] before:absolute before:w-2 before:h-2 before:bg-white before:border-gray-200 before:transform before:rotate-45 before:border before:border-t-0 before:border-l-0" data-popper-arrow></div>
                                                    </div>
                                                </x-button>
                                            @endcan
        
                                            @can('delete-offline-payments-settings')
                                                <x-button
                                                    type="button"
                                                    class="relative bg-white hover:bg-gray-100 border py-0.5 px-1 cursor-pointer index-actions"
                                                    override="class"
                                                    id="delete-{{ $item->code }}"
                                                    data-code="{{ $item->code }}"
                                                    v-bind:disabled="update_code === '{{ $item->code }}'"
                                                    @click="confirmDelete('{{ $item->code }}', '{{ trans('general.delete') }}', '{{ trans('general.delete_confirm', ['name' => '<strong>' . $item->name . '</strong>', 'type' => mb_strtolower(trans('offline-payments::general.name'))]) }}', '{{ trans('general.cancel') }}', '{{ trans('general.delete') }}')">
                                                    <span class="material-icons-outlined text-purple text-lg">delete</span>
                                                    <div class="inline-block absolute invisible z-20 py-1 px-2 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 shadow-sm opacity-0 tooltip-content -top-10 -left-2" data-tooltip-placement="top">
                                                        <span>{{ trans('general.delete') }}</span>
                                                        <div class="absolute w-2 h-2 -bottom-1 before:content-[' '] before:absolute before:w-2 before:h-2 before:bg-white before:border-gray-200 before:transform before:rotate-45 before:border before:border-t-0 before:border-l-0" data-popper-arrow></div>
                                                    </div>
                                                </x-button>
                                            @endcan
                                        </div>
                                    </x-table.th>
                                </x-table.tr>
                            @endforeach
                        </x-table.tbody>
                    </x-table>
                </x-show.content.right>
            </x-show.content>
        </x-show.container>
    </x-slot>

    <x-script alias="offline-payments" file="offline-payments" />
</x-layouts.admin>
