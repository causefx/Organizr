$(document).ready(function() {
    
    var drag =  function() {
        $('.calendar-event').each(function() {

        // store data so the calendar knows to render an event upon drop
        $(this).data('event', {
            title: $.trim($(this).text()), // use the element's text as the event title
            stick: true // maintain when user navigates (see docs on the renderEvent method)
        });

        // make the event draggable using jQuery UI
        $(this).draggable({
            zIndex: 1111999,
            revert: true,      // will cause the event to go back to its
            revertDuration: 0  //  original position after the drag
        });
    });
    };
    
    var removeEvent =  function() {
        $('.remove-calendar-event').click(function() {
        $(this).closest('.calendar-event').fadeOut();
        return false;
    });
    };
    
    $(".add-event").keypress(function (e) {
        if ((e.which == 13)&&(!$(this).val().length == 0)) {
            $('<div class="calendar-event">' + $(this).val() + '<a href="javascript:void(0);" class="remove-calendar-event"><i class="ti-close"></i></a></div>').insertBefore(".add-event");
            $(this).val('');
        } else if(e.which == 13) {
            alert('Please enter event name');
        }
        drag();
        removeEvent();
    });
    
    
    drag();
    removeEvent();
    
    var date = new Date();
    var day = date.getDate();
    var month = date.getMonth();
    var year = date.getFullYear();
    
    $('#calendar').fullCalendar({
       
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            editable: true,
            droppable: true, // this allows things to be dropped onto the calendar
            eventLimit: true, // allow "more" link when too many events
            events: [
                {
                    title: 'All Day Event',
                    start: new Date(year, month, day-8)
                },
                {
                    title: 'Long Event',
                    start: new Date(year, month, day-5),
                    end: new Date(year, month, day-2)
                },
                {
                    id: 999,
                    title: 'Repeating Event',
                    start: new Date(year, month, day)
                },
                {
                    id: 999,
                    title: 'Repeating Event',
                    start: new Date(year, month, day+7)
                },
                {
                    title: 'Conference',
                    start: new Date(year, month, day+3),
                    end: new Date(year, month, day+6)
                },
                {
                    title: 'Meeting',
                    start: new Date(year, month, day+5)
                },
                {
                    title: 'Lunch',
                    start: new Date(year, month, day+7)
                },
                {
                    title: 'Meeting',
                    start: new Date(year, month, day+10)
                },
                {
                    title: 'Happy Hour',
                    start: new Date(year, month, day+10)
                },
                {
                    title: 'Dinner',
                    start: new Date(year, month, day+13)
                },
                {
                    title: 'Birthday Party',
                    start: new Date(year, month, day+15)
                },
                {
                    title: 'Click for Google',
                    url: 'http://google.com/',
                    start: new Date(year, month, day+18)
                }
            ]
        });
    
});