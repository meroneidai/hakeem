<x-layouts.admin :title="__('admin.seo.site')">
    <x-page-header :title="__('admin.seo.site')" :subtitle="__('admin.seo.site_sub')">
        <x-slot:actions>
            <x-button :href="route('admin.seo-pages.index')" variant="secondary" size="sm">{{ __('admin.seo.heading') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="POST" action="{{ route('admin.seo.site.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <x-card class="max-w-3xl">
            <div class="grid gap-4">
                <x-field :label="__('admin.seo.site_title_ar')" name="seo.site_title_ar">
                    <x-input name="seo[site_title_ar]" :value="$values['seo.site_title_ar']" maxlength="70"/>
                </x-field>
                <x-field :label="__('admin.seo.site_title_en')" name="seo.site_title_en">
                    <x-input name="seo[site_title_en]" :value="$values['seo.site_title_en']" dir="ltr" maxlength="70"/>
                </x-field>
                <x-field :label="__('admin.seo.default_description_ar')" name="seo.default_description_ar">
                    <x-textarea name="seo[default_description_ar]" :value="$values['seo.default_description_ar']" rows="3" maxlength="320"/>
                </x-field>
                <x-field :label="__('admin.seo.default_description_en')" name="seo.default_description_en">
                    <x-textarea name="seo[default_description_en]" :value="$values['seo.default_description_en']" rows="3" dir="ltr" maxlength="320"/>
                </x-field>
                <x-field :label="__('admin.seo.keywords_ar')" name="seo.keywords_ar">
                    <x-textarea name="seo[keywords_ar]" :value="$values['seo.keywords_ar']" rows="2" maxlength="320"/>
                </x-field>
                <x-field :label="__('admin.seo.keywords_en')" name="seo.keywords_en">
                    <x-textarea name="seo[keywords_en]" :value="$values['seo.keywords_en']" rows="2" dir="ltr" maxlength="320"/>
                </x-field>
                <x-field :label="__('admin.seo.og_image')" name="seo.og_image">
                    <x-input name="seo[og_image]" :value="$values['seo.og_image']" dir="ltr" maxlength="500"/>
                </x-field>
                <x-field :label="__('admin.seo.twitter_site')" name="seo.twitter_site">
                    <x-input name="seo[twitter_site]" :value="$values['seo.twitter_site']" dir="ltr" maxlength="64"/>
                </x-field>
                <x-field :label="__('admin.seo.app_ios_url')" name="seo.app_ios_url">
                    <x-input name="seo[app_ios_url]" :value="$values['seo.app_ios_url']" dir="ltr" maxlength="500"/>
                </x-field>
                <x-field :label="__('admin.seo.app_android_url')" name="seo.app_android_url">
                    <x-input name="seo[app_android_url]" :value="$values['seo.app_android_url']" dir="ltr" maxlength="500"/>
                </x-field>
                <p class="text-sm text-ink-500">{{ __('admin.seo.support_hint') }}</p>
                <x-field :label="__('admin.seo.support_whatsapp')" name="general.support_whatsapp">
                    <x-input name="general[support_whatsapp]" :value="$values['general.support_whatsapp']" dir="ltr" maxlength="32"/>
                </x-field>
                <x-field :label="__('admin.seo.support_phone')" name="general.support_phone">
                    <x-input name="general[support_phone]" :value="$values['general.support_phone']" dir="ltr" maxlength="32"/>
                </x-field>
                <x-field :label="__('admin.seo.support_email')" name="general.support_email">
                    <x-input name="general[support_email]" type="email" :value="$values['general.support_email']" dir="ltr" maxlength="190"/>
                </x-field>

                <h2 class="pt-2 text-sm font-semibold text-ink-800">{{ __('admin.branding.heading') }}</h2>
                <x-image-field name="logo" :path="$values['branding.logo_path']" :label="__('admin.branding.logo')"/>
                <x-image-field name="favicon" :path="$values['branding.favicon_path']" :label="__('admin.branding.favicon')"/>
                <x-field :label="__('admin.branding.tagline_ar')" name="branding.tagline_ar">
                    <x-input name="branding[tagline_ar]" :value="$values['branding.tagline_ar']" maxlength="190"/>
                </x-field>
                <x-field :label="__('admin.branding.tagline_en')" name="branding.tagline_en">
                    <x-input name="branding[tagline_en]" :value="$values['branding.tagline_en']" dir="ltr" maxlength="190"/>
                </x-field>
                <h2 class="pt-2 text-sm font-semibold text-ink-800">{{ __('admin.branding.social') }}</h2>
                @foreach (['facebook', 'instagram', 'twitter', 'youtube', 'tiktok', 'linkedin'] as $network)
                    <x-field :label="$network" :name="'social.'.$network">
                        <x-input :name="'social['.$network.']'" :value="$values['social.'.$network]" dir="ltr" maxlength="500"/>
                    </x-field>
                @endforeach
            </div>
            <x-slot:footer>
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>
</x-layouts.admin>
