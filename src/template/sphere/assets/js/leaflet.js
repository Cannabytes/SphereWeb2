(function () {
    "use strict";

    /* default map */
    var map = L.map('map').setView([51.505, -0.09], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    /* maps with markers circles and polygons */
    var shapesmap = L.map('map1').setView([51.505, -0.09], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© OpenStreetMap'
    }).addTo(shapesmap);
    var marker = L.marker([51.5, -0.09]).addTo(shapesmap);
    var circle = L.circle([51.508, -0.11], {
        color: '#05c3fb',
        fillColor: '#05c3fb',
        fillOpacity: 0.5,
        radius: 500
    }).addTo(shapesmap);
    var polygon = L.polygon([
        [51.509, -0.08],
        [51.503, -0.06],
        [51.51, -0.047]
    ], {
        color: "#e82646",
        fillColor: "#e82646"
    }).addTo(shapesmap);

    /* maps with popup */
    var popupmap = L.map('map-popup').setView([51.505, -0.09], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© OpenStreetMap'
    }).addTo(popupmap);
    var marker = L.marker([51.5, -0.09]).addTo(popupmap);
    var circle = L.circle([51.508, -0.11], {
        color: '#ffc102',
        fillColor: '#ffc102',
        fillOpacity: 0.5,
        radius: 500
    }).addTo(popupmap);
    var polygon = L.polygon([
        [51.509, -0.08],
        [51.503, -0.06],
        [51.51, -0.047]
    ], {
        color: "#5b67c7",
        fillColor: "#5b67c7"
    }).addTo(popupmap);
    marker.bindPopup("<b>Hello world!</b><br>I am a popup.").openPopup();
    circle.bindPopup("I am a circle.");
    polygon.bindPopup("I am a polygon.");
    var popup = L.popup()
        .setLatLng([51.513, -0.09])
        .setContent("I am a standalone popup.")
        .openOn(popupmap);

    /* maps with custom icon */
    var customicon = L.map('map-custom-icon').setView([51.505, -0.09], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© OpenStreetMap'
    }).addTo(customicon);
    var greenIcon = L.icon({
        iconUrl: '../assets/images/brand-logos/desktop-logo.png',
        iconSize: [80, 25], // size of the icon
        iconAnchor: [22, 94], // point of the icon which will correspond to marker's location
        popupAnchor: [-3, -76] // point from which the popup should open relative to the iconAnchor
    });
    L.marker([51.5, -0.09], { icon: greenIcon }).addTo(customicon);

    /* interactive chloropleth map */
    var geomap = L.map('interactive-map').setView([37.8, -96], 4);
    var tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(geomap);
    // L.geoJson(statesData).addTo(geomap);
    function getColor(d) {
        return d > 1000 ? '#800026' :
            d > 500 ? '#BD0026' :
                d > 200 ? '#E31A1C' :
                    d > 100 ? '#FC4E2A' :
                        d > 50 ? '#FD8D3C' :
                            d > 20 ? '#FEB24C' :
                                d > 10 ? '#FED976' :
                                    '#FFEDA0';
    }
    function style(feature) {
        return {
            fillColor: getColor(feature.properties.density),
            weight: 2,
            opacity: 1,
            color: 'white',
            dashArray: '3',
            fillOpacity: 0.7,
            // fillColor: '#fff'
        };
    }
    // L.geoJson(statesData, { style: style }).addTo(geomap);
    function highlightFeature(e) {
        var layer = e.target;
        layer.setStyle({
            weight: 5,
            color: '#666',
            dashArray: '',
            fillOpacity: 0.7
        });
        if (!L.Browser.ie && !L.Browser.opera && !L.Browser.edge) {
            layer.bringToFront();
        }
    }
    function resetHighlight(e) {
        geojson.resetStyle(e.target);
    }
    function zoomToFeature(e) {
        map.fitBounds(e.target.getBounds());
    }

})();