<x-layouts.admin :title="__('admin.map.heading')">
    <x-page-header :title="__('admin.map.heading')" :subtitle="__('admin.map.subheading')"/>

    <div id="clinics-map" class="h-[32rem] overflow-hidden rounded-2xl border border-ink-200"></div>

    @push('head')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <script>
            const pins = @json($pins);
            const map = L.map('clinics-map').setView([26.8, 30.8], 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
            const bounds = [];
            pins.forEach((pin) => {
                const marker = L.marker([pin.lat, pin.lng]).addTo(map);
                marker.bindPopup(`<a href="${pin.url || '#'}">${pin.name || ''} — ${pin.branch || ''}</a>`);
                bounds.push([pin.lat, pin.lng]);
            });
            if (bounds.length) {
                map.fitBounds(bounds, { padding: [24, 24] });
            }
        </script>
    @endpush
</x-layouts.admin>
