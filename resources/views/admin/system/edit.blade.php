<x-layouts.admin :title="__('admin.integrations.heading')">
    <x-page-header :title="__('admin.integrations.heading')" :subtitle="__('admin.integrations.subheading')"/>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($status as $channel => $row)
            <x-card class="p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-ink-400">{{ __('admin.integrations.channels.'.$channel) }}</p>
                <p class="mt-2 text-sm font-semibold {{ $row['ready'] ? 'text-success-700' : 'text-warning-700' }}">
                    {{ $row['ready'] ? __('admin.integrations.ready') : __('admin.integrations.missing') }}
                </p>
                <p class="mt-1 text-xs text-ink-500">{{ $row['detail'] }}</p>
                <form method="POST" action="{{ route('admin.system.probe') }}" class="mt-3" x-data="{ result: null }"
                      @submit.prevent="
                          const body = new FormData($el);
                          const response = await fetch($el.action, { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body });
                          result = await response.json();
                      ">
                    @csrf
                    <input type="hidden" name="channel" value="{{ $channel }}">
                    <x-button type="submit" variant="secondary" size="sm">{{ __('admin.integrations.test') }}</x-button>
                    <p class="mt-2 text-xs" x-show="result" x-text="result?.detail" :class="result?.ready ? 'text-success-700' : 'text-danger-600'"></p>
                </form>
            </x-card>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.system.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        <x-card :title="__('admin.integrations.mail')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('admin.integrations.mail_host')" name="integrations.mail_host">
                    <x-input name="integrations[mail_host]" :value="$values['integrations.mail_host']" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.integrations.mail_port')" name="integrations.mail_port">
                    <x-input name="integrations[mail_port]" type="number" :value="$values['integrations.mail_port']" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.integrations.mail_username')" name="integrations.mail_username">
                    <x-input name="integrations[mail_username]" :value="$values['integrations.mail_username']" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.integrations.mail_password')" name="integrations.mail_password">
                    <x-input name="integrations[mail_password]" type="password" dir="ltr" autocomplete="new-password"/>
                    @if ($values['has']['mail_password'])
                        <p class="mt-1 text-xs text-ink-400">{{ __('admin.payments.secret_set') }}</p>
                    @endif
                </x-field>
                <x-field :label="__('admin.integrations.mail_encryption')" name="integrations.mail_encryption">
                    <x-select name="integrations[mail_encryption]" :selected="$values['integrations.mail_encryption'] ?? 'tls'"
                              :options="['tls' => 'TLS', 'ssl' => 'SSL', 'none' => __('common.none')]"/>
                </x-field>
                <x-field :label="__('admin.integrations.mail_from_address')" name="integrations.mail_from_address">
                    <x-input name="integrations[mail_from_address]" type="email" :value="$values['integrations.mail_from_address']" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.integrations.mail_from_name')" name="integrations.mail_from_name">
                    <x-input name="integrations[mail_from_name]" :value="$values['integrations.mail_from_name']"/>
                </x-field>
            </div>
        </x-card>

        <x-card :title="__('admin.integrations.firebase')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('admin.integrations.firebase_project_id')" name="integrations.firebase_project_id">
                    <x-input name="integrations[firebase_project_id]" :value="$values['integrations.firebase_project_id']" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.integrations.firebase_web_api_key')" name="integrations.firebase_web_api_key">
                    <x-input name="integrations[firebase_web_api_key]" :value="$values['integrations.firebase_web_api_key']" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.integrations.firebase_auth_domain')" name="integrations.firebase_auth_domain">
                    <x-input name="integrations[firebase_auth_domain]" :value="$values['integrations.firebase_auth_domain']" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.integrations.firebase_vapid_key')" name="integrations.firebase_vapid_key">
                    <x-input name="integrations[firebase_vapid_key]" :value="$values['integrations.firebase_vapid_key']" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.integrations.firebase_server_key')" name="integrations.firebase_server_key" class="sm:col-span-2">
                    <x-input name="integrations[firebase_server_key]" type="password" dir="ltr" autocomplete="new-password"/>
                    @if ($values['has']['firebase_server_key'])
                        <p class="mt-1 text-xs text-ink-400">{{ __('admin.payments.secret_set') }}</p>
                    @endif
                </x-field>
                <x-field :label="__('admin.integrations.firebase_credentials')" name="integrations.firebase_credentials" class="sm:col-span-2">
                    <x-textarea name="integrations[firebase_credentials]" rows="5" dir="ltr" :placeholder="__('admin.integrations.firebase_credentials_hint')"/>
                    @if ($values['has']['firebase_credentials'])
                        <p class="mt-1 text-xs text-ink-400">{{ __('admin.payments.secret_set') }}</p>
                    @endif
                </x-field>
            </div>
        </x-card>

        <x-card :title="__('admin.integrations.sms_whatsapp')">
            <p class="mb-4 text-sm text-ink-500">{{ __('admin.integrations.sms_hint') }}</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('admin.integrations.sms_provider')" name="integrations.sms_provider">
                    <x-select name="integrations[sms_provider]" :selected="$values['integrations.sms_provider'] ?? 'log'"
                              :options="['twilio' => __('admin.integrations.sms_twilio'), 'log' => __('admin.integrations.sms_log')]"/>
                </x-field>
                <x-field :label="__('admin.integrations.twilio_sid')" name="integrations.twilio_sid">
                    <x-input name="integrations[twilio_sid]" :value="$values['integrations.twilio_sid']" dir="ltr" :placeholder="__('admin.integrations.twilio_sid_hint')"/>
                </x-field>
                <x-field :label="__('admin.integrations.sms_sender')" name="integrations.sms_sender" :hint="__('admin.integrations.sms_sender_hint')">
                    <x-input name="integrations[sms_sender]" :value="$values['integrations.sms_sender']" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.integrations.sms_key')" name="integrations.sms_key" :hint="__('admin.integrations.sms_key_hint')">
                    <x-input name="integrations[sms_key]" type="password" dir="ltr" autocomplete="new-password"/>
                    @if ($values['has']['sms_key'])
                        <p class="mt-1 text-xs text-ink-400">{{ __('admin.payments.secret_set') }}</p>
                    @endif
                </x-field>
                <x-field :label="__('admin.integrations.whatsapp_phone_id')" name="integrations.whatsapp_phone_id">
                    <x-input name="integrations[whatsapp_phone_id]" :value="$values['integrations.whatsapp_phone_id']" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.integrations.whatsapp_token')" name="integrations.whatsapp_token" class="sm:col-span-2">
                    <x-input name="integrations[whatsapp_token]" type="password" dir="ltr" autocomplete="new-password"/>
                    @if ($values['has']['whatsapp_token'])
                        <p class="mt-1 text-xs text-ink-400">{{ __('admin.payments.secret_set') }}</p>
                    @endif
                </x-field>
            </div>
        </x-card>

        <x-card :title="__('admin.integrations.oauth')">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field :label="__('admin.integrations.google_client_id')" name="integrations.google_client_id">
                    <x-input name="integrations[google_client_id]" :value="$values['integrations.google_client_id']" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.integrations.google_client_secret')" name="integrations.google_client_secret">
                    <x-input name="integrations[google_client_secret]" type="password" dir="ltr" autocomplete="new-password"/>
                </x-field>
                <x-field :label="__('admin.integrations.facebook_client_id')" name="integrations.facebook_client_id">
                    <x-input name="integrations[facebook_client_id]" :value="$values['integrations.facebook_client_id']" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.integrations.facebook_client_secret')" name="integrations.facebook_client_secret">
                    <x-input name="integrations[facebook_client_secret]" type="password" dir="ltr" autocomplete="new-password"/>
                </x-field>
                <x-field :label="__('admin.integrations.apple_client_id')" name="integrations.apple_client_id">
                    <x-input name="integrations[apple_client_id]" :value="$values['integrations.apple_client_id']" dir="ltr"/>
                </x-field>
                <x-field :label="__('admin.integrations.apple_client_secret')" name="integrations.apple_client_secret">
                    <x-input name="integrations[apple_client_secret]" type="password" dir="ltr" autocomplete="new-password"/>
                </x-field>
            </div>
        </x-card>

        <x-card :title="__('admin.integrations.messages')">
            <p class="mb-4 text-sm text-ink-500">{{ __('admin.integrations.messages_hint') }}</p>
            <div class="grid gap-4">
                @foreach (\App\Support\MessageTemplates::keys() as $key)
                    @php $short = str_replace('messages.', '', $key); @endphp
                    <x-field :label="__('admin.integrations.'.$short)" :name="'messages.'.$short">
                        @if (str_contains($short, 'email_body') || str_starts_with($short, 'sms_') || $short === 'promo_sms')
                            <x-textarea :name="'messages['.$short.']'" :rows="str_contains($short, 'email_body') ? 3 : 2" :value="$values[$key]"/>
                        @else
                            <x-input :name="'messages['.$short.']'" :value="$values[$key]"/>
                        @endif
                    </x-field>
                @endforeach
            </div>
        </x-card>

        <div class="flex justify-end">
            <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
        </div>
    </form>
</x-layouts.admin>
