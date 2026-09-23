@props(['selected' => null, 'providers', 'hint' => null])

<x-field :label="__('account.insurance')" name="insurance_provider_id" :hint="$hint ?? __('account.insurance_hint')">
    <x-select
        name="insurance_provider_id"
        :placeholder="__('account.insurance_none')"
        :selected="$selected"
        :options="$providers->mapWithKeys(fn ($provider) => [$provider->id => $provider->name])->all()"
    />
</x-field>
