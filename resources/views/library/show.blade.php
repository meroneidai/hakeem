<x-layouts.public :title="$article->title" :description="$article->excerpt">
    <x-catalog-hero :title="$article->title" :subtitle="$article->excerpt">
        <x-slot:crumbs>
            <a href="{{ route('home') }}" class="hover:text-primary-700">{{ __('discover.nav.home') }}</a>
            <span aria-hidden="true">·</span>
            <a href="{{ route('library.index') }}" class="hover:text-primary-700">{{ __('discover.nav.library') }}</a>
            <span aria-hidden="true">·</span>
            <span class="text-ink-700">{{ $article->category->label() }}</span>
        </x-slot:crumbs>
    </x-catalog-hero>

    <div class="mx-auto max-w-3xl space-y-8 px-4 py-8">
        @if ($article->imageUrl())
            <img src="{{ $article->imageUrl() }}" alt="" class="w-full rounded-3xl object-cover">
        @endif

        <x-card>
            <div class="rich-content text-sm leading-8 text-ink-700">{!! \App\Support\SafeHtml::render($article->body) !!}</div>
            <p class="mt-6 text-xs text-ink-400">{{ __('pages.disclaimer.body') }}</p>
        </x-card>

        @if ($related->isNotEmpty())
            <section>
                <h2 class="mb-3 text-lg font-semibold text-ink-900">{{ __('discover.library.related') }}</h2>
                <div class="grid gap-3">
                    @foreach ($related as $item)
                        <a href="{{ route('library.show', $item) }}" class="card p-4 hover:ring-2 hover:ring-primary-200">
                            <p class="text-xs text-primary-700">{{ $item->category->label() }}</p>
                            <p class="font-semibold text-ink-900">{{ $item->title }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.public>
