<x-layouts.admin :title="__('admin.users.heading')">
    <x-page-header :title="__('admin.users.heading')" :subtitle="__('admin.users.subheading')"/>

    <form method="GET" class="mb-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
        <x-input name="q" :value="$filters['q'] ?? ''" :placeholder="__('common.search')"/>
        <x-select name="phone" :placeholder="__('admin.users.phone_any')" :selected="$filters['phone'] ?? ''"
                  :options="['verified' => __('admin.users.phone_verified'), 'unverified' => __('admin.users.phone_unverified')]"/>
        <x-select name="app" :placeholder="__('admin.users.app_any')" :selected="$filters['app'] ?? ''"
                  :options="['installed' => __('admin.users.app_yes'), 'missing' => __('admin.users.app_no')]"/>
        <label class="flex items-center gap-2 text-sm text-ink-600">
            <input type="checkbox" name="new" value="1" @checked(! empty($filters['new'])) class="size-4 rounded border-ink-300 text-primary-600">
            {{ __('admin.users.new') }}
        </label>
        <x-button variant="secondary">{{ __('common.filter') }}</x-button>
    </form>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('auth.name') }}</x-th>
            <x-th>{{ __('auth.phone') }}</x-th>
            <x-th>{{ __('admin.users.registered') }}</x-th>
            <x-th>{{ __('admin.users.phone_status') }}</x-th>
            <x-th>{{ __('admin.users.app') }}</x-th>
            <x-th>{{ __('admin.loyalty.balance') }}</x-th>
            <x-th>{{ __('admin.users.bookings') }}</x-th>
            <x-th>{{ __('common.actions') }}</x-th>
        </x-slot:head>
        @forelse ($users as $user)
            <tr>
                <x-td class="font-medium text-ink-900">{{ $user->name }}</x-td>
                <x-td dir="ltr">{{ $user->phone }}</x-td>
                <x-td class="text-xs text-ink-500">{{ $user->created_at?->format('Y-m-d') }}</x-td>
                <x-td><x-status-dot :active="$user->isPhoneVerified()"/></x-td>
                <x-td><x-status-dot :active="$user->hasInstalledApp()"/></x-td>
                <x-td class="tabular">{{ number_format((float) $user->wallet_balance, 2) }}</x-td>
                <x-td>{{ $user->bookings_count }}</x-td>
                <x-td><a href="{{ route('admin.users.show', $user) }}" class="text-sm font-medium text-primary-700 hover:underline">{{ __('common.view') }}</a></x-td>
            </tr>
        @empty
            <x-empty-state colspan="8"/>
        @endforelse
        @if ($users->hasPages())
            <x-slot:footer>{{ $users->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
