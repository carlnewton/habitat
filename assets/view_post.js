require('leaflet');

import PhotoSwipeLightbox from 'photoswipe/lightbox';
import 'photoswipe/style.css';

if (document.getElementById('map') !== null) {
    var location;
    var centerLatLng = document.getElementById('map').dataset.center;
    var centerLatLngArr = [
        centerLatLng.substring(0, centerLatLng.indexOf(',')),
        centerLatLng.substring(centerLatLng.indexOf(',') + 1, centerLatLng.length)
    ];
    var map = L.map('map', {
        center: centerLatLngArr,
        zoom: 19
    });

    var markerIcon = L.icon({
        iconUrl: '/build/images/marker-icon.2b3e1faf.png',
        iconSize:     [25, 41],
        iconAnchor:   [13, 41],
    })

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    let latLng = L.latLng(
        centerLatLngArr
    );

    location = L.marker(latLng, { icon: markerIcon });

    map.addLayer(location);
    map.setMaxBounds(latLng.toBounds(500));
}

if (document.getElementById('gallery') !== null) {
    const lightbox = new PhotoSwipeLightbox({
        gallery: '#gallery',
        children: 'a',
        pswpModule: () => import('photoswipe'),
    });

    lightbox.init();
}
