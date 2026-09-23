@if (($tracking ?? null)?->gtmContainerId())
    <noscript>
        <iframe src="https://www.googletagmanager.com/ns.html?id={{ $tracking->gtmContainerId() }}"
                height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
@endif
