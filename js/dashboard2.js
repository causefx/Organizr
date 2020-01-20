$(document).ready(function () {
    "use strict";
    //gauge chart
    $(".mtbutton").on("click", function () {
        var randomNum = Math.floor((Math.random() * 100));
        $('#gaugeDemo .gauge-arrow').trigger('updateGauge', randomNum);
    });
    $('#gaugeDemo .gauge-arrow').cmGauge();
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
            offset: 0

        },
        fullWidth: true,
        height: 65,
        plugins: [
    Chartist.plugins.tooltip()
  ]
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
        backgroundColor: '#353c48',
        labelColor: '#96a2b4',
        colors: ['#ff7676', '#2cabe3', '#53e69d', '#7bcef3']
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
            color: "rgba(255, 255, 255, 0.3)",
            hoverable: true,
            borderWidth: 0

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
    $(window).resize(function () {
        $.plot($('#placeholder'), [getRandomData()]);
    });
    update();
});
