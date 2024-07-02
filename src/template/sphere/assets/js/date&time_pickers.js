(function () {
    "use strict";
    
    /* To choose date */
    flatpickr("#date", {});

    /* To choose date and time */
    flatpickr("#datetime", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
    });

    /* For Human Friendly dates */
    flatpickr("#date_start_server", {
        enableTime: true,
        altInput: true,
        dateFormat: "Y-m-d H:i",
        defaultTime: "13:45",
        time_24hr: true,
    });

    /* For Date Range Picker */
    flatpickr("#daterange", {
        mode: "range",
        dateFormat: "Y-m-d",
    });

    /* For Time Picker */
    flatpickr("#timepikcr", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
    });

    /* For Time Picker With 24hr Format */
    flatpickr("#timepickr1", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true
    });

    /* For Time Picker With Limits */
    flatpickr("#limittime", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        minTime: "16:00",
        maxTime: "22:30",
    });

    /* For DateTimePicker with Limited Time Range */
    flatpickr("#limitdatetime", {
        enableTime: true,
        minTime: "16:00",
        maxTime: "22:00"
    });

    /* For Inline Calendar */
    flatpickr("#inlinecalendar", {
        inline: true
    });

    /* For Date Pickr With Week Numbers */
    flatpickr("#weeknum", {
        weekNumbers: true,
    });

    /* For Inline Time */
    flatpickr("#inlinetime", {
        inline: true,
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
    });

    /* For Preloading Time */
    flatpickr("#pretime", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        defaultDate: "13:45"
    });

})();