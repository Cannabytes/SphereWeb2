(function () {
    "use strict";
    //_____Calendar Events Intialization
  
    // sample calendar events data
    var curYear = moment().format('YYYY');
    var curMonth = moment().format('MM');
    // Calendar Event Source
    var sptCalendarEvents = {
      id: 1,
      events: [{
        id: '1',
        start: curYear + '-' + curMonth + '-02',
        end: curYear + '-' + curMonth + '-03',
        title: 'Spruko Meetup',
        backgroundColor: '#6c5ffc',
        borderColor: '#6c5ffc',
        description: 'All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary'
      }, {
        id: '2',
        start: curYear + '-' + curMonth + '-17',
        end: curYear + '-' + curMonth + '-17',
        title: 'Design Review',
        backgroundColor: '#05c3fb',
        borderColor: '#05c3fb',
        description: 'All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary'
      }, {
        id: '3',
        start: curYear + '-' + curMonth + '-13',
        end: curYear + '-' + curMonth + '-13',
        title: 'Lifestyle Conference',
        backgroundColor: '#6c5ffc',
        borderColor: '#6c5ffc',
        description: 'All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary'
      }, {
        id: '4',
        start: curYear + '-' + curMonth + '-21',
        end: curYear + '-' + curMonth + '-21',
        title: 'Team Weekly Brownbag',
        backgroundColor: '#f7b731',
        borderColor: '#f7b731',
        description: 'All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary'
      }, {
        id: '5',
        start: curYear + '-' + curMonth + '-04T10:00:00',
        end: curYear + '-' + curMonth + '-06T15:00:00',
        title: 'Music Festival',
        backgroundColor: '#09ad95',
        borderColor: '#09ad95',
        description: 'All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary'
      }, {
        id: '6',
        start: curYear + '-' + curMonth + '-23T13:00:00',
        end: curYear + '-' + curMonth + '-25T18:30:00',
        title: 'Attend Lea\'s Wedding',
        backgroundColor: '#09ad95',
        borderColor: '#09ad95',
        description: 'All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary'
      }]
    };
    // Birthday Events Source
    var sptBirthdayEvents = {
      id: 2,
      backgroundColor: '#49b6f5',
      borderColor: '#49b6f5',
      textColor: '#fff',
      events: [{
        id: '7',
        start: curYear + '-' + curMonth + '-04',
        end: curYear + '-' + curMonth + '-04',
        title: 'Harcates Birthday',
        description: 'All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary'
      }, {
        id: '8',
        start: curYear + '-' + curMonth + '-28',
        end: curYear + '-' + curMonth + '-28',
        title: 'Bunnysin\'s Birthday',
        description: 'All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary'
      }, {
        id: '9',
        start: curYear + '-' + curMonth + '-31',
        end: curYear + '-' + curMonth + '-31',
        title: 'Lee shin\'s Birthday',
        description: 'All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary'
      }, {
        id: '10',
        start: curYear + '-' + 11 + '-11',
        end: curYear + '-' + 11 + '-11',
        title: 'Shinchan\'s Birthday',
        description: 'All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary'
      },]
    };
    var sptHolidayEvents = {
      id: 3,
      backgroundColor: '#e82646',
      borderColor: '#e82646',
      textColor: '#fff',
      events: [{
        id: '10',
        start: curYear + '-' + curMonth + '-05',
        end: curYear + '-' + curMonth + '-08',
        title: 'Festival Day'
      }, {
        id: '11',
        start: curYear + '-' + curMonth + '-18',
        end: curYear + '-' + curMonth + '-19',
        title: 'Memorial Day'
      }, {
        id: '12',
        start: curYear + '-' + curMonth + '-25',
        end: curYear + '-' + curMonth + '-26',
        title: 'Diwali'
      }]
    };
    var sptOtherEvents = {
      id: 4,
      backgroundColor: '#05c3fb',
      borderColor: '#05c3fb',
      textColor: '#fff',
      events: [{
        id: '13',
        start: curYear + '-' + curMonth + '-07',
        end: curYear + '-' + curMonth + '-09',
        title: 'My Rest Day'
      }, {
        id: '13',
        start: curYear + '-' + curMonth + '-29',
        end: curYear + '-' + curMonth + '-31',
        title: 'My Rest Day'
      }]
    };
  
  
    //________ FullCalendar
    var containerEl = document.getElementById('external-events');
    new FullCalendar.Draggable(containerEl, {
      itemSelector: '.fc-event',
      eventData: function (eventEl) {
        return {
          title: eventEl.innerText.trim(),
          title: eventEl.innerText,
          className: eventEl.className + ' overflow-hidden '
        }
      }
    });
    var calendarEl = document.getElementById('calendar2');
  
    var calendar = new FullCalendar.Calendar(calendarEl, {
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
      },
      defaultView: 'month',
      navLinks: true, // can click day/week names to navigate views
      businessHours: true, // display business hours
      editable: true,
      selectable: true,
      selectMirror: true,
      droppable: true, // this allows things to be dropped onto the calendar
  
      select: function (arg) {
        var title = prompt('Event Title:');
        if (title) {
          calendar.addEvent({
            title: title,
            start: arg.start,
            end: arg.end,
            allDay: arg.allDay
          })
        }
        calendar.unselect()
      },
      eventClick: function (arg) {
        if (confirm('Are you sure you want to delete this event?')) {
          arg.event.remove()
        }
      },
  
      editable: true,
      dayMaxEvents: true, // allow "more" link when too many events
      eventSources: [sptCalendarEvents, sptBirthdayEvents, sptHolidayEvents, sptOtherEvents,],
  
    });
    calendar.render();

    // for activity scroll
    var myElement1 = document.getElementById('full-calendar-activity');
    new SimpleBar(myElement1, { autoHide: true });
  
  
  })();
  