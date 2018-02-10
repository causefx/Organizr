<?php
if(file_exists('config'.DIRECTORY_SEPARATOR.'config.php')){
$pageHomepage = '
<script>
!function($) {
    "use strict";
    var CalendarApp = function() {
        this.$body = $("body")
        this.$calendar = $("#calendar"),
        this.$event = ("#calendar-events div.calendar-events"),
        this.$categoryForm = $("#add-new-event form"),
        this.$extEvents = $("#calendar-events"),
        this.$modal = $("#my-event"),
        this.$saveCategoryBtn = $(".save-category"),
        this.$calendarObj = null
    };
    /* Initializing */
    CalendarApp.prototype.init = function() {
        /*  Initialize the calendar  */
        var date = new Date();
        var d = date.getDate();
        var m = date.getMonth();
        var y = date.getFullYear();
        var form = "";
        var today = new Date($.now());
        var $this = this;
        $this.$calendarObj = $this.$calendar.fullCalendar({
            defaultView: "'.$GLOBALS['calendarDefault'].'",
            firstDay: "'.$GLOBALS['calendarFirstDay'].'",
            timeFormat: "'.$GLOBALS['calendarTimeFormat'].'",
            handleWindowResize: true,
            header: {
                left: "prev,next",
                center: "title",
                right: "month,basicDay,basicWeek"
            },
            timezone: "local",
            editable: false,
            droppable: false, // this allows things to be dropped onto the calendar !!!
            eventLimit: false, // allow "more" link when too many events
            //eventLimit: tof("'.$GLOBALS['calendarLimit'].'","b"), // allow "more" link when too many events
            selectable: false,
            height: "auto",
            eventRender: function eventRender( event, element, view ) {
            	if (typeof filter !== "undefined") {
            		if(filter === "all"){
            			return event.imagetype === event.imagetype;
            		}else if(filter !== "all"){
            			return filter === event.imagetype;
            		}
            		if(filter === null){
            			return event.imagetype === event.imagetype;
            		}
            	}else {
            		return event.imagetype === event.imagetype;
            	}
            },
        });
    },
   //init CalendarApp
    $.CalendarApp = new CalendarApp, $.CalendarApp.Constructor = CalendarApp
}(window.jQuery),
//initializing CalendarApp
function($) {
    "use strict";
    $.CalendarApp.init()
}(window.jQuery);
</script>
<link href="plugins/bower_components/owl.carousel/owl.carousel.min.css" rel="stylesheet" type="text/css" />
<link href="plugins/bower_components/owl.carousel/owl.theme.default.css" rel="stylesheet" type="text/css" />
<div class="container-fluid p-t-10" id="homepage-items">
    '.buildHomepage().'
</div>
<!-- /.container-fluid -->
';
}
