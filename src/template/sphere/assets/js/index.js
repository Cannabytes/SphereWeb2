"use strict";

// CHARTJS SALES CHART OPEN
var ctx = document.getElementById("saleschart").getContext("2d");
new Chart(ctx, {
  type: "bar",
  data: {
    labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
    datasets: [
      {
        barPercentage: 0.1,
        barThickness: 6,
        barGap: 0,
        maxBarThickness: 8,
        minBarLength: 2,
        label: "Total Sales",
        data: [19, 10, 15, 8, 6, 10, 13],
        backgroundColor: [
          "rgba(5, 195, 251, 0.2)",
          "rgba(5, 195, 251, 0.2)",
          "#05c3fb",
          "rgba(5, 195, 251, 0.2)",
          "rgba(5, 195, 251, 0.2)",
          "#05c3fb",
          "#05c3fb",
        ],
        borderColor: [
          "rgba(5, 195, 251, 0.5)",
          "rgba(5, 195, 251, 0.5)",
          "#05c3fb",
          "rgba(5, 195, 251, 0.5)",
          "rgba(5, 195, 251, 0.5)",
          "#05c3fb",
          "#05c3fb",
        ],
        pointBorderWidth: 2,
        pointRadius: 2,
        pointHoverRadius: 2,
        borderRadius: 10,
        borderWidth: 1,
      },
    ],
  },
  options: {
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false,
        labels: {
          display: false,
        },
      },
      tooltip: {
        enabled: false,
      },
    },
    responsive: true,
    scales: {
      x: {
        categoryPercentage: 1.0,
        barPercentage: 1.0,
        barDatasetSpacing: 0,
        display: false,
        barThickness: 5,
        barRadius: 0,
        gridLines: {
          color: "transparent",
          zeroLineColor: "transparent",
        },
        ticks: {
          fontSize: 2,
          fontColor: "transparent",
        },
      },
      y: {
        display: false,
        ticks: {
          display: false,
        },
      },
    },
    title: {
      display: false,
    },
    elements: {
      point: {
        radius: 0,
      },
    },
  },
});
// CHARTJS SALES CHART CLOSED

// CHARTJS LEADS CHART  OPEN
var ctx1 = document.getElementById("leadschart").getContext("2d");
new Chart(ctx1, {
  type: "line",
  data: {
    labels: [
      "Date 1",
      "Date 2",
      "Date 3",
      "Date 4",
      "Date 5",
      "Date 6",
      "Date 7",
      "Date 8",
      "Date 9",
      "Date 10",
      "Date 11",
      "Date 12",
      "Date 13",
      "Date 14",
      "Date 15",
    ],
    datasets: [
      {
        label: "Total Sales",
        data: [
          45, 23, 32, 67, 49, 72, 52, 55, 46, 54, 32, 74, 88, 36, 36, 32, 48,
          54,
        ],
        backgroundColor: "transparent",
        borderColor: "#f46ef4",
        borderWidth: "2.5",
        pointBorderColor: "transparent",
        pointBackgroundColor: "transparent",
        lineTension: 0.3,
      },
    ],
  },
  options: {
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false,
        labels: {
          display: false,
        },
      },
      tooltip: {
        enabled: false,
      },
    },
    responsive: true,
    scales: {
      x: {
        ticks: {
          beginAtZero: true,
          fontSize: 10,
          fontColor: "transparent",
        },
        title: {
          display: false,
        },
        grid: {
          display: true,
          color: "transparent																																					",
          drawBorder: false,
        },
        categoryPercentage: 1.0,
        barPercentage: 1.0,
        barDatasetSpacing: 0,
        display: false,
        barThickness: 5,
      },
      y: {
        display: false,
        ticks: {
          display: false,
        },
      },
    },
    title: {
      display: false,
    },
    elements: {
      point: {
        radius: 0,
      },
    },
  },
});
// CHARTJS LEADS CHART CLOSED

