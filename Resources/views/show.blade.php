<div>
    <div>
        @if (!empty($setting['name']))
            <h2>{{ $setting['name'] }}</h2>
        @endif

        @if (!empty($setting['description']))
            <div class="well well-sm mt-2 blockquote">{{ $setting['description'] }}</div>
        @endif
    </div>
    <br>

    <div class="buttons">
        <div class="pull-right">
            <x-form id="redirect-form" method="POST" :url="$confirm_url">
                <x-button
                    id="button-confirm"
                    type="submit"
                    @click="onRedirectConfirm"
                    class="relative flex items-center justify-center bg-green hover:bg-green-700 text-white px-6 py-1.5 text-base rounded-lg disabled:bg-green-100"
                    ::disabled="form.loading"
                    override="class"
                >
                    <i v-if="form.loading" class="submit-spin absolute w-3 h-3 rounded-full left-0 right-0 -top-3.5 m-auto"></i>
                    <span :class="[{'opacity-0': form.loading}]">
                        {{ trans('general.confirm') }}
                    </span>
                </x-button>

                <x-form.input.hidden name="payment_method" :value="$setting['code']" />

                <x-form.input.hidden name="type" value="income" />
            </x-form>
        </div>
    </div>
</div>
