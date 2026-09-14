@php
    // Arrows follow reading direction, so they flip with the locale.
    $isRtl = in_array(app()->getLocale(), ['ar'], true);
    $prevIcon = $isRtl ? 'chevron-right' : 'chevron-left';
    $nextIcon = $isRtl ? 'chevron-left' : 'chevron-right';

    $box = 'inline-flex items-center justify-center min-w-9 h-9 px-3 text-sm border border-ink-300 first:rounded-s-lg last:rounded-e-lg -ms-px first:ms-0';
    $idle = $box.' bg-white text-ink-700 hover:bg-ink-50 hover:text-ink-900 transition';
    $active = $box.' bg-primary-600 border-primary-600 text-white font-semibold z-10';
    $disabled = $box.' bg-ink-100 text-ink-400 cursor-not-allowed';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('common.pagination.nav') }}"
         class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-ink-500 tabular">
            {{ __('common.pagination.showing', [
                'first' => $paginator->firstItem(),
                'last' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ]) }}
        </p>

        <div class="flex tabular" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
            @if ($paginator->onFirstPage())
                <span class="{{ $disabled }}" aria-disabled="true" aria-label="{{ __('common.pagination.previous') }}">
                    <x-icon :name="$prevIcon" class="size-4" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $idle }}"
                   aria-label="{{ __('common.pagination.previous') }}">
                    <x-icon :name="$prevIcon" class="size-4" />
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="{{ $disabled }}" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="{{ $active }}" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="{{ $idle }}"
                               aria-label="{{ __('common.pagination.go_to_page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $idle }}"
                   aria-label="{{ __('common.pagination.next') }}">
                    <x-icon :name="$nextIcon" class="size-4" />
                </a>
            @else
                <span class="{{ $disabled }}" aria-disabled="true" aria-label="{{ __('common.pagination.next') }}">
                    <x-icon :name="$nextIcon" class="size-4" />
                </span>
            @endif
        </div>
    </nav>
@endif
