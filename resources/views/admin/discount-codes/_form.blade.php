@php
    $typeOptions = [
        'percentage' => __('admin.discount_codes.percentage'),
        'fixed' => __('admin.discount_codes.fixed'),
    ];
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <x-field :label="__('admin.discount_codes.code')" name="code" required>
        <x-input name="code" :value="$code->code" dir="ltr" class="uppercase" placeholder="LAUNCH25"/>
    </x-field>

    <x-field :label="__('common.description_ar')" name="description">
        <x-input name="description" :value="$code->description"/>
    </x-field>

    <x-field :label="__('admin.discount_codes.discount_type')" name="discount_type" required>
        <x-select name="discount_type" :options="$typeOptions" :selected="$code->discount_type"/>
    </x-field>

    <x-field :label="__('admin.discount_codes.discount_value')" name="discount_value" required>
        <x-input name="discount_value" type="number" step="0.01" min="0" :value="$code->discount_value"/>
    </x-field>

    <x-field :label="__('admin.discount_codes.plan')" name="subscription_plan_id">
        <x-select name="subscription_plan_id" :placeholder="__('admin.discount_codes.all_plans')"
                  :options="$plans->pluck('name', 'id')->all()" :selected="$code->subscription_plan_id"/>
    </x-field>

    <x-field :label="__('admin.discount_codes.max_uses')" name="max_uses" :hint="__('admin.plans.cap_hint')">
        <x-input name="max_uses" type="number" min="1" :value="$code->max_uses"/>
    </x-field>

    <x-field :label="__('admin.discount_codes.valid_from')" name="valid_from">
        <x-input name="valid_from" type="datetime-local"
                 :value="$code->valid_from?->format('Y-m-d\TH:i')"/>
    </x-field>

    <x-field :label="__('admin.discount_codes.valid_to')" name="valid_to">
        <x-input name="valid_to" type="datetime-local"
                 :value="$code->valid_to?->format('Y-m-d\TH:i')"/>
    </x-field>
</div>

<div class="mt-4">
    <x-checkbox name="is_active" :label="__('common.is_active')" :checked="$code->is_active ?? true"/>
</div>
