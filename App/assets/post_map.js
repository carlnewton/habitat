require('leaflet');

var location;
var centerLatLng = document.querySelector('#map').dataset.center;
var centerLatLngArr = [
    centerLatLng.substring(0, centerLatLng.indexOf(',')),
    centerLatLng.substring(centerLatLng.indexOf(',') + 1, centerLatLng.length)
];
var map = L.map('map', {
    center: centerLatLngArr,
    zoom: document.querySelector('#map').dataset.zoom,
});

var perimeter = L.circle(centerLatLngArr, {
    color: '#000',
    opacity: 0.2,
    radius: document.querySelector('#map').dataset.radius,
    fill: false,
});

map.addLayer(perimeter);

var markerIcon = L.icon({
    iconUrl: 'build/images/marker-icon.2b3e1faf.png',
    iconSize:     [25, 41],
    iconAnchor:   [13, 41],
})

L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

setLocation();

function setLocation() {
    if (location !== undefined && map.hasLayer(location)) {
        map.removeLayer(location);
    }

    let latLngStr = document.querySelector('#locationLatLng').value;
    let latLng = L.latLng(
        latLngStr.substring(0, latLngStr.indexOf(',')),
        latLngStr.substring(latLngStr.indexOf(',') + 1, latLngStr.length)
    );

    location = L.marker(latLng, { icon: markerIcon });

    map.addLayer(location);
}

function withinPerimeter(latLng) {
    if (latLng.distanceTo(L.latLng(centerLatLngArr)) > document.querySelector('#map').dataset.radius) {
        return false;
    }

    return true;
}

function getGeolocation(position) {
    let latLng = L.latLng(parseFloat(position.coords.latitude).toPrecision(6), parseFloat(position.coords.longitude).toPrecision(6));
    if (!withinPerimeter(latLng)) {
        return;
    }

    document.querySelector('#locationLatLng').value = position.coords.latitude + ',' + position.coords.longitude;
    map.panTo([
        position.coords.latitude,
        position.coords.longitude
    ]);

    setLocation();
}

document.querySelector('.get-location').onclick = function() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(getGeolocation);
    }
}

map.on('click', function(e) {
    let latLng = L.latLng(parseFloat(e.latlng.lat).toPrecision(6), parseFloat(e.latlng.lng).toPrecision(6));
    if (!withinPerimeter(latLng)) {
        return;
    }
    document.querySelector('#locationLatLng').value = parseFloat(e.latlng.lat).toPrecision(6) + ',' + parseFloat(e.latlng.lng).toPrecision(6);
    setLocation();
});