// PROFIT CHART OPEN
var ctx2 = document.getElementById("profitchart").getContext("2d");
new Chart(ctx2, {
  type: "bar",
  data: {
    labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
    datasets: [
      {
        barPercentage: 0.1,
        barThickness: 6,
        maxBarThickness: 8,
        minBarLength: 2,
        label: "Total Sales",
        barGap: 0,
        barSizeRatio: 1,
        data: [10, 12, 5, 6, 3, 11, 12],
        backgroundColor: "#4ecc48",
        borderColor: "#4ecc48",
        pointBackgroundColor: "#fff",
        pointHoverBackgroundColor: "#4ecc48",
        pointBorderColor: "#4ecc48",
        pointHoverBorderColor: "#4ecc48",
        pointBorderWidth: 2,
        pointRadius: 2,
        pointHoverRadius: 2,
        borderRadius: 10,
        borderWidth: 1,
      },
    ],
  },
  options: {
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false,
        labels: {
          display: false,
        },
      },
      tooltip: {
        enabled: false,
      },
    },
    responsive: true,
    scales: {
      x: {
        categoryPercentage: 1.0,
        barPercentage: 1.0,
        barDatasetSpacing: 0,
        display: false,
        barThickness: 5,
        gridLines: {
          color: "transparent",
          zeroLineColor: "transparent",
        },
        ticks: {
          fontSize: 2,
          fontColor: "transparent",
        },
      },
      y: {
        display: false,
        ticks: {
          display: false,
        },
      },
    },
    title: {
      display: false,
    },
    elements: {
      point: {
        radius: 0,
      },
    },
  },
});
// PROFIT CHART CLOSED

// COST CHART OPEN
var ctx3 = document.getElementById("costchart").getContext("2d");
new Chart(ctx3, {
  type: "line",
  data: {
    labels: [
      "Date 1",
      "Date 2",
      "Date 3",
      "Date 4",
      "Date 5",
      "Date 6",
      "Date 7",
      "Date 8",
      "Date 9",
      "Date 10",
      "Date 11",
      "Date 12",
      "Date 13",
      "Date 14",
      "Date 15",
      "Date 16",
      "Date 17",
    ],
    datasets: [
      {
        label: "Total Sales",
        data: [
          28, 56, 36, 32, 48, 54, 37, 58, 66, 53, 21, 24, 14, 45, 0, 32, 67, 49,
          52, 55, 46, 54, 130,
        ],
        backgroundColor: "transparent",
        borderColor: "#f7ba48",
        borderWidth: "2.5",
        pointBorderColor: "transparent",
        pointBackgroundColor: "transparent",
        lineTension: 0.3,
      },
    ],
  },
  options: {
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false,
        labels: {
          display: false,
        },
      },
      tooltip: {
        enabled: false,
      },
    },
    responsive: true,
    scales: {
      x: {
        categoryPercentage: 1.0,
        barPercentage: 1.0,
        barDatasetSpacing: 0,
        display: false,
        barThickness: 5,
        gridLines: {
          color: "transparent",
          zeroLineColor: "transparent",
        },
        ticks: {
          fontSize: 2,
          fontColor: "transparent",
        },
      },
      y: {
        display: false,
        ticks: {
          display: false,
        },
      },
    },
    title: {
      display: false,
    },
    elements: {
      point: {
        radius: 0,
      },
    },
  },
});
// COST CHART CLOSED


// TRANSACTIONS OPEN
var myCanvas = document.getElementById("transactions");
myCanvas.height = "330";

var myCanvasContext = myCanvas.getContext("2d");
var gradientStroke1 = myCanvasContext.createLinearGradient(0, 80, 0, 280);
gradientStroke1.addColorStop(0, 'rgba(108, 95, 252, 0.8)');
gradientStroke1.addColorStop(1, 'rgba(108, 95, 252, 0.2) ');

