require('leaflet');

if (document.getElementById('about-map') !== null) {
    var mapEl = document.getElementById('about-map');
    var latLngStr = mapEl.dataset.center;
    var zoom = parseInt(mapEl.dataset.zoom);
    var radius = parseInt(mapEl.dataset.radius);

    var centerLatLng = L.latLng(
        latLngStr.substring(0, latLngStr.indexOf(',')),
        latLngStr.substring(latLngStr.indexOf(',') + 1, latLngStr.length)
    );

    var map = L.map('about-map', {
        center: centerLatLng,
        zoom: zoom,
        zoomControl: false,
        dragging: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        boxZoom: false,
        keyboard: false,
        touchZoom: false,
    });

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    L.circle(centerLatLng, {
        color: '#177bba',
        fillColor: '#177bba',
        fillOpacity: 0.3,
        radius: radius
    }).addTo(map);
}
