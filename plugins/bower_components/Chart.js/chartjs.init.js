$( document ).ready(function() {
    
    var ctx1 = document.getElementById("chart1").getContext("2d");
    var data1 = {
        labels: ["January", "February", "March", "April", "May", "June", "July"],
        datasets: [
            {
                label: "My Second dataset",
                fillColor: "rgba(243,245,246,0.9)",
                strokeColor: "rgba(243,245,246,0.9)",
                pointColor: "rgba(243,245,246,0.9)",
                pointStrokeColor: "#fff",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(243,245,246,0.9)",
                data: [28, 48, 40, 19, 86, 27, 90]
            },
            {
                label: "My First dataset",
                fillColor: "rgba(44,171,227,0.8)",
                strokeColor: "rgba(44,171,227,0.8)",
                pointColor: "rgba(44,171,227,1)",
                pointStrokeColor: "#fff",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(44,171,227,1)",
                data: [0, 59, 80, 58, 20, 55, 40]
            }
            
        ]
    };
    
    var chart1 = new Chart(ctx1).Line(data1, {
        scaleShowGridLines : true,
        scaleGridLineColor : "rgba(0,0,0,.005)",
        scaleGridLineWidth : 0,
        scaleShowHorizontalLines: true,
        scaleShowVerticalLines: true,
        bezierCurve : true,
        bezierCurveTension : 0.4,
        pointDot : true,
        pointDotRadius : 4,
        pointDotStrokeWidth : 1,
        pointHitDetectionRadius : 2,
        datasetStroke : true,
		tooltipCornerRadius: 2,
        datasetStrokeWidth : 2,
        datasetFill : true,
        legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].strokeColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>",
        responsive: true
    });
    
    var ctx2 = document.getElementById("chart2").getContext("2d");
    var data2 = {
        labels: ["January", "February", "March", "April", "May", "June", "July"],
        datasets: [
            {
                label: "My First dataset",
                fillColor: "rgba(255,118,118,0.8)",
                strokeColor: "rgba(255,118,118,0.8)",
                highlightFill: "rgba(255,118,118,1)",
                highlightStroke: "rgba(255,118,118,1)",
                data: [10, 30, 80, 61, 26, 75, 40]
            },
            {
                label: "My Second dataset",
                fillColor: "rgba(180,193,215,0.8)",
                strokeColor: "rgba(180,193,215,0.8)",
                highlightFill: "rgba(180,193,215,1)",
                highlightStroke: "rgba(180,193,215,1)",
                data: [28, 48, 40, 19, 86, 27, 90]
            }
        ]
    };
    
    var chart2 = new Chart(ctx2).Bar(data2, {
        scaleBeginAtZero : true,
        scaleShowGridLines : true,
        scaleGridLineColor : "rgba(0,0,0,.005)",
        scaleGridLineWidth : 0,
        scaleShowHorizontalLines: true,
        scaleShowVerticalLines: true,
        barShowStroke : true,
        barStrokeWidth : 0,
		tooltipCornerRadius: 2,
        barDatasetSpacing : 3,
        legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].fillColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>",
        responsive: true
    });
    
    var ctx3 = document.getElementById("chart3").getContext("2d");
    var data3 = [
        {
            value: 300,
            color:"#2cabe3",
            highlight: "#2cabe3",
            label: "Blue"
        },
        {
            value: 50,
            color: "#edf1f5",
            highlight: "#edf1f5",
            label: "Light"
        },
		 {
            value: 50,
            color: "#b4c1d7",
            highlight: "#b4c1d7",
            label: "Dark"
        },
		 {
            value: 50,
            color: "#53e69d",
            highlight: "#53e69d",
            label: "Megna"
        },
        {
            value: 100,
            color: "#ff7676",
            highlight: "#ff7676",
            label: "Orange"
        }
    ];
    
    var myPieChart = new Chart(ctx3).Pie(data3,{
        segmentShowStroke : true,
        segmentStrokeColor : "#fff",
        segmentStrokeWidth : 0,
        animationSteps : 100,
		tooltipCornerRadius: 0,
        animationEasing : "easeOutBounce",
        animateRotate : true,
        animateScale : false,
        legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><li><span style=\"background-color:<%=segments[i].fillColor%>\"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>",
        responsive: true
    });
    
    var ctx4 = document.getElementById("chart4").getContext("2d");
    var data4 = [
        {
            value: 300,
            color:"#2cabe3",
            highlight: "#2cabe3",
            label: "Blue"
        },
        {
            value: 50,
            color: "#53e69d",
            highlight: "#53e69d",
            label: "Green"
        },
        {
            value: 100,
            color: "#ff7676",
            highlight: "#ff7676",
            label: "Orange"
        }
    ];
    
    var myDoughnutChart = new Chart(ctx4).Doughnut(data4,{
        segmentShowStroke : true,
        segmentStrokeColor : "#fff",
        segmentStrokeWidth : 0,
        animationSteps : 100,
		tooltipCornerRadius: 2,
        animationEasing : "easeOutBounce",
        animateRotate : true,
        animateScale : false,
        legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><li><span style=\"background-color:<%=segments[i].fillColor%>\"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>",
        responsive: true
    });
    
    var ctx5 = document.getElementById("chart5").getContext("2d");
    var data5 =  [
        {
            value: 300,
            color:"#2cabe3",
            highlight: "#2cabe3",
            label: "Blue"
        },
        {
            value: 50,
            color: "#53e69d",
            highlight: "#53e69d",
            label: "Green"
        },
        {
            value: 100,
            color: "#ff7676",
            highlight: "#ff7676",
            label: "Orange"
        },
        {
            value: 40,
            color: "#949FB1",
            highlight: "#A8B3C5",
            label: "Grey"
        }

    ];
    
    var myPolarArea = new Chart(ctx5).PolarArea(data5, {
        scaleShowLabelBackdrop : true,
        scaleBackdropColor : "rgba(255,255,255,0.75)",
        scaleBeginAtZero : true,
        scaleBackdropPaddingY : 2,
        scaleBackdropPaddingX : 2,
        scaleShowLine : true,
        segmentShowStroke : true,
        segmentStrokeColor : "#fff",
        segmentStrokeWidth : 2,
        animationSteps : 100,
		tooltipCornerRadius: 2,
        animationEasing : "easeOutBounce",
        animateRotate : true,
        animateScale : false,
        legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><li><span style=\"background-color:<%=segments[i].fillColor%>\"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>",
        responsive: true
    });
    
    var ctx6 = document.getElementById("chart6").getContext("2d");
    var data6 = {
        labels: ["Eating", "Drinking", "Sleeping", "Designing", "Coding", "Cycling", "Running"],
        datasets: [
            {
                label: "My First dataset",
                fillColor: "rgba(44,171,227,0.8)",
                strokeColor: "rgba(44,171,227,1)",
                pointColor: "rgba(44,171,227,1)",
                pointStrokeColor: "#fff",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(19,218,254,1)",
                data: [65, 59, 90, 81, 56, 55, 40]
            },
            {
                label: "My Second dataset",
                fillColor: "rgba(255,118,118,0.8)",
                strokeColor: "rgba(255,118,118,1)",
                pointColor: "rgba(255,118,118,1)",
                pointStrokeColor: "#fff",
                pointHighlightFill: "#fff",
                pointHighlightStroke: "rgba(255,118,118,1)",
                data: [28, 48, 40, 19, 96, 27, 100]
            }
        ]
    };
    
    var myRadarChart = new Chart(ctx6).Radar(data6, {
        scaleShowLine : true,
        angleShowLineOut : true,
        scaleShowLabels : false,
        scaleBeginAtZero : true,
        angleLineColor : "rgba(0,0,0,.1)",
        angleLineWidth : 1,
        pointLabelFontFamily : "'Arial'",
        pointLabelFontStyle : "normal",
        pointLabelFontSize : 10,
        pointLabelFontColor : "#666",
        pointDot : true,
        pointDotRadius : 3,
		tooltipCornerRadius: 2,
        pointDotStrokeWidth : 1,
        pointHitDetectionRadius : 20,
        datasetStroke : true,
        datasetStrokeWidth : 2,
        datasetFill : true,
        legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].strokeColor%>\"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>",
        responsive: true
    });
    
});