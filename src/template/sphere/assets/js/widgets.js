(function () {
    'use strict';

    // Bitcoin Chart
    var options = {
        series: [
            {
                data: [34, 55, 41, 67, 22, 43, 21]
            },
        ],
        chart: {
            sparkline: {
                enabled: true
            },
            dropShadow: {
                enabled: true,
                enabledOnSeries: undefined,
                top: 3,
                right: 6,
                blur: 3,
                color: ['#47bbed'],
                opacity: 0.2
            },
            type: 'line',
            height: 20,
            width: 100
        },
        tooltip: {
            x: {
                show: false
            },
            y: {
                title: {
                    formatter: function (seriesName) {
                        return ''
                    }
                }
            },
            marker: {
                show: false
            }
        },
        colors: ["#47bbed"],
        stroke: {
            width: [1.5],
            curve: ['smooth'],
        },
        xaxis: {
            crosshairs: {
                show: false,
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                opacityFrom: 0.9,
                opacityTo: 0.9,
                stops: [0, 98],
            }
        },
    };
    var chart2 = new ApexCharts(document.querySelector("#btcCoin"), options);
    chart2.render();

    // Ethereum coin Chart
    var options = {
        series: [
            {
                data: [34, 55, 41, 47, 32, 53, 31]
            },
        ],
        chart: {
            sparkline: {
                enabled: true
            },
            dropShadow: {
                enabled: true,
                enabledOnSeries: undefined,
                top: 3,
                right: 6,
                blur: 3,
                color: ['#28D193'],
                opacity: 0.2
            },
            type: 'line',
            height: 20,
            width: 100
        },
        tooltip: {
            x: {
                show: false
            },
            y: {
                title: {
                    formatter: function (seriesName) {
                        return ''
                    }
                }
            },
            marker: {
                show: false
            }
        },
        colors: ["#28D193"],
        stroke: {
            width: [1.5],
            curve: ['smooth'],
        },
        xaxis: {
            crosshairs: {
                show: false,
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                opacityFrom: 0.9,
                opacityTo: 0.9,
                stops: [0, 98],
            }
        },
    };
    var chart = new ApexCharts(document.querySelector("#ethCoin"), options);
    chart.render();

    // Dash coin Chart
    var options = {
        series: [
            {
                data: [31, 53, 32, 47, 41, 55, 44]
            },
        ],
        chart: {
            sparkline: {
                enabled: true
            },
            dropShadow: {
                enabled: true,
                enabledOnSeries: undefined,
                top: 3,
                right: 6,
                blur: 3,
                color: ['#FF534D'],
                opacity: 0.2
            },
            type: 'line',
            height: 20,
            width: 100
        },
        tooltip: {
            x: {
                show: false
            },
            y: {
                title: {
                    formatter: function (seriesName) {
                        return ''
                    }
                }
            },
            marker: {
                show: false
            }
        },
        colors: ["#FF534D"],
        stroke: {
            width: [1.5],
            curve: ['smooth'],
        },
        xaxis: {
            crosshairs: {
                show: false,
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                opacityFrom: 0.9,
                opacityTo: 0.9,
                stops: [0, 98],
            }
        },
    };
    var chart = new ApexCharts(document.querySelector("#dshCoin"), options);
    chart.render();

    // Golem coin Chart
    var options = {
        series: [
            {
                data: [21, 43, 22, 45, 35, 55, 34]
            },
        ],
        chart: {
            sparkline: {
                enabled: true
            },
            dropShadow: {
                enabled: true,
                enabledOnSeries: undefined,
                top: 3,
                right: 6,
                blur: 3,
                color: ['#FFBE14'],
                opacity: 0.2
            },
            type: 'line',
            height: 20,
            width: 100
        },
        tooltip: {
            x: {
                show: false
            },
            y: {
                title: {
                    formatter: function (seriesName) {
                        return ''
                    }
                }
            },
            marker: {
                show: false
            }
        },
        colors: ["#FFBE14"],
        stroke: {
            width: [1.5],
            curve: ['smooth'],
        },
        xaxis: {
            crosshairs: {
                show: false,
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                opacityFrom: 0.9,
                opacityTo: 0.9,
                stops: [0, 98],
            }
        },
    };
    var chart = new ApexCharts(document.querySelector("#glmCoin"), options);
    chart.render();

    // Total revenue chart
    var options = {
        series: [{
            name: "Revenue",
            data: [55, 55, 52, 52, 55, 55, 58, 58, 53, 53, 55, 55]
        }],
        chart: {
            height: 180,
            type: "area",
            sparkline: {
                enabled: true
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            width: [1.4],
            show: true,
            curve: ['smooth'],
        },
        xaxis: {
            crosshairs: {
                show: false,
            }
        },
        legend: {
            show: false
        },
        labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        markers: {
            size: 0
        },
        colors: ["#FFBE14"],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.4,
                opacityTo: 0.7,
                stops: [0, 100]
            }
        },
    };
    var chart = new ApexCharts(document.querySelector("#totalRevenue"), options);
    chart.render();

    // Sessions By Device Chart
    var options = {
        series: [
            {
                name: "Tablet",
                data: [[10, 35, 80]]
            },
            {
                name: "Mobile",
                data: [[22, 10, 80]]
            },
            {
                name: "Desktop",
                data: [[25, 25, 150]]
            },
        ],
        chart: {
            height: 350,
            type: "bubble",
            toolbar: {
                show: false
            }
        },
        grid: {
            borderColor: '#f3f3f3',
            strokeDashArray: 3
        },
        colors: ["#ff8c33", "#28d193", "#4b9bfa"],
        dataLabels: {
            enabled: false
        },
        legend: {
            show: true,
            fontSize: '13px',
            labels: {
                colors: '#959595',
            },
            markers: {
                width: 10,
                height: 10,
            },
        },
        xaxis: {
            min: 0,
            max: 50,
            labels: {
                show: false,
            },
            axisBorder: {
                show: false,
            },
        },
        yaxis: {
            max: 50,
            labels: {
                show: false,
            },
        }
    };
    var chart1 = new ApexCharts(document.querySelector("#sessionsByDevice"), options);
    chart1.render();

    // Users by country map
    var markers = [{
        name: 'Russia',
        coords: [61, 105],
        style: {
            fill: '#28d193'
        }
    },
    {
        name: 'Greenland',
        coords: [72, -42],
        style: {
            fill: '#ff8c33'
        }
    },
    {
        name: 'Canada',
        coords: [56, -106],
        style: {
            fill: '#ff534d'
        }
    },
    {
        name: 'Palestine',
        coords: [31.5, 34.8],
        style: {
            fill: '#ffbe14'
        }
    },
    {
        name: 'Brazil',
        coords: [-14.2350, -51.9253],
        style: {
            fill: '#4b9bfa'
        }
    },
    ];
    var map = new jsVectorMap({
        map: 'world_merc',
        selector: '#users-map',
        markersSelectable: true,

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

})();

// Target report chart
function targetReport() {
    var options = {
        series: [{
            data: [17, 22, 37, 47, 39, 28, 14],
            name: 'Revenue',
        }],
        chart: {
            type: 'bar',
            height: 235,
            toolbar: {
                show: false
            },
            dropShadow: {
                enabled: true,
                enabledOnSeries: undefined,
                top: 6,
                left: 6,
                blur: 3,
                color: '#000',
                opacity: 0.05
            },
        },
        plotOptions: {
            bar: {
                columnWidth: '35%',
                borderRadius: 4,
                horizontal: false,
                colors: {
                    ranges: [{
                        from: 41,
                        to: Infinity,
                        color: "rgb(" + myVarVal + ")"
                    },
                    {
                        from: 0,
                        to: 40,
                        color: "rgba(" + myVarVal + ", 0.2)"
                    }]
                },
            }
        },
        dataLabels: {
            enabled: false
        },
        grid: {
            show: false,
            borderColor: 'transparent',
            padding: {
                top: 0,
                right: 0,
                bottom: 0,
                left: 0
            },
            yaxis: {
                lines: {
                    show: false
                }
            },
        },
        xaxis: {
            categories: ['Mon', 'Tues', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            colors: '#fff',
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            },
            labels: {
                rotate: -90,
                style: {
                    fontFamily: 'Inter, sans-serif',
                },
            }
        },
        yaxis: {
            colors: '#fff',
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            },
            labels: {
                show: false
            }
        }
    };
    document.querySelector("#report").innerHTML = ""
    var chart = new ApexCharts(document.querySelector("#report"), options);
    chart.render();
}

