require('leaflet');

var location;
var centerLatLng = document.querySelector('#map').dataset.center;
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
