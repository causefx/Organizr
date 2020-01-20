 $(document).ready(function () {
     "use strict";
     // toat popup js
     $.toast({
         heading: 'Welcome to Ample admin',
         text: 'Use the predefined ones, or specify a custom position object.',
         position: 'top-right',
         loaderBg: '#fff',
         icon: 'warning',
         hideAfter: 3500,
         stack: 6
     })
     $('#calendar').fullCalendar('option', 'height', 745);
     // Dashboard 1 Morris-chart
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
         pointStrokeColors: ['#79e580', '#2cabe3'],
         behaveLikeLine: true,
         gridLineColor: '#ffffff',
         lineWidth: 2,
         smooth: true,
         hideHover: 'auto',
         lineColors: ['#79e580', '#2cabe3'],
         resize: true
     });

     //ct-bar-chart
     new Chartist.Bar('#ct-daily-sales', {
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
             left: 45,
         },
         axisY: {
             showLabel: false,
             showGrid: false,
             // On the y-axis start means left and end means right
             position: 'end'
         },
         height: 335,
         plugins: [
    Chartist.plugins.tooltip()
  ]
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
     //ct-visits
     new Chartist.Line('#ct-visits', {
         labels: ['2008', '2009', '2010', '2011', '2012', '2013', '2014', '2015'],
         series: [
    [5, 2, 7, 4, 5, 3, 5, 4],
    [2, 5, 2, 6, 2, 5, 2, 4]
  ]
     }, {
         top: 0,

         low: 1,
         showPoint: true,

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
     // counter
     $(".counter").counterUp({
         delay: 100,
         time: 1200
     });

 });
