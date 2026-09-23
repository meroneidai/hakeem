<x-layouts.admin :title="__('admin.reviews.heading')">
    <x-page-header :title="__('admin.reviews.heading')" :subtitle="__('admin.reviews.subheading')">
        <x-slot:actions>
            <x-button :href="route('admin.reviews.index', ['hidden' => 1])" variant="secondary" size="sm">{{ __('admin.reviews.hidden') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table>
        <x-slot:head>
            <x-th>{{ __('admin.reviews.patient') }}</x-th>
            <x-th>{{ __('admin.reviews.target') }}</x-th>
            <x-th>{{ __('admin.reviews.score') }}</x-th>
            <x-th>{{ __('admin.reviews.body') }}</x-th>
            <x-th>{{ __('common.actions') }}</x-th>
        </x-slot:head>
        @forelse ($reviews as $review)
            <tr>
                <x-td>{{ $review->patient?->name }}</x-td>
                <x-td>
                    <p class="font-medium">{{ $review->doctor?->name }}</p>
                    <p class="text-xs text-ink-400">{{ $review->clinic?->name }}</p>
                </x-td>
                <x-td>
                    <x-rating :average="$review->overall" :count="1"/>
                </x-td>
                <x-td class="max-w-sm text-sm text-ink-600">{{ $review->body }}</x-td>
                <x-td>
                    <form method="POST" action="{{ route('admin.reviews.update', $review) }}" class="flex flex-wrap gap-2">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="is_visible" value="{{ $review->is_visible ? 0 : 1 }}">
                        <x-button size="sm" variant="secondary">
                            {{ $review->is_visible ? __('admin.reviews.hide') : __('admin.reviews.show') }}
                        </x-button>
                    </form>
                </x-td>
            </tr>
        @empty
            <x-empty-state colspan="5"/>
        @endforelse
        @if ($reviews->hasPages())
            <x-slot:footer>{{ $reviews->links() }}</x-slot:footer>
        @endif
    </x-table>
</x-layouts.admin>
