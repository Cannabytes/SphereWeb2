(function () {
    "use strict";

    /* basic boxplot chart */
    var options = {
        series: [
            {
                type: 'boxPlot',
                data: [
                    {
                        x: 'Jan 2015',
                        y: [54, 66, 69, 75, 88]
                    },
                    {
                        x: 'Jan 2016',
                        y: [43, 65, 69, 76, 81]
                    },
                    {
                        x: 'Jan 2017',
                        y: [31, 39, 45, 51, 59]
                    },
                    {
                        x: 'Jan 2018',
                        y: [39, 46, 55, 65, 71]
                    },
                    {
                        x: 'Jan 2019',
                        y: [29, 31, 35, 39, 44]
                    },
                    {
                        x: 'Jan 2020',
                        y: [41, 49, 58, 61, 67]
                    },
                    {
                        x: 'Jan 2021',
                        y: [54, 59, 66, 71, 88]
                    }
                ]
            }
        ],
        chart: {
            type: 'boxPlot',
            height: 320
        },
        title: {
            text: 'Basic BoxPlot Chart',
            align: 'left',
            style: {
                fontSize: '13px',
                fontWeight: 'bold',
                color: '#8c9097'
            },
        },
        grid: {
            borderColor: '#f2f5f7',
        },
        plotOptions: {
            boxPlot: {
                colors: {
                    upper: '#6c5ffc',
                    lower: '#05c3fb'
                }
            }
        },
        xaxis: {
            labels: {
                show: true,
                style: {
                    colors: "#8c9097",
                    fontSize: '11px',
                    fontWeight: 600,
                    cssClass: 'apexcharts-xaxis-label',
                },
            }
        },
        yaxis: {
            labels: {
                show: true,
                style: {
                    colors: "#8c9097",
                    fontSize: '11px',
                    fontWeight: 600,
                    cssClass: 'apexcharts-yaxis-label',
                },
            }
        }
    };
    var chart = new ApexCharts(document.querySelector("#boxplot-basic"), options);
    chart.render();

    /* boxplot with scatter chart */
    var options = {
        series: [
            {
                name: 'box',
                type: 'boxPlot',
                data: [
                    {
                        x: new Date('2017-01-01').getTime(),
                        y: [54, 66, 69, 75, 88]
                    },
                    {
                        x: new Date('2018-01-01').getTime(),
                        y: [43, 65, 69, 76, 81]
                    },
                    {
                        x: new Date('2019-01-01').getTime(),
                        y: [31, 39, 45, 51, 59]
                    },
                    {
                        x: new Date('2020-01-01').getTime(),
                        y: [39, 46, 55, 65, 71]
                    },
                    {
                        x: new Date('2021-01-01').getTime(),
                        y: [29, 31, 35, 39, 44]
                    }
                ]
            },
            {
                name: 'outliers',
                type: 'scatter',
                data: [
                    {
                        x: new Date('2017-01-01').getTime(),
                        y: 32
                    },
                    {
                        x: new Date('2018-01-01').getTime(),
                        y: 25
                    },
                    {
                        x: new Date('2019-01-01').getTime(),
                        y: 64
                    },
                    {
                        x: new Date('2020-01-01').getTime(),
                        y: 27
                    },
                    {
                        x: new Date('2020-01-01').getTime(),
                        y: 78
                    },
                    {
                        x: new Date('2021-01-01').getTime(),
                        y: 15
                    }
                ]
            }
        ],
        chart: {
            type: 'boxPlot',
            height: 320
        },
        colors: ['#6c5ffc', '#05c3fb'],
        grid: {
            borderColor: '#f2f5f7',
        },
        title: {
            text: 'BoxPlot - Scatter Chart',
            align: 'left',
            style: {
                fontSize: '13px',
                fontWeight: 'bold',
                color: '#8c9097'
            },
        },
        plotOptions: {
            boxPlot: {
                colors: {
                    upper: '#6c5ffc',
                    lower: '#05c3fb'
                }
            }
        },
        xaxis: {
            type: 'datetime',
            tooltip: {
                formatter: function (val) {
                    return new Date(val).getFullYear()
                }
            },
            labels: {
                show: true,
                style: {
                    colors: "#8c9097",
                    fontSize: '11px',
                    fontWeight: 600,
                    cssClass: 'apexcharts-xaxis-label',
                },
            }
        },
        yaxis: {
            labels: {
                show: true,
                style: {
                    colors: "#8c9097",
                    fontSize: '11px',
                    fontWeight: 600,
                    cssClass: 'apexcharts-yaxis-label',
                },
            }
        },
        tooltip: {
            shared: false,
            intersect: true
        }
    };
    var chart = new ApexCharts(document.querySelector("#boxplot-scatter"), options);
    chart.render();

    /* horizontal boxplot chart */
    var options = {
        series: [
            {
                data: [
                    {
                        x: 'Category A',
                        y: [54, 66, 69, 75, 88]
                    },
                    {
                        x: 'Category B',
                        y: [43, 65, 69, 76, 81]
                    },
                    {
                        x: 'Category C',
                        y: [31, 39, 45, 51, 59]
                    },
                    {
                        x: 'Category D',
                        y: [39, 46, 55, 65, 71]
                    },
                    {
                        x: 'Category E',
                        y: [29, 31, 35, 39, 44]
                    },
                    {
                        x: 'Category F',
                        y: [41, 49, 58, 61, 67]
                    },
                    {
                        x: 'Category G',
                        y: [54, 59, 66, 71, 88]
                    }
                ]
            }
        ],
        chart: {
            type: 'boxPlot',
            height: 320
        },
        title: {
            text: 'Horizontal BoxPlot Chart',
            align: 'left',
            style: {
                fontSize: '13px',
                fontWeight: 'bold',
                color: '#8c9097'
            },
        },
        plotOptions: {
            bar: {
                horizontal: true,
                barHeight: '50%'
            },
            boxPlot: {
                colors: {
                    upper: '#e9ecef',
                    lower: '#f8f9fa'
                }
            }
        },
        grid: {
            borderColor: '#f2f5f7',
        },
        xaxis: {
            labels: {
                show: true,
                style: {
                    colors: "#8c9097",
                    fontSize: '11px',
                    fontWeight: 600,
                    cssClass: 'apexcharts-xaxis-label',
                },
            }
        },
        yaxis: {
            labels: {
                show: true,
                style: {
                    colors: "#8c9097",
                    fontSize: '11px',
                    fontWeight: 600,
                    cssClass: 'apexcharts-yaxis-label',
                },
            }
        },
        stroke: {
            colors: ['#6c757d']
        }
    };
    var chart = new ApexCharts(document.querySelector("#boxplot-horizontal"), options);
    chart.render();

})();