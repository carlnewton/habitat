function getGeolocation(position) {
    let latLng = parseFloat(position.coords.latitude).toPrecision(6).toString() + ',' + parseFloat(position.coords.longitude).toPrecision(6).toString();
    let postsList = document.querySelector('#posts-list');
    let hxPath = postsList.getAttribute('hx-get');
    postsList.setAttribute('hx-get', hxPath + '?latLng=' + latLng);
    htmx.process(postsList);
    htmx.trigger("#posts-list", "hasLocation")
}

if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(getGeolocation);
}

