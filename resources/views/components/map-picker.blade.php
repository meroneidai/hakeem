@props([
    'lat' => null,
    'lng' => null,
    'addressInput' => 'patient_home_address',
])

<div class="space-y-2" data-map-picker>
    <p class="text-sm font-medium text-ink-700">{{ __('labs.checkout.map') }}</p>
    <p class="text-xs text-ink-500">{{ __('labs.checkout.map_hint') }}</p>
    <div class="flex flex-wrap gap-2">
        <button type="button" data-map-locate class="inline-flex items-center gap-1.5 rounded-full bg-primary-50 px-3 py-1.5 text-xs font-medium text-primary-800 hover:bg-primary-100">
            <x-icon name="map-pin" class="size-3.5"/>
            {{ __('labs.checkout.use_location') }}
        </button>
    </div>
    <div dir="ltr" class="h-64 w-full overflow-hidden rounded-xl border border-ink-200">
        <div data-map-canvas class="h-full w-full"></div>
    </div>
    <input type="hidden" name="latitude" id="home-latitude" value="{{ old('latitude', $lat) }}">
    <input type="hidden" name="longitude" id="home-longitude" value="{{ old('longitude', $lng) }}">
    @error('latitude')
        <p class="text-xs font-medium text-danger-500">{{ $message }}</p>
    @enderror
</div>

@pushOnce('head', 'leaflet-map-picker-css')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <style>
        [data-map-canvas].leaflet-container,
        [data-map-canvas] .leaflet-pane,
        [data-map-canvas] .leaflet-map-pane {
            direction: ltr;
            right: auto;
        }
    </style>
@endpushOnce
@pushOnce('scripts', 'leaflet-map-picker')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        const bootHomeMap = () => {
            const root = document.querySelector('[data-map-picker]');
            const canvas = root?.querySelector('[data-map-canvas]');
            const latInput = document.getElementById('home-latitude');
            const lngInput = document.getElementById('home-longitude');
            const locate = root?.querySelector('[data-map-locate]');
            const address = document.getElementById(@js($addressInput));

            if (! root || ! canvas || ! latInput || ! lngInput || typeof L === 'undefined' || root.dataset.mapReady === '1') {
                return;
            }

            root.dataset.mapReady = '1';

            const fallback = [30.0444, 31.2357];
            let map = null;
            let marker = null;
            let pendingPin = null;
            let waiting = false;

            const start = () => [
                parseFloat(pendingPin?.lat) || parseFloat(latInput.value) || fallback[0],
                parseFloat(pendingPin?.lng) || parseFloat(lngInput.value) || fallback[1],
            ];

            const fillAddress = (lat, lng) => {
                if (! address || address.value.trim() !== '') {
                    return;
                }

                fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng + '&accept-language=ar')
                    .then((response) => response.ok ? response.json() : null)
                    .then((payload) => {
                        if (payload?.display_name && address.value.trim() === '') {
                            address.value = payload.display_name;
                        }
                    })
                    .catch(() => {});
            };

            const sync = (latlng, geocode = true) => {
                latInput.value = latlng.lat.toFixed(7);
                lngInput.value = latlng.lng.toFixed(7);
                if (geocode) {
                    fillAddress(latlng.lat, latlng.lng);
                }
            };

            const applyPending = () => {
                if (! pendingPin || ! map || ! marker) {
                    return;
                }

                const latlng = L.latLng(parseFloat(pendingPin.lat), parseFloat(pendingPin.lng));
                marker.setLatLng(latlng);
                map.setView(latlng, 16);
                sync(latlng, false);
                pendingPin = null;
            };

            const ensure = () => {
                if (canvas.offsetWidth < 100) {
                    if (! waiting) {
                        waiting = true;
                        const wait = () => {
                            if (canvas.offsetWidth >= 100) {
                                waiting = false;
                                ensure();
                                return;
                            }

                            setTimeout(wait, 80);
                        };
                        wait();
                    }

                    return;
                }

                if (map) {
                    map.invalidateSize();
                    applyPending();
                    return;
                }

                const point = start();
                map = L.map(canvas).setView(point, (latInput.value || pendingPin) ? 16 : 12);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap',
                }).addTo(map);
                marker = L.marker(point, { draggable: true }).addTo(map);
                marker.on('dragend', () => sync(marker.getLatLng()));
                map.on('click', (event) => {
                    marker.setLatLng(event.latlng);
                    sync(event.latlng);
                });
                sync(marker.getLatLng(), ! pendingPin);
                setTimeout(() => {
                    map.invalidateSize();
                    applyPending();
                }, 200);
            };

            const move = (lat, lng) => {
                if (! lat || ! lng) {
                    return;
                }

                pendingPin = { lat, lng };
                ensure();
                applyPending();
            };

            window.addEventListener('hakeem-home-map', ensure);
            window.addEventListener('hakeem-home-pin', (event) => {
                move(event.detail?.lat, event.detail?.lng);
            });

            locate?.addEventListener('click', () => {
                if (! navigator.geolocation) {
                    return;
                }
                navigator.geolocation.getCurrentPosition((position) => {
                    move(position.coords.latitude, position.coords.longitude);
                    fillAddress(position.coords.latitude, position.coords.longitude);
                });
            });

            if (latInput.value && lngInput.value) {
                ensure();
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootHomeMap);
        } else {
            bootHomeMap();
        }

        const waitForLeaflet = (attempt = 0) => {
            if (typeof L !== 'undefined') {
                bootHomeMap();
                return;
            }

            if (attempt < 40) {
                setTimeout(() => waitForLeaflet(attempt + 1), 50);
            }
        };

        waitForLeaflet();
    </script>
@endpushOnce
