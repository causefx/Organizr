<?php
if (file_exists('config' . DIRECTORY_SEPARATOR . 'config.php')) {
	$pageHomepage = '
<script>
!function($) {
    "use strict";
    var CalendarApp = function() {
        this.$body = $("body");
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
            defaultView: (activeInfo.mobile) ? "list" : "' . $GLOBALS['calendarDefault'] . '",
            firstDay: "' . $GLOBALS['calendarFirstDay'] . '",
            timeFormat: "' . $GLOBALS['calendarTimeFormat'] . '",
            handleWindowResize: true,
            header: {
               left: "prev,next,today",
               center: "title",
               right: (activeInfo.mobile) ? "" : "month,basicWeek,basicDay,list",
            },
            views: {
               basicDay: { buttonText: window.lang.translate("Day"), eventLimit: ' . $GLOBALS['calendarLimit'] . ' },
               basicWeek: { buttonText: window.lang.translate("Week"), eventLimit: ' . $GLOBALS['calendarLimit'] . ' },
               month: { buttonText: window.lang.translate("Month"), eventLimit: ' . $GLOBALS['calendarLimit'] . ' },
               list: { buttonText: window.lang.translate("List"), duration: {days: 15} },
            },
            timezone: "local",
            editable: false,
            navLinks: true, // can click day/week names to navigate views
            droppable: false, // this allows things to be dropped onto the calendar !!!
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
<div class="container-fluid p-t-30" id="homepage-items">
    ' . buildHomepage() . '
</div>
<div id="open-youtube" class="white-popup mfp-with-anim mfp-hide">
    <div class="col-md-8 col-md-offset-2 youtube-div">  </div>
</div>
<!-- /.container-fluid -->
';
}
