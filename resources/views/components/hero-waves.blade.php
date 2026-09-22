@props(['footer' => false])

<div @class(['footer-wave' => $footer, 'hero-wave' => ! $footer]) aria-hidden="true">
    @if ($footer)
        <svg class="hero-wave-svg hero-wave-svg-back" viewBox="0 0 2880 120" preserveAspectRatio="none">
            <path fill="#d5e6f3" d="M0,0 L2880,0 L2880,56 C2640,96 2400,18 2160,64 C1920,110 1680,20 1440,56 C1200,96 960,18 720,64 C480,110 240,20 0,72 Z"/>
        </svg>
        <svg class="hero-wave-svg hero-wave-svg-front" viewBox="0 0 2880 90" preserveAspectRatio="none">
            <path fill="#f8fafb" d="M0,0 L2880,0 L2880,48 C2640,16 2400,72 2160,40 C1920,8 1680,90 1440,48 C1200,16 960,72 720,40 C480,8 240,90 0,48 Z"/>
        </svg>
    @else
        <svg class="hero-wave-svg hero-wave-svg-back" viewBox="0 0 2880 120" preserveAspectRatio="none">
            <path fill="#9fd0b8" d="M0,72 C240,20 480,110 720,64 C960,18 1200,96 1440,56 C1680,20 1920,110 2160,64 C2400,18 2640,96 2880,56 L2880,120 L0,120 Z"/>
        </svg>
        <svg class="hero-wave-svg hero-wave-svg-front" viewBox="0 0 2880 90" preserveAspectRatio="none">
            <path fill="#f8fafb" d="M0,48 C240,90 480,8 720,40 C960,72 1200,16 1440,48 C1680,90 1920,8 2160,40 C2400,72 2640,16 2880,48 L2880,90 L0,90 Z"/>
        </svg>
    @endif
</div>
