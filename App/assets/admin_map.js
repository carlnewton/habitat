require('leaflet');

var location;
var latLngStr = document.querySelector('#locationLatLng').value;
var map = L.map('map', {
    center: [
        latLngStr.substring(0, latLngStr.indexOf(',')),
        latLngStr.substring(latLngStr.indexOf(',') + 1, latLngStr.length)
    ],
    zoom: document.querySelector('#locationZoom').value,
});

L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
}).addTo(map);

setLocation();
setLocationRadiusField();

function setLocation() {
    latLngStr = document.querySelector('#locationLatLng').value;
    let latlng = L.latLng(
        latLngStr.substring(0, latLngStr.indexOf(',')),
        latLngStr.substring(latLngStr.indexOf(',') + 1, latLngStr.length)
    );

    if (location !== undefined && map.hasLayer(location)) {
        map.removeLayer(location);
    }
    location = L.circle(latlng, {
        color: '#0d6efd',
        fillColor: '#0d6efd',
        fillOpacity: 0.3,
        radius: document.querySelector('#locationRadiusMeters').value
    });

    map.addLayer(location);
}

function getGeolocation(position) {
    document.querySelector('#locationZoom').value = 10;
    map.setZoom(document.querySelector('#locationZoom').value);
    document.querySelector('#locationLatLng').value = position.coords.latitude + ',' + position.coords.longitude;
    map.panTo([
        position.coords.latitude,
        position.coords.longitude
    ]);
    setLocation();
}

function setLocationRadiusField() {
    let locationRadiusValue = document.querySelector('#locationRadiusMeters').value;
    switch (document.querySelector('#locationMeasurement').value) {
        case 'miles':
            document.querySelector('#measurement-kms').classList.remove('active');
            document.querySelector('#measurement-miles').classList.add('active');
            document.querySelector('#locationRadius').value = parseFloat(locationRadiusValue * 0.00062137).toPrecision(4);
            break;
        case 'km':
            document.querySelector('#measurement-miles').classList.remove('active');
            document.querySelector('#measurement-kms').classList.add('active');
            document.querySelector('#locationRadius').value = parseFloat(locationRadiusValue / 1000).toPrecision(4);
            break;
    }
}

function setMeasurement(value) {
    if (document.querySelector('#locationMeasurement').value === value) {
        return;
    }
    document.querySelector('#locationMeasurement').value = value;
    setLocationRadiusField();
}

document.querySelector('#locationRadius').onchange = function(e) {
    switch (document.querySelector('#locationMeasurement').value) {
        case 'km':
            document.querySelector('#locationRadiusMeters').value = Math.ceil(e.target.value * 1000);
            break;
        case 'miles':
            document.querySelector('#locationRadiusMeters').value = Math.ceil(e.target.value * 1609.34);
    }

    if (document.querySelector('#locationRadiusMeters').value < 1000 || document.querySelector('#locationRadiusMeters').value > 100000) {
        document.querySelector('#size-warning').classList.remove('d-none');
    } else {
        document.querySelector('#size-warning').classList.add('d-none');
    }

    location.setRadius(document.querySelector('#locationRadiusMeters').value);
}

document.querySelector('.get-location').onclick = function() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(getGeolocation);
    }
}

document.querySelector('#measurement-miles').onclick = function() {
    setMeasurement('miles');
}

document.querySelector('#measurement-kms').onclick = function() {
    setMeasurement('km');
}

map.on('click', function(e) {
    document.querySelector('#locationLatLng').value = parseFloat(e.latlng.lat).toPrecision(6) + ',' + parseFloat(e.latlng.lng).toPrecision(6);
    setLocation();
});

map.on('zoomend', function(e) {
    document.querySelector('#locationZoom').value = map.getZoom();
});
