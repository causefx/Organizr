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
        	customButtons: {
			    filterCalendar: {
			      text: \'Filter\',
			      click: function() {
			        $(\'#calendar-filter-modal\').modal(\'show\');
			      }
			    }
			  },
            defaultView: (activeInfo.mobile) ? "list" : "' . $GLOBALS['calendarDefault'] . '",
            firstDay: "' . $GLOBALS['calendarFirstDay'] . '",
            timeFormat: "' . $GLOBALS['calendarTimeFormat'] . '",
            handleWindowResize: true,
            header: {
               left: "prev,next,today",
               center: "title",
               right: (activeInfo.mobile) ? "filterCalendar" : "filterCalendar,month,basicWeek,basicDay,list",
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
                        return event.imagetypeFilter === event.imagetypeFilter;
                    }else if(filter !== "all"){
                        return filter === event.imagetypeFilter;
                    }
                    if(filter === null){
                        return event.imagetypeFilter === event.imagetypeFilter;
                    }
                }else {
                    return event.imagetypeFilter === event.imagetypeFilter;
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
<!--  modal content -->
<div id="calendar-filter-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                <h4 class="modal-title" id="mySmallModalLabel" lane="en">Filter Calendar</h4> </div>
            <div class="modal-body">
            	<div class="row">
                    
                    <div class="col-md-12">
                        <label class="control-label" lang="en">Choose Media Type</label>
                        <select class="form-control form-white" data-placeholder="Choose media type" id="choose-calender-filter">
                            <option value="all">All</option>
                            <option value="tv">TV</option>
                            <option value="film">Movie</option>
                            <option value="music">Music</option>
                        </select>
                    </div>
                </div>
			</div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->
';
}