function pageviews() {
    var options = {
        series: [{
            name: 'Job Applied',
            type: 'line',
            data: [25, 50, 30, 55, 20, 45, 30]
        }, {
            name: 'Job Viewed',
            type: 'area',
            data: [35, 25, 40, 30, 45, 35, 60]
        }],
        chart: {
            height: 348,
            type: 'line',
            stacked: false,
            toolbar: {
                show: false
            },
            dropShadow: {
                enabled: true,
                enabledOnSeries: undefined,
                top: 7,
                left: 1,
                blur: 3,
                color: '#000',
                opacity: 0.2
            },
        },
        colors: ["rgba(" + myVarVal + ", 1)", "rgba(" + myVarVal + ", 0.1)"],
        grid: {
            borderColor: '#e9edf4',
            padding: {
                top: 10,
                right: 0,
                bottom: 0,
                left: 0
            },
        },
        stroke: {
            width: [2, 2],
            curve: 'smooth',
            dashArray: [0, 4]
        },
        labels: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        markers: {
            size: 4,
            hover: {
                size: 5
            }
        },
        fill: {
            opacity: [0.85, 0.25, 1],
            gradient: {
                inverseColors: false,
                shadeIntensity: 1,
                shade: 'light',
                type: "vertical",
                opacityFrom: 0.85,
                opacityTo: 0.55,
                stops: [0, 100, 100, 100]
            }
        },
        legend: {
            show: true,
            position: 'top',
            fontFamily: 'Inter, sans-serif',
            markers: {
                width: 10,
                height: 10,
            }
        },
        xaxis: {
            type: 'week',
            axisBorder: {
                show: true,
                color: 'rgba(119, 119, 142, 0.05)',
                offsetX: 0,
                offsetY: 0,
            },
            axisTicks: {
                show: true,
                borderType: 'solid',
                color: 'rgba(119, 119, 142, 0.05)',
                width: 6,
                offsetX: 0,
                offsetY: 0
            },
            labels: {
                rotate: -90
            }
        },
        yaxis: {
            title: {
                style: {
                    color: '#adb5be',
                    fontSize: '14px',
                    fontFamily: 'Inter, sans-serif',
                    fontWeight: 600
                },
            },
            labels: {
                formatter: function (y) {
                    return y.toFixed(0) + "";
                }
            }
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function (y) {
                    if (typeof y !== "undefined") {
                        return y.toFixed(0) + " points";
                    }
                    return y;

                }
            }
        }
    };
    document.querySelector("#views").innerHTML = ""
    var chart = new ApexCharts(document.querySelector("#views"), options);
    chart.render();
}