(function () {
  "use strict";

  /* basic map */
  var map = new GMaps({
    el: '#google-map',
    lat: -12.043333,
    lng: -77.028333
  });

  /* overlay map */
  var map1 = new GMaps({
    el: '#google-map-overlay',
    lat: -12.043333,
    lng: -77.028333
  });
  map1.drawOverlay({
    lat: map1.getCenter().lat(),
    lng: map1.getCenter().lng(),
    layer: 'overlayLayer',
    content: '<div class="google-map-overlay">Lima<div class="google-overlay_arrow above"></div></div>',
    verticalAlign: 'top',
    horizontalAlign: 'center'
  });

  /* layeres map */
  var map2 = new GMaps({
    el: "#map-layers",
    lat: -12.043333,
    lng: -77.028333,
    zoom: 3
  });

  /* map with markers */
  var map3 = new GMaps({
    el: '#map-markers',
    lat: -12.043333,
    lng: -77.028333
  });
  map3.addMarker({
    lat: -12.043333,
    lng: -77.03,
    title: 'Lima',
    details: {
      database_id: 42,
      author: 'HPNeo'
    },
    click: function (e) {
      if (console.log)
        console.log(e);
      alert('You clicked in this marker');
    },
    mouseover: function (e) {
      if (console.log)
        console.log(e);
    }
  });
  map3.addMarker({
    lat: -12.042,
    lng: -77.028333,
    title: 'Marker with InfoWindow',
    infoWindow: {
      content: '<p>HTML Content</p>'
    }
  });

  /* streetview panaroma map */
  GMaps.createPanorama({
    el: '#streetview-map',
    lat: 42.3455,
    lng: -71.0983,
    pov: {
      heading: 60,
      pitch: -10,
      zoom: 1
    }
  });

  /* geo fencing map */
  var map4 = new GMaps({
    el: '#map-geofencing',
    lat: -12.043333,
    lng: -77.028333
  });
  var path = [
    [-12.040397656836609, -77.03373871559225],
    [-12.040248585302038, -77.03993927003302],
    [-12.050047116528843, -77.02448169303511],
    [-12.044804866577001, -77.02154422636042]
  ];
  var polygon = map4.drawPolygon({
    paths: path,
    strokeColor: '#e82646',
    strokeOpacity: 1,
    strokeWeight: 3,
    fillColor: '#e82646',
    fillOpacity: 0.5
  });/*  */
  var circle = map4.drawCircle({
    lat: -12.040504866577001,
    lng: -77.02024422636042,
    radius: 350,
    strokeColor: '#f7b731',
    strokeOpacity: 1,
    strokeWeight: 3,
    fillColor: '#f7b731',
    fillOpacity: 0.5
  });

})();