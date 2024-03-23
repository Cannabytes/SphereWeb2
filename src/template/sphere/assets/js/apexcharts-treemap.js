(function () {
    "use strict";

    /* basic treemap chart */
    var options = {
        series: [
            {
                data: [
                    {
                        x: 'New Delhi',
                        y: 218
                    },
                    {
                        x: 'Kolkata',
                        y: 149
                    },
                    {
                        x: 'Mumbai',
                        y: 184
                    },
                    {
                        x: 'Ahmedabad',
                        y: 55
                    },
                    {
                        x: 'Bangaluru',
                        y: 84
                    },
                    {
                        x: 'Pune',
                        y: 31
                    },
                    {
                        x: 'Chennai',
                        y: 70
                    },
                    {
                        x: 'Jaipur',
                        y: 30
                    },
                    {
                        x: 'Surat',
                        y: 44
                    },
                    {
                        x: 'Hyderabad',
                        y: 68
                    },
                    {
                        x: 'Lucknow',
                        y: 28
                    },
                    {
                        x: 'Indore',
                        y: 19
                    },
                    {
                        x: 'Kanpur',
                        y: 29
                    }
                ]
            }
        ],
        legend: {
            show: false
        },
        chart: {
            height: 350,
            type: 'treemap'
        },
        colors: ["#6c5ffc"],
        title: {
            text: 'Basic Treemap',
            align: 'left',
            style: {
                fontSize: '13px',
                fontWeight: 'bold',
                color: '#8c9097'
            },
        },
    };
    var chart = new ApexCharts(document.querySelector("#treemap-basic"), options);
    chart.render();

    /* multi dimensional treemap chart */
    var options = {
        series: [
            {
                name: 'Desktops',
                data: [
                    {
                        x: 'ABC',
                        y: 10
                    },
                    {
                        x: 'DEF',
                        y: 60
                    },
                    {
                        x: 'XYZ',
                        y: 41
                    }
                ]
            },
            {
                name: 'Mobile',
                data: [
                    {
                        x: 'ABCD',
                        y: 10
                    },
                    {
                        x: 'DEFG',
                        y: 20
                    },
                    {
                        x: 'WXYZ',
                        y: 51
                    },
                    {
                        x: 'PQR',
                        y: 30
                    },
                    {
                        x: 'MNO',
                        y: 20
                    },
                    {
                        x: 'CDE',
                        y: 30
                    }
                ]
            }
        ],
        colors: ["#6c5ffc", "#05c3fb"],
        legend: {
            show: false
        },
        chart: {
            height: 350,
            type: 'treemap'
        },
        title: {
            text: 'Multi-dimensional Treemap',
            align: 'center',
            style: {
                fontSize: '13px',
                fontWeight: 'bold',
                color: '#8c9097'
            },
        },
    };
    var chart = new ApexCharts(document.querySelector("#treemap-multi"), options);
    chart.render();

    /* distributed treemap chart */
    var options = {
        series: [
            {
                data: [
                    {
                        x: 'New Delhi',
                        y: 218
                    },
                    {
                        x: 'Kolkata',
                        y: 149
                    },
                    {
                        x: 'Mumbai',
                        y: 184
                    },
                    {
                        x: 'Ahmedabad',
                        y: 55
                    },
                    {
                        x: 'Bangaluru',
                        y: 84
                    },
                    {
                        x: 'Pune',
                        y: 31
                    },
                    {
                        x: 'Chennai',
                        y: 70
                    },
                    {
                        x: 'Jaipur',
                        y: 30
                    },
                    {
                        x: 'Surat',
                        y: 44
                    },
                    {
                        x: 'Hyderabad',
                        y: 68
                    },
                    {
                        x: 'Lucknow',
                        y: 28
                    },
                    {
                        x: 'Indore',
                        y: 19
                    },
                    {
                        x: 'Kanpur',
                        y: 29
                    }
                ]
            }
        ],
        legend: {
            show: false
        },
        chart: {
            height: 350,
            type: 'treemap'
        },
        title: {
            text: 'Distibuted Treemap (different color for each cell)',
            align: 'center',
            style: {
                fontSize: '13px',
                fontWeight: 'bold',
                color: '#8c9097'
            },
        },
        colors: [
            '#6c5ffc',
            '#a65e76',
            '#f7b731',
            '#a66a5e',
            '#a65e9a',
            '#09ad95',
            '#e82646',
            '#49b6f5',
            '#5b67c7',
            '#2dce89',
            '#EF6537',
            '#8c9097'
        ],
        plotOptions: {
            treemap: {
                distributed: true,
                enableShades: false
            }
        }
    };
    var chart = new ApexCharts(document.querySelector("#treemap-distributed"), options);
    chart.render();

    /* treemap chart with color ranges */
    var options = {
        series: [
            {
                data: [
                    {
                        x: 'INTC',
                        y: 1.2
                    },
                    {
                        x: 'GS',
                        y: 0.4
                    },
                    {
                        x: 'CVX',
                        y: -1.4
                    },
                    {
                        x: 'GE',
                        y: 2.7
                    },
                    {
                        x: 'CAT',
                        y: -0.3
                    },
                    {
                        x: 'RTX',
                        y: 5.1
                    },
                    {
                        x: 'CSCO',
                        y: -2.3
                    },
                    {
                        x: 'JNJ',
                        y: 2.1
                    },
                    {
                        x: 'PG',
                        y: 0.3
                    },
                    {
                        x: 'TRV',
                        y: 0.12
                    },
                    {
                        x: 'MMM',
                        y: -2.31
                    },
                    {
                        x: 'NKE',
                        y: 3.98
                    },
                    {
                        x: 'IYT',
                        y: 1.67
                    }
                ]
            }
        ],
        legend: {
            show: false
        },
        chart: {
            height: 350,
            type: 'treemap'
        },
        title: {
            text: 'Treemap with Color scale',
            align: 'left',
            style: {
                fontSize: '13px',
                fontWeight: 'bold',
                color: '#8c9097'
            },
        },
        dataLabels: {
            enabled: true,
            style: {
                fontSize: '12px',
            },
            formatter: function (text, op) {
                return [text, op.value]
            },
            offsetY: -4
        },
        plotOptions: {
            treemap: {
                enableShades: true,
                shadeIntensity: 0.5,
                reverseNegativeShade: true,
                colorScale: {
                    ranges: [
                        {
                            from: -6,
                            to: 0,
                            color: '#6c5ffc'
                        },
                        {
                            from: 0.001,
                            to: 6,
                            color: '#05c3fb'
                        }
                    ]
                }
            }
        }
    };
    var chart = new ApexCharts(document.querySelector("#treemap-colorranges"), options);
    chart.render();

})();