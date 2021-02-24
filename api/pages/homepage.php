<?php
$GLOBALS['organizrPages'][] = 'homepage';
function get_page_homepage($Organizr = null)
{
	if (!$Organizr) {
		$Organizr = new Organizr();
	}
	if ((!$Organizr->hasDB())) {
		return false;
	}
	return '
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
        	locale: "' . $Organizr->config['calendarLocale'] . '",
        	customButtons: {
			    filterCalendar: {
			      text: \'Filter\',
			      click: function() {
			        toggleCalendarFilter();
			      },
			      //icon: \'x\'
			    },
			    refreshCalendar: {
			      text: \'Refresh\',
			      click: function() {
			        homepageCalendar();
			      }
			    }
			  },
            defaultView: (activeInfo.mobile) ? "list" : "' . $Organizr->config['calendarDefault'] . '",
            firstDay: "' . $Organizr->config['calendarFirstDay'] . '",
            timeFormat: "' . $Organizr->config['calendarTimeFormat'] . '",
            handleWindowResize: true,
            header: {
               left: "prev,next,today",
               center: "title",
               right: (activeInfo.mobile) ? "refreshCalendar,filterCalendar" : "refreshCalendar,filterCalendar,month,basicWeek,basicDay,list",
            },
            views: {
               basicDay: { buttonText: window.lang.translate("Day"), eventLimit: ' . $Organizr->config['calendarLimit'] . ' },
               basicWeek: { buttonText: window.lang.translate("Week"), eventLimit: ' . $Organizr->config['calendarLimit'] . ' },
               month: { buttonText: window.lang.translate("Month"), eventLimit: ' . $Organizr->config['calendarLimit'] . ' },
               list: { buttonText: window.lang.translate("List"), duration: {days: 15} },
            },
            timezone: "local",
            editable: false,
            navLinks: true, // can click day/week names to navigate views
            droppable: false, // this allows things to be dropped onto the calendar !!!
            selectable: false,
            height: "auto",
            eventRender: function eventRender( event, element, view ) {
                if (typeof filter !== "undefined" && filterDownload !== "undefined") {
                    if(filter === "all" && filterDownload === "all"){
                        return (event.imagetypeFilter === event.imagetypeFilter && event.downloadFilter === event.downloadFilter);
                    }else if(filter !== "all" && filterDownload !== "all"){
                        return filter === event.imagetypeFilter && filterDownload === event.downloadFilter;
                    }else if(filter !== "all" && filterDownload === "all"){
                        return filter === event.imagetypeFilter && event.downloadFilter === event.downloadFilter;
                    }else if(filter === "all" && filterDownload !== "all"){
                        return event.imagetypeFilter === event.imagetypeFilter && filterDownload === event.downloadFilter;
                    }
                }else {
                    return event.imagetypeFilter === event.imagetypeFilter && event.downloadFilter === event.downloadFilter;
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
$(".homepage-loading-box").fadeOut(5000);
</script>
<div class="container-fluid p-t-30" id="homepage-items">
    ' . $Organizr->buildHomepage() . '
</div>
<div id="open-youtube" class="white-popup mfp-with-anim mfp-hide">
    <div class="col-md-8 col-md-offset-2 youtube-div">  </div>
</div>
<!-- /.container-fluid -->

';
}
