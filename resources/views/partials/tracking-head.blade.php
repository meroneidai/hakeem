@if (($tracking ?? null)?->searchConsoleVerification())
    <meta name="google-site-verification" content="{{ $tracking->searchConsoleVerification() }}">
@endif
@if (($tracking ?? null)?->gtmContainerId())
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $tracking->gtmContainerId() }}');</script>
@endif
@if (($tracking ?? null)?->gaMeasurementId() || ($tracking ?? null)?->googleAdsId())
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $tracking->gaMeasurementId() ?: $tracking->googleAdsId() }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        @if ($tracking->gaMeasurementId())
            gtag('config', '{{ $tracking->gaMeasurementId() }}');
        @endif
        @if ($tracking->googleAdsId())
            gtag('config', '{{ $tracking->googleAdsId() }}');
        @endif
    </script>
@endif
