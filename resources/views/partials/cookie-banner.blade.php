<div
    x-data="{
        accepted: false,
        init() {
            this.accepted = window.localStorage.getItem('hakeem.cookies') === '1';
        },
        accept() {
            window.localStorage.setItem('hakeem.cookies', '1');
            this.accepted = true;
        },
    }"
    x-cloak
    x-show="! accepted"
    class="fixed inset-x-3 bottom-24 z-[70] rounded-2xl bg-primary-900 p-4 text-white shadow-xl ring-1 ring-primary-700/60 sm:bottom-6 sm:max-w-lg lg:bottom-6"
    role="dialog"
    aria-live="polite"
>
    <p class="text-sm leading-6 text-primary-100">{{ __('pages.cookies.banner') }}</p>
    <div class="mt-3 flex flex-wrap items-center gap-2">
        <a href="{{ route('cookies') }}" class="text-xs font-medium text-primary-200 underline-offset-2 hover:text-white hover:underline">{{ __('pages.cookies.heading') }}</a>
        <button type="button"
                class="ms-auto inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-1.5 text-sm font-medium text-primary-900 shadow-sm transition hover:bg-primary-50"
                @click="accept()">
            <x-icon name="check" class="size-4 text-primary-700"/>
            {{ __('pages.cookies.accept') }}
        </button>
    </div>
</div>
