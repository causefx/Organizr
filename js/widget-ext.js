$(document).ready(function () {
    "use strict";
    // This is for Vertical carousel
    $('.vcarousel').carousel({
        interval: 3000
    })


    //gauge chart
    $(".mtbutton").on("click", function () {
        var randomNum = Math.floor((Math.random() * 100));
        $('#gaugeDemo .gauge-arrow').trigger('updateGauge', randomNum);
    });
    $('#gaugeDemo .gauge-arrow').cmGauge();


    //chartist-chart
    var chart = new Chartist.Line('#ct-sales', {
        labels: ['1', '2', '3', '4', '5', '6'],
        series: [
    [1, -2, 5, 2, 6, 5.5]

  ]
    }, {
        showArea: true,
        showPoint: true,

        chartPadding: {
            left: -40
        },
        axisX: {
            showLabel: false,
            showGrid: false
        },
        axisY: {
            showLabel: false,
            showGrid: true
        },
        fullWidth: true,
        plugins: [
    Chartist.plugins.tooltip()
  ]
    });
    chart.on('draw', function (data) {

        if (data.type === 'line' || data.type === 'area') {
            data.element.animate({

                d: {
                    begin: 2000 * data.index,
                    dur: 2000,
                    from: data.path.clone().scale(1, 0).translate(0, data.chartRect.height()).stringify(),
                    to: data.path.clone().stringify(),
                    easing: Chartist.Svg.Easing.easeOutQuint
                }
            });
        }
    });


    // ct-weather
    var chart = new Chartist.Line('#ct-weather', {
        labels: ['1', '2', '3', '4', '5', '6'],
        series: [
    [1, 0, 5, 3, 2, 2.5]

  ]
    }, {
        showArea: true,
        showPoint: false,

        chartPadding: {
            left: -20
        },
        axisX: {
            showLabel: false,
            showGrid: false
        },
        axisY: {
            showLabel: false,
            showGrid: true
        },
        fullWidth: true,

    });


    //extra-chart
    var chart = new Chartist.Line('#ct-extra', {
        labels: ['1', '2', '3', '4', '5', '6'],
        series: [
    [1, -2, 5, 3, 0, 2.5]

  ]
    }, {
        showArea: true,
        showPoint: true,
        height: 100,
        chartPadding: {
            left: -20,
            top: 10,
        },
        axisX: {
            showLabel: false,
            showGrid: true
        },
        axisY: {
            showLabel: false,
            showGrid: false
        },
        fullWidth: true,
        plugins: [
    Chartist.plugins.tooltip()
  ]
    });


    //ct-main-balance-chart
    var chart = new Chartist.Line('#ct-main-bal', {
        labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9'],
        series: [
    [1, 2, 5, 3, 4, 2.5, 5, 3, 1],
    [1, 4, 2, 5, 2, 5.5, 3, 4, 1]
   ]

    }, {
        showArea: true,
        showPoint: true,
        height: 100,
        chartPadding: {
            left: -20,
            top: 10,
        },
        axisX: {
            showLabel: false,
            showGrid: false
        },
        axisY: {
            showLabel: false,
            showGrid: false
        },
        fullWidth: true,
        plugins: [
    Chartist.plugins.tooltip()
  ]
    });


    //ct-bar-chart
    new Chartist.Bar('#ct-bar-chart', {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        series: [
    [5, 4, 3, 7, 5, 2, 3]

  ]
    }, {
        axisX: {
            showLabel: false,
            showGrid: false,
            // On the x-axis start means top and end means bottom
            position: 'start'
        },

        chartPadding: {
            top: -20,
        },
        axisY: {
            showLabel: false,
            showGrid: false,
            // On the y-axis start means left and end means right
            position: 'end'
        },
        height: 65,
        plugins: [
    Chartist.plugins.tooltip()
  ]
    });


    //ct-visits
    new Chartist.Line('#ct-visits', {
        labels: ['12AM', '2AM', '6AM', '9AM', '12AM', '3PM', '6PM', '9PM'],
        series: [
    [5, 2, 7, 4, 5, 3, 5, 4],
    [2, 5, 2, 6, 2, 5, 2, 4]
  ]
    }, {
        top: 0,

        low: 1,
        showPoint: true,
        height: 210,
        fullWidth: true,
        plugins: [
    Chartist.plugins.tooltip()
  ],
        axisY: {
            labelInterpolationFnc: function (value) {
                return (value / 1) + 'k';
            }
        },
        showArea: true
    });


    //ct-weather
    new Chartist.Line('#ct-city-wth', {
        labels: ['12AM', '2AM', '6AM', '9AM', '12AM', '3PM', '6PM', '9PM'],
        series: [
    [5, 2, 7, 4, 5, 3, 5, 4]
  ]
    }, {
        chartPadding: {
            left: -20,
            top: 10,
        },
        low: 1,
        showPoint: true,
        height: 260,
        fullWidth: true,
        plugins: [
    Chartist.plugins.tooltip()
  ],
        axisX: {
            showLabel: true,
            showGrid: false
        },
        axisY: {
            showLabel: false,
            showGrid: false
        },
        showArea: true
    });


    //polar chart
    new Chartist.Line('#ct-polar-chart', {
        labels: [1, 2, 3, 4, 5, 6, 7, 8],
        series: [
    [1, 2, 3, 1, -2, 0, 1, 0],
    [-2, -1, -2, -1, -2.5, -1, -2, -1],
    [0, 0, 0, 1, 2, 2.5, 2, 1],
    [2.5, 2, 1, 0.5, 1, 0.5, -1, -2.5]
  ]
    }, {
        high: 3,
        low: -3,
        chartPadding: {
            left: -20,
            top: 10,
        },
        showArea: true,
        showLine: false,
        showPoint: true,
        fullWidth: true,
        plugins: [
    Chartist.plugins.tooltip()
  ],
        axisX: {
            showLabel: true,
            showGrid: true
        },
        axisY: {
            showLabel: false,
            showGrid: true
        }
    });


    // Morris donut chart
    Morris.Donut({
        element: 'morris-donut-chart',
        data: [{
            label: "Jan",
            value: 15,
    }, {
            label: "Feb",
            value: 15,
    }, {
            label: "Mar",
            value: 35,
    }, {
            label: "Apr",
            value: 105
        }],
        resize: true,
        colors: ['#ff7676', '#2cabe3', '#53e69d', '#7bcef3']
    });
    Morris.Area({
        element: 'morris-area-chart2',
        data: [{
                period: '2010',
                SiteA: 50,
                SiteB: 0,
        }, {
                period: '2011',
                SiteA: 160,
                SiteB: 100,
        }, {
                period: '2012',
                SiteA: 110,
                SiteB: 60,
        }, {
                period: '2013',
                SiteA: 60,
                SiteB: 200,
        }, {
                period: '2014',
                SiteA: 130,
                SiteB: 150,
        }, {
                period: '2015',
                SiteA: 200,
                SiteB: 90,
        }
        , {
                period: '2016',
                SiteA: 100,
                SiteB: 150,
        }],
        xkey: 'period',
        ykeys: ['SiteA', 'SiteB'],
        labels: ['Site A', 'Site B'],
        pointSize: 0,
        fillOpacity: 0.1,
        pointStrokeColors: ['#ff7878', '#2cabe3'],
        behaveLikeLine: true,
        gridLineColor: '#ffffff',
        lineWidth: 2,
        smooth: true,
        hideHover: 'auto',
        lineColors: ['#ff7878', '#2cabe3'],
        resize: true
    });

    // This is for Morris-chart-2
    Morris.Area({
        element: 'morris-area-chart3',
        data: [{
                period: '2010',
                SiteA: 0,
                SiteB: 0,

        }, {
                period: '2011',
                SiteA: 130,
                SiteB: 100,

        }, {
                period: '2012',
                SiteA: 80,
                SiteB: 60,

        }, {
                period: '2013',
                SiteA: 70,
                SiteB: 200,

        }, {
                period: '2014',
                SiteA: 180,
                SiteB: 150,

        }, {
                period: '2015',
                SiteA: 105,
                SiteB: 90,

        },
            {
                period: '2016',
                SiteA: 250,
                SiteB: 150,

        }],
        xkey: 'period',
        ykeys: ['SiteA', 'SiteB'],
        labels: ['Site A', 'Site B'],
        pointSize: 0,
        fillOpacity: 0.4,
        pointStrokeColors: ['#b4becb', '#2cabe3'],
        behaveLikeLine: true,
        gridLineColor: '#e0e0e0',
        lineWidth: 0,
        smooth: false,
        hideHover: 'auto',
        lineColors: ['#b4becb', '#2cabe3'],
        resize: true

    });

    // Real Time chart
    var data = [],
        totalPoints = 300;

    function getRandomData() {
        if (data.length > 0) data = data.slice(1);
        // Do a random walk
        while (data.length < totalPoints) {
            var prev = data.length > 0 ? data[data.length - 1] : 50,
                y = prev + Math.random() * 10 - 5;
            if (y < 0) {
                y = 0;
            } else if (y > 100) {
                y = 100;
            }
            data.push(y);
        }
        // Zip the generated y values with the x values
        var res = [];
        for (var i = 0; i < data.length; ++i) {
            res.push([i, data[i]])
        }
        return res;
    }
    // Set up the control widget
    var updateInterval = 30;
    $("#updateInterval").val(updateInterval).change(function () {
        var v = $(this).val();
        if (v && !isNaN(+v)) {
            updateInterval = +v;
            if (updateInterval < 1) {
                updateInterval = 1;
            } else if (updateInterval > 3000) {
                updateInterval = 3000;
            }
            $(this).val("" + updateInterval);
        }
    });
    var plot = $.plot("#placeholder", [getRandomData()], {
        series: {
            shadowSize: 0 // Drawing is faster without shadows
        },
        yaxis: {
            min: 0,
            max: 100
        },
        xaxis: {
            show: false
        },
        colors: ["#fff"],
        grid: {
            color: "#e45f5f",
            hoverable: true,
            borderWidth: 0,
            backgroundColor: '#ff7676'
        },
        tooltip: true,
        tooltipOpts: {
            content: "Y: %y",
            defaultTheme: false
        }
    });

    function update() {
        plot.setData([getRandomData()]);
        // Since the axes don't change, we don't need to call plot.setupGrid()
        plot.draw();
        setTimeout(update, updateInterval);
    }
    $(window).on("resize", function () {
        $.plot($('#placeholder'), [getRandomData()]);
    });
    update();


    // This is for Sparkline-chart

    var sparklineLogin = function () {
        $("#sparkline1dash").sparkline([0, 23, 43, 35, 44, 45, 56, 37, 40, 45, 56, 7, 10], {
            type: 'line',
            width: '100%',
            height: '70',
            lineColor: '#fff',
            fillColor: 'transparent',
            spotColor: '#fff',
            minSpotColor: undefined,
            maxSpotColor: undefined,
            highlightSpotColor: undefined,
            highlightLineColor: undefined
        });
        $('#sparkline2dash').sparkline([10, 12, 9, 6, 10, 9, 11, 9, 10, 12, 9, 11, 9, 10, 12, ], {
            type: 'bar',
            height: '70',
            barWidth: '5',
            resize: true,
            barSpacing: '10',
            barColor: '#fff'
        });
        $("#sparkline3dash").sparkline([0, 23, 43, 35, 44, 45, 56, 37, 40, 45, 56, 7, 10], {
            type: 'line',
            width: '100%',
            height: '70',
            lineColor: '#fff',
            fillColor: 'transparent',
            spotColor: '#fff',
            minSpotColor: undefined,
            maxSpotColor: undefined,
            highlightSpotColor: undefined,
            highlightLineColor: undefined
        });
        $('#sparkline4dash').sparkline([10, 12, 9, 6, 10, 9, 11, 9, 10, 12, 9, 11, 9, 10, 12, ], {
            type: 'bar',
            height: '70',
            barWidth: '5',
            resize: true,
            barSpacing: '10',
            barColor: '#fff'
        });
        $('#sales1').sparkline([20, 40, 30], {
            type: 'pie',
            height: '100',
            resize: true,
            sliceColors: ['#808f8f', '#fecd36', '#f1f2f7']
        });
        $('#sales2').sparkline([6, 10, 9, 11, 9, 10, 12], {
            type: 'bar',
            height: '154',
            barWidth: '4',
            resize: true,
            barSpacing: '10',
            barColor: '#2cabe3'
        });
        $("#sparkline8").sparkline([2, 4, 4, 6, 8, 5, 6, 4, 8, 6, 6, 2], {
            type: 'line',
            width: '100%',
            height: '50',
            lineColor: '#53e69d',
            fillColor: '#53e69d',
            maxSpotColor: '#53e69d',
            highlightLineColor: 'rgba(0, 0, 0, 0.2)',
            highlightSpotColor: '#53e69d'
        });
        $("#sparkline9").sparkline([0, 2, 8, 6, 8, 5, 6, 4, 8, 6, 6, 2], {
            type: 'line',
            width: '100%',
            height: '50',
            lineColor: '#2cabe3',
            fillColor: '#2cabe3',
            minSpotColor: '#2cabe3',
            maxSpotColor: '#2cabe3',
            highlightLineColor: 'rgba(0, 0, 0, 0.2)',
            highlightSpotColor: '#2cabe3'
        });
        $("#sparkline10").sparkline([2, 4, 4, 6, 8, 5, 6, 4, 8, 6, 6, 2], {
            type: 'line',
            width: '100%',
            height: '50',
            lineColor: '#ff754b',
            fillColor: '#ff754b',
            maxSpotColor: '#ff754b',
            highlightLineColor: 'rgba(0, 0, 0, 0.2)',
            highlightSpotColor: '#ff754b'
        });

    }
    var sparkResize;

    $(window).on("resize", function (e) {
        clearTimeout(sparkResize);
        sparkResize = setTimeout(sparklineLogin, 100);
    });
    sparklineLogin();
});
