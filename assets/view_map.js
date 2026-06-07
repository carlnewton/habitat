require('leaflet');

if (document.getElementById('map') !== null) {
    var location;
    var watchPosition;
    var myLocationEnabled = false;
    var myLocationRefresh = false;
    var relativeDistanceControl;
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
        iconUrl: '/build/images/marker_icon.png',
        iconSize:     [30, 41],
        iconAnchor:   [15, 41],
    })

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    L.Control.MyLocation = L.Control.extend({
        onAdd: function(map) {
            viewLocationButton = L.DomUtil.create('button', 'leaflet-btn');

            viewLocationButton.id = 'view-location-btn';
            viewLocationButton.innerHTML = '<i class="bi bi-compass"></i>';
            viewLocationButton.title = document.getElementById('map').dataset.showcurrentlocation;
            viewLocationButton.setAttribute('data-test', 'current-location-btn')

            return viewLocationButton;
        },

        onRemove: function(map) {
        }
    })
    L.control.MyLocation = function(opts) {
        return new L.Control.MyLocation(opts);
    }

    L.control.MyLocation({
        position: 'bottomleft'
    }).addTo(map);

    let latLng = L.latLng(
        centerLatLngArr
    );

    location = L.marker(latLng, { icon: markerIcon });

    map.addLayer(location);
    map.setMaxBounds(latLng.toBounds(500));

    document.getElementById('view-location-btn').onclick = function() {
        myLocationEnabled = !myLocationEnabled;
        if (myLocationEnabled) {
            viewLocationButton.title = document.getElementById('map').dataset.hidecurrentlocation;
            document.getElementById('view-location-btn').classList.add('leaflet-btn-active');
            watchPosition = navigator.geolocation.watchPosition(setCurrentPosition, failCurrentPosition);
        } else {
            navigator.geolocation.clearWatch(watchPosition);
            disableCurrentLocation();
        }
    }

    function centerOnBounds(bounds) {
        map.panTo(bounds.getCenter());
        map.setMaxBounds(bounds.pad(0.5));
        map.fitBounds(bounds);
    }

    function centerOnMarker() {
        map.removeLayer(myLocation);
        map.removeLayer(line);
        map.setMaxBounds(latLng.toBounds(500));
        map.panTo(latLng);
    }

    function failCurrentPosition(err) {
        switch (err.code) {
            case 1:
                alert(document.getElementById('map').dataset.error);
                break;
            default:
                alert(err.message);
        }

        disableCurrentLocation();
    }

    function disableCurrentLocation() {
        viewLocationButton.title = document.getElementById('map').dataset.showcurrentlocation;
        document.getElementById('view-location-btn').classList.remove('leaflet-btn-active');

        if (relativeDistanceControl !== undefined) {
            relativeDistanceControl.remove();
            relativeDistanceControl = undefined;
        }
        myLocationRefresh = false;
        centerOnMarker();
    }

    function setCurrentPosition(position) {
        if (!myLocationEnabled) {
            return;
        }

        var currentLatLng = L.latLng(parseFloat(position.coords.latitude).toPrecision(6), parseFloat(position.coords.longitude).toPrecision(6));

        var locationIcon = L.icon({
            iconUrl: '/build/images/location_icon.png',
            iconSize:     [30, 30],
            iconAnchor:   [15, 15],
            className: 'my-location-icon',
        })


        let bounds = L.latLngBounds(latLng, currentLatLng);
        if (myLocationRefresh) {
            myLocation.setLatLng(currentLatLng);
            map.removeLayer(line);
            line = undefined;
            distanceIndicator.innerHTML = printDistance(map.distance(currentLatLng, latLng));
            // TODO:
            // When the user is moving, it would be nice to center on the bounds of the target and their location
            // but only if they haven't panned or zoomed - because otherwise that would be a frustrating experience.
            // This is difficult because leaflet cannot currently discern between automated and manual pan/zoom events.
            // Once this is available, we can implement it: https://github.com/Leaflet/Leaflet/pull/6929
            // centerOnBounds(bounds);
        } else {
            myLocation = L.marker(currentLatLng, { icon: locationIcon, zIndexOffset: -1000 });
            map.addLayer(myLocation);
            myLocationRefresh = true;
            centerOnBounds(bounds);

            L.Control.RelativeDistance = L.Control.extend({
                onAdd: function(map) {
                    distanceIndicator = L.DomUtil.create('div', 'bg-light rounded shadow-sm opacity-75 p-1 fs-6');

                    distanceIndicator.id = 'distanceIndicator';
                    distanceIndicator.title = document.getElementById('map').dataset.showcurrentlocation;
                    distanceIndicator.innerHTML = printDistance(map.distance(currentLatLng, latLng));
                    distanceIndicator.setAttribute('data-test', 'distance-indicator')

                    return distanceIndicator;
                },

                onRemove: function(map) {
                }
            })
            L.control.RelativeDistance = function(opts) {
                return new L.Control.RelativeDistance(opts);
            }

            relativeDistanceControl = L.control.RelativeDistance({
                position: 'topright'
            }).addTo(map);
        }

        line = L.polyline([latLng, currentLatLng], { color: 'white', weight: 6, dashArray: '10,14', className: 'location-path' });
        line.addTo(map);
    }

    function printDistance(meters) {
        let distance;
        if (document.getElementById('map').dataset.measurementtype === 'km') {
            distance = parseFloat(meters / 1000).toFixed(1);
        } else {
            distance = parseFloat(meters * 0.000621371192).toFixed(1);
        }

        if (distance % 1 === 0) {
            distance = Math.round(distance);
        }

        return distance + ' ' + document.getElementById('map').dataset.measurement + ' ' + document.getElementById('map').dataset.away;
    }
}

