@props(['footer' => false])

<div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
    @if ($footer)
        <span class="footer-mesh"></span>
    @else
        <span class="hero-mesh"></span>
    @endif
    <span @class(['hero-blob hero-blob-a', 'footer-blob' => $footer])></span>
    <span @class(['hero-blob hero-blob-b', 'footer-blob' => $footer])></span>
    <span @class(['hero-blob hero-blob-c', 'footer-blob' => $footer])></span>
    <span @class(['hero-blob hero-blob-d', 'footer-blob' => $footer])></span>
</div>
