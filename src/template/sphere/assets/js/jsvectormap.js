(function () {
    "use strict";

    /* basic vector map */
    var map = new jsVectorMap({
        selector: "#vector-map",
        map: "world_merc",
    });

    /* map with markers */
    var markers = [{
        name: 'Russia',
        coords: [61, 105],
        style: {
            fill: '#5c5cff'
        }
    },
    {
        name: 'Greenland',
        coords: [72, -42],
        style: {
            fill: '#ff9251'
        }
    },
    {
        name: 'Canada',
        coords: [56, -106],
        style: {
            fill: '#56de80'
        }
    },
    {
        name: 'Palestine',
        coords: [31.5, 34.8],
        style: {
            fill: 'yellow'
        }
    },
    {
        name: 'Brazil',
        coords: [-14.2350, -51.9253],
        style: {
            fill: '#000'
        }
    },
    ];

    var map = new jsVectorMap({
        map: 'world_merc',
        selector: '#marker-map',
        markersSelectable: true,
        // markersSelectableOne: true,

        onMarkerSelected(index, isSelected, selectedMarkers) {
            console.log(index, isSelected, selectedMarkers);
        },

        // -------- Labels --------
        labels: {
            markers: {
                render: function (marker) {
                    return marker.name
                },
            },
        },

        // -------- Marker and label style --------
        markers: markers,
        markerStyle: {
            hover: {
                stroke: "#DDD",
                strokeWidth: 3,
                fill: '#FFF'
            },
            selected: {
                fill: '#ff525d'
            }
        },
        markerLabelStyle: {
            initial: {
                fontFamily: 'Poppins',
                fontSize: 13,
                fontWeight: 500,
                fill: '#35373e',
            },
        },
    })

    /* map with image markers */
    var markers = [
        {
            name: 'Palestine',
            coords: [31.5, 34.8],
        },
        {
            name: 'Russia',
            coords: [61, 105],
        },
        {
            name: 'Greenland',
            coords: [72, -42],
        },
        {
            name: 'Canada',
            coords: [56, -106],
        },
    ];
    var map = new jsVectorMap({
        map: 'world_merc',
        selector: '#marker-image-map',

        labels: {
            markers: {
                render: function (marker) {
                    return marker.name
                }
            }
        },
        markers: markers,
        markerStyle: {
            initial: {
                image: true
            }
        },
        series: {
            markers: [{
                attribute: 'image',
                scale: {
                    marker1title: {
                        url: '../assets/images/brand-logos/toggle-logo.png',
                        offset: [10, 0]
                    },
                    marker2title: {
                        url: '../assets/images/brand-logos/toggle-logo.png',
                        offset: [10, 0]
                    }
                },
                values: {
                    0: 'marker1title',
                    1: 'marker2title',
                    2: 'marker2title',
                    3: 'marker1title',
                }
            }],
        }
    })

    /* maps with lines */
    var markers = [
        { name: 'Russia', coords: [61.5240, 105.3188] },
        { name: 'Egypt', coords: [26.8206, 30.8025] },
        { name: 'Greenland', coords: [71.7069, -42.6043], offsets: [2, 10] },
        { name: 'Canada', coords: [56, -106], offsets: [-7, 12] },
    ]

    var lines = [
        { from: 'Russia', to: 'Egypt', style: { stroke: '#abb0b7', strokeWidth: 1.5 } },
        { from: 'Canada', to: 'Russia', style: { stroke: '#abb0b7', strokeWidth: 1.5 } },
    ]
    new jsVectorMap({
        map: 'world_merc',
        selector: document.querySelector('#lines-map'),
        // -------- Labels --------
        labels: {
            markers: {
                render: function (marker) {
                    return marker.name
                },
                offsets: function (index) {
                    return markers[index].offsets || [0, 0]
                }
            },
        },
        // -------- Marker and label style --------
        markers: markers,
        lines: lines,
        lineStyle: {
            animation: true,
            strokeDasharray: "6 3 6",
        },
        markerStyle: {
            initial: {
                r: 6,
                fill: '#1266f1',
                stroke: '#fff',
                strokeWidth: 3,
            }
        },
        markerLabelStyle: {
            initial: {
                fontSize: 13,
                fontWeight: 500,
                fill: '#35373e',
            },
        },
    })

    /* us vector map */
    var map = new jsVectorMap({
        selector: "#us-map",
        map: "us_merc_en",
        regionStyle: {
            initial: {
                stroke: "#e9e9e9",
                strokeWidth: .15,
                fill: "#6c5ffc",
                fillOpacity: 1
            }
        },
    });

    /* russia vector map */
    var map = new jsVectorMap({
        selector: "#russia-map",
        map: "russia",
        regionStyle: {
            initial: {
                stroke: "#e9e9e9",
                strokeWidth: .15,
                fill: "#a65e76",
                fillOpacity: 1
            }
        },
    });

    /* spain vector map */
    var map = new jsVectorMap({
        selector: "#spain-map",
        map: "spain",
        regionStyle: {
            initial: {
                stroke: "#e9e9e9",
                strokeWidth: .15,
                fill: "#05c3fb",
                fillOpacity: 1
            }
        },
    });

    /* canada vector map */
    var map = new jsVectorMap({
        selector: "#canada-map",
        map: "canada",
        regionStyle: {
            initial: {
                stroke: "#e9e9e9",
                strokeWidth: .15,
                fill: "#f7b731",
                fillOpacity: 1
            }
        },
    });

})();