(function () {
    "use strict";

    /* basic column chart */
    var options = {
        series: [{
            name: 'Net Profit',
            data: [44, 55, 57, 56, 61, 58, 63, 60, 66]
        }, {
            name: 'Revenue',
            data: [76, 85, 101, 98, 87, 105, 91, 114, 94]
        }, {
            name: 'Free Cash Flow',
            data: [35, 41, 36, 26, 45, 48, 52, 53, 41]
        }],
        chart: {
            type: 'bar',
            height: 320
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '80%',
                endingShape: 'rounded'
            },
        },
        grid: {
            borderColor: '#f2f5f7',
        },
        dataLabels: {
            enabled: false
        },
        colors: ["#6c5ffc", "#05c3fb", "#f7b731"],
        stroke: {
            show: true,
            width: 2,
            colors: ['transparent']
        },
        xaxis: {
            categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
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
            title: {
                text: '$ (thousands)',
                style: {
                    color: "#8c9097",
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
        fill: {
            opacity: 1
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return "$ " + val + " thousands"
                }
            }
        }
    };
    var chart = new ApexCharts(document.querySelector("#column-basic"), options);
    chart.render();

    /* column chart with datalabels */
    var options = {
        series: [{
            name: 'Inflation',
            data: [2.3, 3.1, 4.0, 10.1, 4.0, 3.6, 3.2, 2.3, 1.4, 0.8, 0.5, 0.2]
        }],
        chart: {
            height: 320,
            type: 'bar',
        },
        grid: {
            borderColor: '#f2f5f7',
        },
        plotOptions: {
            bar: {
                borderRadius: 10,
                dataLabels: {
                    position: 'top', // top, center, bottom
                },
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return val + "%";
            },
            offsetY: -20,
            style: {
                fontSize: '12px',
                colors: ["#8c9097"]
            }
        },
        colors: ["#6c5ffc"],
        xaxis: {
            categories: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
            position: 'top',
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            },
            crosshairs: {
                fill: {
                    type: 'gradient',
                    gradient: {
                        colorFrom: '#D8E3F0',
                        colorTo: '#BED1E6',
                        stops: [0, 100],
                        opacityFrom: 0.4,
                        opacityTo: 0.5,
                    }
                }
            },
            tooltip: {
                enabled: true,
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
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false,
            },
            labels: {
                show: false,
                formatter: function (val) {
                    return val + "%";
                }
            }

        },
        title: {
            text: 'Monthly Inflation in Argentina, 2002',
            floating: true,
            offsetY: 330,
            align: 'center',
            style: {
                color: '#444'
            }
        }
    };
    var chart = new ApexCharts(document.querySelector("#column-datalabels"), options);
    chart.render();

    /* stacked column chart */
    var options = {
        series: [{
            name: 'PRODUCT A',
            data: [44, 55, 41, 67, 22, 43]
        }, {
            name: 'PRODUCT B',
            data: [13, 23, 20, 8, 13, 27]
        }, {
            name: 'PRODUCT C',
            data: [11, 17, 15, 15, 21, 14]
        }, {
            name: 'PRODUCT D',
            data: [21, 7, 25, 13, 22, 8]
        }],
        chart: {
            type: 'bar',
            height: 320,
            stacked: true,
            toolbar: {
                show: true
            },
            zoom: {
                enabled: true
            }
        },
        grid: {
            borderColor: '#f2f5f7',
        },
        colors: ["#6c5ffc", "#05c3fb", "#f7b731", "#e82646"],
        responsive: [{
            breakpoint: 480,
            options: {
                legend: {
                    position: 'bottom',
                    offsetX: -10,
                    offsetY: 0
                }
            }
        }],
        plotOptions: {
            bar: {
                horizontal: false,
            },
        },
        xaxis: {
            type: 'datetime',
            categories: ['01/01/2011 GMT', '01/02/2011 GMT', '01/03/2011 GMT', '01/04/2011 GMT',
                '01/05/2011 GMT', '01/06/2011 GMT'
            ],
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
        legend: {
            position: 'right',
            offsetY: 40
        },
        fill: {
            opacity: 1
        },
        yaxis: {
            labels: {
                show: true,
                style: {
                    colors: "#8c9097",
                    fontSize: '11px',
                    fontWeight: 600,
                    cssClass: 'apexcharts-xaxis-label',
                },
            }
        }
    };
    var chart = new ApexCharts(document.querySelector("#column-stacked"), options);
    chart.render();

    /* 100% stacked column chart */
    var options = {
        series: [{
            name: 'PRODUCT A',
            data: [44, 55, 41, 67, 22, 43, 21, 49]
        }, {
            name: 'PRODUCT B',
            data: [13, 23, 20, 8, 13, 27, 33, 12]
        }, {
            name: 'PRODUCT C',
            data: [11, 17, 15, 15, 21, 14, 15, 13]
        }],
        chart: {
            type: 'bar',
            height: 320,
            stacked: true,
            stackType: '100%'
        },
        grid: {
            borderColor: '#f2f5f7',
        },
        responsive: [{
            breakpoint: 480,
            options: {
                legend: {
                    position: 'bottom',
                    offsetX: -10,
                    offsetY: 0
                }
            }
        }],
        colors: ["#6c5ffc", "#05c3fb", "#f7b731"],
        xaxis: {
            categories: ['2011 Q1', '2011 Q2', '2011 Q3', '2011 Q4', '2012 Q1', '2012 Q2',
                '2012 Q3', '2012 Q4'
            ],
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
                    cssClass: 'apexcharts-xaxis-label',
                },
            }
        },
        fill: {
            opacity: 1
        },
        legend: {
            position: 'right',
            offsetX: 0,
            offsetY: 50
        },
    };
    var chart = new ApexCharts(document.querySelector("#column-stacked-full"), options);
    chart.render();

    /* colummn chart with markers */
    var options = {
        series: [
            {
                name: 'Actual',
                data: [
                    {
                        x: '2011',
                        y: 1292,
                        goals: [
                            {
                                name: 'Expected',
                                value: 1400,
                                strokeHeight: 5,
                                strokeColor: '#775DD0'
                            }
                        ]
                    },
                    {
                        x: '2012',
                        y: 4432,
                        goals: [
                            {
                                name: 'Expected',
                                value: 5400,
                                strokeHeight: 5,
                                strokeColor: '#775DD0'
                            }
                        ]
                    },
                    {
                        x: '2013',
                        y: 5423,
                        goals: [
                            {
                                name: 'Expected',
                                value: 5200,
                                strokeHeight: 5,
                                strokeColor: '#775DD0'
                            }
                        ]
                    },
                    {
                        x: '2014',
                        y: 6653,
                        goals: [
                            {
                                name: 'Expected',
                                value: 6500,
                                strokeHeight: 5,
                                strokeColor: '#775DD0'
                            }
                        ]
                    },
                    {
                        x: '2015',
                        y: 8133,
                        goals: [
                            {
                                name: 'Expected',
                                value: 6600,
                                strokeHeight: 13,
                                strokeWidth: 0,
                                strokeLineCap: 'round',
                                strokeColor: '#775DD0'
                            }
                        ]
                    },
                    {
                        x: '2016',
                        y: 7132,
                        goals: [
                            {
                                name: 'Expected',
                                value: 7500,
                                strokeHeight: 5,
                                strokeColor: '#775DD0'
                            }
                        ]
                    },
                    {
                        x: '2017',
                        y: 7332,
                        goals: [
                            {
                                name: 'Expected',
                                value: 8700,
                                strokeHeight: 5,
                                strokeColor: '#775DD0'
                            }
                        ]
                    },
                    {
                        x: '2018',
                        y: 6553,
                        goals: [
                            {
                                name: 'Expected',
                                value: 7300,
                                strokeHeight: 2,
                                strokeDashArray: 2,
                                strokeColor: '#775DD0'
                            }
                        ]
                    }
                ]
            }
        ],
        chart: {
            height: 320,
            type: 'bar'
        },
        plotOptions: {
            bar: {
                columnWidth: '60%'
            }
        },
        colors: ['#05c3fb'],
        dataLabels: {
            enabled: false
        },
        grid: {
            borderColor: '#f2f5f7',
        },
        legend: {
            show: true,
            showForSingleSeries: true,
            customLegendItems: ['Actual', 'Expected'],
            markers: {
                fillColors: ['#05c3fb', '#775DD0']
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
                    cssClass: 'apexcharts-xaxis-label',
                },
            }
        }
    };
    var chart = new ApexCharts(document.querySelector("#column-markers"), options);
    chart.render();

    /* column chart with rotated labels */
    var options = {
        series: [{
            name: 'Servings',
            data: [44, 55, 41, 67, 22, 43, 21, 33, 45, 31, 87, 65, 35]
        }],
        annotations: {
            points: [{
                x: 'Bananas',
                seriesIndex: 0,
                label: {
                    borderColor: '#775DD0',
                    offsetY: 0,
                    style: {
                        color: '#fff',
                        background: '#775DD0',
                    },
                    text: 'Bananas are good',
                }
            }]
        },
        chart: {
            height: 320,
            type: 'bar',
        },
        plotOptions: {
            bar: {
                borderRadius: 10,
                columnWidth: '50%',
            }
        },
        dataLabels: {
            enabled: false
        },
        colors: ["#6c5ffc"],
        stroke: {
            width: 2
        },
        grid: {
            borderColor: '#f2f5f7',
        },
        xaxis: {
            labels: {
                rotate: -45,
                rotateAlways: true,
                style: {
                    colors: "#8c9097",
                    fontSize: '11px',
                    fontWeight: 600,
                    cssClass: 'apexcharts-xaxis-label',
                }
            },
            categories: ['Apples', 'Oranges', 'Strawberries', 'Pineapples', 'Mangoes', 'Bananas',
                'Blackberries', 'Pears', 'Watermelons', 'Cherries', 'Pomegranates', 'Tangerines', 'Papayas'
            ],
            tickPlacement: 'on'
        },
        yaxis: {
            labels: {
                style: {
                    colors: "#8c9097",
                    fontSize: '11px',
                    fontWeight: 600,
                    cssClass: 'apexcharts-yaxis-label',
                }
            },
            title: {
                text: 'Servings',
                style: {
                    color: "#8c9097",
                }
            },
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: "horizontal",
                shadeIntensity: 0.25,
                gradientToColors: undefined,
                inverseColors: true,
                opacityFrom: 0.85,
                opacityTo: 0.85,
                stops: [50, 0, 100]
            },
        }
    };
    var chart = new ApexCharts(document.querySelector("#column-rotated-labels"), options);
    chart.render();

    /* column chart with negative values */
    var options = {
        series: [{
            name: 'Cash Flow',
            data: [1.45, 5.42, 5.9, -0.42, -12.6, -18.1, -18.2, -14.16, -11.1, -6.09, 0.34, 3.88, 13.07,
                5.8, 2, 7.37, 8.1, 13.57, 15.75, 17.1, 19.8, -27.03, -54.4, -47.2, -43.3, -18.6, -
                48.6, -41.1, -39.6, -37.6, -29.4, -21.4, -2.4
            ]
        }],
        chart: {
            type: 'bar',
            height: 320
        },
        plotOptions: {
            bar: {
                colors: {
                    ranges: [{
                        from: -100,
                        to: -46,
                        color: '#e82646'
                    }, {
                        from: -45,
                        to: 0,
                        color: '#a66a5e'
                    }]
                },
                columnWidth: '80%',
            }
        },
        grid: {
            borderColor: '#f2f5f7',
        },
        colors: ["#6c5ffc"],
        dataLabels: {
            enabled: false,
        },
        yaxis: {
            title: {
                text: 'Growth',
                style: {
                    color: "#8c9097",
                }
            },
            labels: {
                formatter: function (y) {
                    return y.toFixed(0) + "%";
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
            }
        },
        xaxis: {
            type: 'datetime',
            categories: [
                '2011-01-01', '2011-02-01', '2011-03-01', '2011-04-01', '2011-05-01', '2011-06-01',
                '2011-07-01', '2011-08-01', '2011-09-01', '2011-10-01', '2011-11-01', '2011-12-01',
                '2012-01-01', '2012-02-01', '2012-03-01', '2012-04-01', '2012-05-01', '2012-06-01',
                '2012-07-01', '2012-08-01', '2012-09-01', '2012-10-01', '2012-11-01', '2012-12-01',
                '2013-01-01', '2013-02-01', '2013-03-01', '2013-04-01', '2013-05-01', '2013-06-01',
                '2013-07-01', '2013-08-01', '2013-09-01'
            ],
            labels: {
                rotate: -90,
                style: {
                    colors: "#8c9097",
                    fontSize: '11px',
                    fontWeight: 600,
                    cssClass: 'apexcharts-xaxis-label',
                },
            }
        }
    };
    var chart = new ApexCharts(document.querySelector("#column-negative"), options);
    chart.render();

    /* range column chart */
    var options = {
        series: [{
            data: [{
                x: 'Team A',
                y: [1, 5]
            }, {
                x: 'Team B',
                y: [4, 6]
            }, {
                x: 'Team C',
                y: [5, 8]
            }, {
                x: 'Team D',
                y: [3, 11]
            }]
        }, {
            data: [{
                x: 'Team A',
                y: [2, 6]
            }, {
                x: 'Team B',
                y: [1, 3]
            }, {
                x: 'Team C',
                y: [7, 8]
            }, {
                x: 'Team D',
                y: [5, 9]
            }]
        }],
        chart: {
            type: 'rangeBar',
            height: 320
        },
        grid: {
            borderColor: '#f2f5f7',
        },
        colors: ["#6c5ffc", "#05c3fb"],
        plotOptions: {
            bar: {
                horizontal: false
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
                    cssClass: 'apexcharts-xaxis-label',
                },
            }
        },
        dataLabels: {
            enabled: true
        }
    };
    var chart = new ApexCharts(document.querySelector("#column-range"), options);
    chart.render();

    /* dynamic loaded chart */
    Apex = {
        chart: {
            toolbar: {
                show: false
            }
        },
        tooltip: {
            shared: false
        },
        legend: {
            show: false
        }
    }
    var colors = ['#6c5ffc', '#05c3fb', '#f7b731', '#49b6f5', '#e82646', '#09ad95', '#5b67c7'];
    function shuffleArray(array) {
        for (var i = array.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var temp = array[i];
            array[i] = array[j];
            array[j] = temp;
        }
        return array;
    }
    var arrayData = [{
        y: 400,
        quarters: [{
            x: 'Q1',
            y: 120
        }, {
            x: 'Q2',
            y: 90
        }, {
            x: 'Q3',
            y: 100
        }, {
            x: 'Q4',
            y: 90
        }]
    }, {
        y: 430,
        quarters: [{
            x: 'Q1',
            y: 120
        }, {
            x: 'Q2',
            y: 110
        }, {
            x: 'Q3',
            y: 90
        }, {
            x: 'Q4',
            y: 110
        }]
    }, {
        y: 448,
        quarters: [{
            x: 'Q1',
            y: 70
        }, {
            x: 'Q2',
            y: 100
        }, {
            x: 'Q3',
            y: 140
        }, {
            x: 'Q4',
            y: 138
        }]
    }, {
        y: 470,
        quarters: [{
            x: 'Q1',
            y: 150
        }, {
            x: 'Q2',
            y: 60
        }, {
            x: 'Q3',
            y: 190
        }, {
            x: 'Q4',
            y: 70
        }]
    }, {
        y: 540,
        quarters: [{
            x: 'Q1',
            y: 120
        }, {
            x: 'Q2',
            y: 120
        }, {
            x: 'Q3',
            y: 130
        }, {
            x: 'Q4',
            y: 170
        }]
    }, {
        y: 580,
        quarters: [{
            x: 'Q1',
            y: 170
        }, {
            x: 'Q2',
            y: 130
        }, {
            x: 'Q3',
            y: 120
        }, {
            x: 'Q4',
            y: 160
        }]
    }];
    function makeData() {
        var dataSet = shuffleArray(arrayData)
        var dataYearSeries = [{
            x: "2011",
            y: dataSet[0].y,
            color: colors[0],
            quarters: dataSet[0].quarters
        }, {
            x: "2012",
            y: dataSet[1].y,
            color: colors[1],
            quarters: dataSet[1].quarters
        }, {
            x: "2013",
            y: dataSet[2].y,
            color: colors[2],
            quarters: dataSet[2].quarters
        }, {
            x: "2014",
            y: dataSet[3].y,
            color: colors[3],
            quarters: dataSet[3].quarters
        }, {
            x: "2015",
            y: dataSet[4].y,
            color: colors[4],
            quarters: dataSet[4].quarters
        }, {
            x: "2016",
            y: dataSet[5].y,
            color: colors[5],
            quarters: dataSet[5].quarters
        }];

        return dataYearSeries
    }
    function updateQuarterChart(sourceChart, destChartIDToUpdate) {
        var series = [];
        var seriesIndex = 0;
        var colors = []
        if (sourceChart.w.globals.selectedDataPoints[0]) {
            var selectedPoints = sourceChart.w.globals.selectedDataPoints;
            for (var i = 0; i < selectedPoints[seriesIndex].length; i++) {
                var selectedIndex = selectedPoints[seriesIndex][i];
                var yearSeries = sourceChart.w.config.series[seriesIndex];
                series.push({
                    name: yearSeries.data[selectedIndex].x,
                    data: yearSeries.data[selectedIndex].quarters
                })
                colors.push(yearSeries.data[selectedIndex].color)
            }

            if (series.length === 0) series = [{
                data: []
            }]
            return ApexCharts.exec(destChartIDToUpdate, 'updateOptions', {
                series: series,
                colors: colors,
                fill: {
                    colors: colors
                }
            })
        }
    }
    var options = {
        series: [{
            data: makeData()
        }],
        chart: {
            id: 'barYear',
            height: 320,
            width: '100%',
            type: 'bar',
            events: {
                dataPointSelection: function (e, chart, opts) {
                    var quarterChartEl = document.querySelector("#chart-quarter");
                    var yearChartEl = document.querySelector("#chart-year");

                    if (opts.selectedDataPoints[0].length === 1) {
                        if (quarterChartEl.classList.contains("active")) {
                            updateQuarterChart(chart, 'barQuarter')
                        } else {
                            yearChartEl.classList.add("chart-quarter-activated")
                            quarterChartEl.classList.add("active");
                            updateQuarterChart(chart, 'barQuarter')
                        }
                    } else {
                        updateQuarterChart(chart, 'barQuarter')
                    }

                    if (opts.selectedDataPoints[0].length === 0) {
                        yearChartEl.classList.remove("chart-quarter-activated")
                        quarterChartEl.classList.remove("active");
                    }

                },
                updated: function (chart) {
                    updateQuarterChart(chart, 'barQuarter')
                }
            }
        },
        plotOptions: {
            bar: {
                distributed: true,
                horizontal: true,
                barHeight: '75%',
                dataLabels: {
                    position: 'bottom'
                }
            }
        },
        dataLabels: {
            enabled: true,
            textAnchor: 'start',
            style: {
                colors: ['#fff']
            },
            formatter: function (val, opt) {
                return opt.w.globals.labels[opt.dataPointIndex]
            },
            offsetX: 0,
            dropShadow: {
                enabled: true
            }
        },
        grid: {
            borderColor: '#f2f5f7',
        },
        colors: colors,
        states: {
            normal: {
                filter: {
                    type: 'desaturate'
                }
            },
            active: {
                allowMultipleDataPointsSelection: true,
                filter: {
                    type: 'darken',
                    value: 1
                }
            }
        },
        tooltip: {
            x: {
                show: false
            },
            y: {
                title: {
                    formatter: function (val, opts) {
                        return opts.w.globals.labels[opts.dataPointIndex]
                    }
                }
            }
        },
        title: {
            text: 'Yearly Results',
            offsetX: 15,
            align: 'left',
            style: {
                fontSize: '13px',
                fontWeight: 'bold',
                color: '#8c9097'
            },
        },
        subtitle: {
            text: '(Click on bar to see details)',
            offsetX: 15
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
                show: false
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#chart-year"), options);
    chart.render();

    var optionsQuarter = {
        series: [{
            data: []
        }],
        chart: {
            id: 'barQuarter',
            height: 320,
            width: '100%',
            type: 'bar',
            stacked: true
        },
        plotOptions: {
            bar: {
                columnWidth: '50%',
                horizontal: false
            }
        },
        legend: {
            show: false
        },
        grid: {
            yaxis: {
                lines: {
                    show: false,
                }
            },
            xaxis: {
                lines: {
                    show: true,
                }
            },
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
                show: false
            },
        },
        title: {
            text: 'Quarterly Results',
            offsetX: 10,
            style: {
                fontSize: '13px',
                fontWeight: 'bold',
                color: '#8c9097'
            },
        },
        tooltip: {
            x: {
                formatter: function (val, opts) {
                    return opts.w.globals.seriesNames[opts.seriesIndex]
                }
            },
            y: {
                title: {
                    formatter: function (val, opts) {
                        return opts.w.globals.labels[opts.dataPointIndex]
                    },
                }
            }
        }
    };

    var chartQuarter = new ApexCharts(document.querySelector("#chart-quarter"), optionsQuarter);
    chartQuarter.render();


    chart.addEventListener('dataPointSelection', function (e, chart, opts) {
        var quarterChartEl = document.querySelector("#chart-quarter");
        var yearChartEl = document.querySelector("#chart-year");

        if (opts.selectedDataPoints[0].length === 1) {
            if (quarterChartEl.classList.contains("active")) {
                updateQuarterChart(chart, 'barQuarter')
            }
            else {
                yearChartEl.classList.add("chart-quarter-activated")
                quarterChartEl.classList.add("active");
                updateQuarterChart(chart, 'barQuarter')
            }
        } else {
            updateQuarterChart(chart, 'barQuarter')
        }

        if (opts.selectedDataPoints[0].length === 0) {
            yearChartEl.classList.remove("chart-quarter-activated")
            quarterChartEl.classList.remove("active");
        }

    })
    chart.addEventListener('updated', function (chart) {
        updateQuarterChart(chart, 'barQuarter')
    })

    /* distributed columns chart */
    var options = {
        series: [{
            data: [21, 22, 10, 28, 16, 21, 13, 30]
        }],
        chart: {
            height: 320,
            type: 'bar',
            events: {
                click: function (chart, w, e) {
                }
            }
        },
        colors: ['#6c5ffc', '#05c3fb', '#f7b731', '#49b6f5', '#e82646', '#09ad95', '#5b67c7', '#a65e76'],
        plotOptions: {
            bar: {
                columnWidth: '45%',
                distributed: true,
            }
        },
        dataLabels: {
            enabled: false
        },
        legend: {
            show: false
        },
        grid: {
            borderColor: '#f2f5f7',
        },
        xaxis: {
            categories: [
                ['John', 'Doe'],
                ['Joe', 'Smith'],
                ['Jake', 'Williams'],
                'Amber',
                ['Peter', 'Brown'],
                ['Mary', 'Evans'],
                ['David', 'Wilson'],
                ['Lily', 'Roberts'],
            ],
            labels: {
                style: {
                    colors: colors,
                    fontSize: '12px'
                }
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
    var chart = new ApexCharts(document.querySelector("#columns-distributed"), options);
    chart.render();

})();