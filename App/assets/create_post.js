require('leaflet');

import Dropzone from "dropzone";
import 'dropzone/dist/dropzone.css';

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
    opacity: 0.4,
    radius: document.querySelector('#map').dataset.radius,
    fill: false,
});

map.addLayer(perimeter);

map.setMaxBounds(perimeter.getBounds().pad(0.3));

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

let dropzone = new Dropzone(".dropzone", { 
    url: "post/upload",
    maxFilesize: 99,
    acceptedFiles: "image/*",
    addRemoveLinks: true,
    dictDefaultMessage: "<i class=\"bi fs-2 bi-upload\"></i><br>Add photos",
    success: function (file, response) {
        let attachmentIdString = document.getElementById('attachmentIds').value;
        let attachmentIdArray = attachmentIdString.split(',');
        attachmentIdArray.push(response.id);
        file.attachmentId = response.id;
        document.getElementById('attachmentIds').value = attachmentIdArray.join(',');
    },
    removedfile: function(file) {
        let attachmentIdString = document.getElementById('attachmentIds').value;
        let attachmentIdArray = attachmentIdString.split(',');
        let attachmentIndex = attachmentIdArray.indexOf(String(file.attachmentId));
        if (attachmentIndex > -1) {
            attachmentIdArray.splice(attachmentIndex, 1);
            document.getElementById('attachmentIds').value = attachmentIdArray.join(',');
            if (file.previewElement != null && file.previewElement.parentNode != null) {
                file.previewElement.parentNode.removeChild(file.previewElement);
            }
            /*
             * NOTE: There's a visual bug here. When removing a file after existing files have been displayed (eg after
             * the form has been posted and the page has been refreshed to display errors) the upload icon will appear
             * above the icons again.
             *
             * NOTE: It would be good to delete files from the server immediately when they're removed here. I'm
             * currently planning to rely mostly on a nightly clean-up scheduled task, but if I do opt in for removal,
             * authorisation will need to be considered, as well as how to handle updating the post if it's decided
             * that I'll allow updates to happen. eg. if editing the post, the attachments should only be removed upon
             * saving the changes, rather than on deletion.
             */
            return this._updateMaxFilesReachedClass();
        }
    }
});

let existingAttachmentIds = document.getElementById('attachmentIds').value;
if (existingAttachmentIds.length > 0) {
    let existingAttachmentIdArray = existingAttachmentIds.split(',');
    for (let i = 0; i < existingAttachmentIdArray.length; i++) {
        if (existingAttachmentIdArray[i].length === 0) {
            continue;
        }

        dropzone.displayExistingFile({ name: 'Uploaded file', attachmentId: existingAttachmentIdArray[i] }, '/attachment/unposted/' + existingAttachmentIdArray[i])
    }
}
