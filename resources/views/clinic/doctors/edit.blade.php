<x-layouts.clinic :title="__('clinic.doctors.edit')">
    <x-page-header :title="__('clinic.doctors.edit')" :subtitle="$doctor->name"/>

    <form method="POST" action="{{ route('clinic.doctors.update', $doctor) }}">
        @csrf
        @method('PUT')
        <x-card class="max-w-3xl">
            @include('clinic.doctors._form')
            <x-slot:footer>
                <x-button :href="route('clinic.doctors.index')" variant="ghost">{{ __('common.cancel') }}</x-button>
                @if ($clinicAccess->canManage() && $clinic->doctors->count() > 1)
                    <x-button form="delete-doctor" variant="danger-ghost">{{ __('common.delete') }}</x-button>
                @endif
                <x-button variant="accent">{{ __('common.save_changes') }}</x-button>
            </x-slot:footer>
        </x-card>
    </form>

    @if ($clinicAccess->canManage() && $clinic->doctors->count() > 1)
        <form id="delete-doctor" method="POST" action="{{ route('clinic.doctors.destroy', $doctor) }}"
              onsubmit="return confirm(@json(__('common.confirm_delete')))">
            @csrf
            @method('DELETE')
        </form>
    @endif
</x-layouts.clinic>