var gradientStroke2 = myCanvasContext.createLinearGradient(0, 80, 0, 280);
gradientStroke2.addColorStop(0, 'rgba(5, 195, 251, 0.8)');
gradientStroke2.addColorStop(1, 'rgba(5, 195, 251, 0.2) ');
document.getElementById('transactions').innerHTML = '';
var myChart;
myChart = new Chart(myCanvas, {

    type: 'line',
    data: {
        labels: ["Jan", "Feb", "Mar", "Apr", "May", "June", "July", "Aug", "Sep", "Oct", "Nov", "Dec"],
        type: 'line',
        datasets: [{
            label: 'Total Sales',
            data: [100, 210, 180, 454, 454, 230, 230, 656, 656, 350, 350, 210],
            backgroundColor: gradientStroke1,
            borderColor: "#05c3fb",
            pointBackgroundColor: '#fff',
            pointHoverBackgroundColor: gradientStroke1,
            pointBorderColor: "#05c3fb",
            pointHoverBorderColor: gradientStroke1,
            pointBorderWidth: 0,
            pointRadius: 0,
            pointHoverRadius: 0,
            borderWidth: 3,
            fill: 'origin',
            lineTension: 0.3
        }, {
            label: 'Total Orders',
            data: [200, 530, 110, 110, 480, 520, 780, 435, 475, 738, 454, 454],
            backgroundColor: 'transparent',
            borderColor: "#05c3fb",
            pointBackgroundColor: '#fff',
            pointHoverBackgroundColor: gradientStroke2,
            pointBorderColor: "#05c3fb",
            pointHoverBorderColor: gradientStroke2,
            pointBorderWidth: 0,
            pointRadius: 0,
            pointHoverRadius: 0,
            borderWidth: 3,
            fill: 'origin',
            lineTension: 0.3

        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
                labels: {
                    usePointStyle: false,
                }
            },
            tooltip: {
                enabled: false
            }
        },
        scales: {
            x: {
                display: true,
                grid: {
                    display: false,
                    drawBorder: false,
                    color: 'rgba(119, 119, 142, 0.08)'
                },
                ticks: {
                    autoSkip: true,
                    color: '#b0bac9'
                },
                scaleLabel: {
                    display: false,
                    labelString: 'Month',
                    fontColor: 'transparent'
                }
            },
            y: {
                ticks: {
                    min: 0,
                    max: 1050,
                    stepSize: 150,
                    color: "#b0bac9",
                },
                display: true,
                grid: {
                    display: true,
                    drawBorder: false,
                    zeroLineColor: 'rgba(142, 156, 173,0.1)',
                    color: "rgba(142, 156, 173,0.1)",
                },
                scaleLabel: {
                    display: false,
                    labelString: 'sales',
                    fontColor: 'transparent'
                }
            }
        },
        title: {
            display: false,
            text: 'Normal Legend'
        }
    }
});
function index(myVarVal, myVarVal1) {
    'use strict'
    let gradientStroke = myCanvasContext.createLinearGradient(0, 80, 0, 280);;
    gradientStroke.addColorStop(0, `rgba(${myVarVal}, 0.8)` || 'rgba(108, 95, 252, 0.8)');
    gradientStroke.addColorStop(1, `rgba(${myVarVal}, 0.2)` || 'rgba(108, 95, 252, 0.2) ');

    myChart.data.datasets[0] = {
        label: 'Total Sales',
        data: [100, 210, 180, 454, 454, 230, 230, 656, 656, 350, 350, 210],
        backgroundColor: gradientStroke,
        borderColor: `rgb(${myVarVal})`,
        pointBackgroundColor: '#fff',
        pointHoverBackgroundColor: gradientStroke,
        pointBorderColor: `rgb(${myVarVal})`,
        pointHoverBorderColor: gradientStroke,
        pointBorderWidth: 0,
        pointRadius: 0,
        pointHoverRadius: 0,
        borderWidth: 3,
        fill: 'origin',
        lineTension: 0.3
    }
    myChart.update();

}

// TRANSACTIONS CLOSED

