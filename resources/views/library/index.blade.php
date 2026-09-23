<x-layouts.public :title="__('discover.library.heading')" :description="__('discover.library.lead')">
    <x-catalog-hero :title="__('discover.library.heading')" :subtitle="__('discover.library.lead')">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ __('discover.nav.library') }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-6xl px-4 py-8">
        <form method="GET" class="mb-6 flex flex-wrap gap-2">
            <a href="{{ route('library.index') }}" class="rounded-full px-3 py-1.5 text-sm {{ empty($filters['category']) ? 'bg-primary-600 text-white' : 'bg-white text-ink-700 ring-1 ring-ink-200' }}">{{ __('common.all') }}</a>
            @foreach ($categories as $category)
                <a href="{{ route('library.index', ['category' => $category->value]) }}" class="rounded-full px-3 py-1.5 text-sm {{ ($filters['category'] ?? '') === $category->value ? 'bg-primary-600 text-white' : 'bg-white text-ink-700 ring-1 ring-ink-200' }}">{{ $category->label() }}</a>
            @endforeach
        </form>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($articles as $article)
                <a href="{{ route('library.show', $article) }}" class="card overflow-hidden hover:ring-2 hover:ring-primary-200">
                    @if ($article->imageUrl())
                        <img src="{{ $article->imageUrl() }}" alt="" class="h-36 w-full object-cover">
                    @endif
                    <div class="p-4">
                        <p class="text-xs font-medium text-primary-700">{{ $article->category->label() }}</p>
                        <h2 class="mt-1 font-semibold text-ink-900">{{ $article->title }}</h2>
                        @if ($article->excerpt)
                            <p class="mt-2 text-sm text-ink-500">{{ $article->excerpt }}</p>
                        @endif
                    </div>
                </a>
            @empty
                <x-card class="sm:col-span-2 lg:col-span-3">
                    <x-empty-state :message="__('discover.library.empty')"/>
                </x-card>
            @endforelse
        </div>

        <div class="mt-6">{{ $articles->links() }}</div>
    </div>
</x-layouts.public>