// RECENT ORDERS OPEN
var myCanvas = document.getElementById("recentorders");
myCanvas.height = "242";
new Chart(myCanvas, {
    type: 'bar',
    data: {
        labels: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
        datasets: [{
            barThickness: 8,
            label: 'This Month',
            data: [27, 50, 28, 50, 28, 30, 22],
            backgroundColor: '#61c9fc',
            borderWidth: 2,
            hoverBackgroundColor: '#61c9fc',
            hoverBorderWidth: 0,
            borderColor: '#61c9fc',
            hoverBorderColor: '#61c9fc',
            borderRadius: 10,
        }, {
            barThickness: 8,
            label: 'This Month',
            data: [32, 58, 68, 65, 40, 68, 58],
            backgroundColor: '#f38ff3',
            borderWidth: 2,
            hoverBackgroundColor: '#f38ff3',
            hoverBorderWidth: 0,
            borderColor: '#f38ff3',
            hoverBorderColor: '#f38ff3',
            borderRadius: 10,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        layout: {
            padding: {
                left: 0,
                right: 0,
                top: 0,
                bottom: 0
            }
        },
        plugins: {
            legend: {
                display: false,
                labels: {
                    display: false
                }
            },
            tooltip: {
                enabled: false
            }
        },
        scales: {
            y: {
                display: false,
                grid: {
                    display: false,
                    drawBorder: false,
                    zeroLineColor: 'rgba(142, 156, 173,0.1)',
                    color: "rgba(142, 156, 173,0.1)",
                },
                scaleLabel: {
                    display: false,
                },
                ticks: {
                    beginAtZero: true,
                    stepSize: 25,
                    suggestedMin: 0,
                    suggestedMax: 100,
                    fontColor: "#8492a6",
                    userCallback: function (tick) {
                        return tick.toString() + '%';
                    }
                },
            },
            x: {
                display: false,
                stacked: false,
                ticks: {
                    beginAtZero: true,
                    fontColor: "#8492a6",
                },
                grid: {
                    color: "rgba(142, 156, 173,0.1)",
                    display: false
                },

            }
        },
        legend: {
            display: false
        },
        elements: {
            point: {
                radius: 0
            }
        }
    }
});
// RECENT ORDERS CLOSED

// RECENT ORDERS2 OPEN
var chartData = [
    {
        name: 'Series 1',
        data: [
            65, 55, 50, 49, 51, 55, 47, 45, 53, 52, 49, 50, 48, 44, 40, 41, 45, 44, 41, 38,
            39, 41, 39, 35, 38, 38, 40, 38, 42, 46, 43, 40, 36, 31, 28, 29, 29, 33, 37, 35,
            37, 39, 39, 34, 37, 39, 38, 37, 40, 35, 31, 31, 30, 25, 28, 28, 30, 32, 32, 37,
            35, 39, 41, 41, 43, 39, 39, 43, 42, 43, 38, 43, 41, 44, 46, 47, 49, 46, 51, 50,
            53, 56, 52, 56, 60, 58, 56, 55, 54, 54, 58, 57, 60, 57, 56, 55, 54, 52, 54, 54
        ],
    },
];

var chartOptions = {
    chart: {
        type: 'line',
        height: '200',
        background: 'transparent',
        toolbar: {
            show: false, // Hide the chart toolbar
        },
    },
    dataLabels: {
        enabled: false,
    },
    colors: ['#ffffff'],
    stroke: {
        curve: 'smooth',
        width: 1,
    },
    fill: {
        opacity: 0.2,
        type: 'solid',
    },
    series: chartData,
    xaxis: {
        labels: {
            show: false, // Hide x-axis labels
        },
        axisBorder: {
            show: false, // Hide x-axis border
        },
    },
    yaxis: {
        show: false, // Hide the y-axis
        axisBorder: {
            show: false, // Hide y-axis border
        },
    },
    grid: {
        show: false, // Hide grid lines
    },
    legend: {
        show: false, // Hide legend
    },
};

var chart = new ApexCharts(document.querySelector('#back-chart'), chartOptions);

chart.render();

// RECENT ORDERS2 CLOSED


/* Visitors By Country Map */
var markers = [{
    name: 'Usa',
    coords: [40.3, -101.38]
},
{
    name: 'India',
    coords: [20.5937, 78.9629]
},
{
    name: 'Vatican City',
    coords: [41.90, 12.45]
},
{
    name: 'Canada',
    coords: [56.1304, -106.3468]
},
{
    name: 'Bahrain',
    coords: [-20.2, 57.5]
},
];
var map = new jsVectorMap({
    map: 'world_merc',
    selector: '#visitors-countries',
    markersSelectable: true,
    zoomOnScroll: false,
    zoomButtons: false,

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
/* Visitors By Country Map */
