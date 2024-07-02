(function () {
    "use strict";

    /* AssignedDate Picker */
    flatpickr("#addignedDate", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
    });

    /* TargetDate Picker */
    flatpickr("#targetDate", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
    });

    /* multi select with remove button */
    const multipleCancelButton = new Choices(
        '#choices-multiple-remove-button',
        {
            allowHTML: true,
            removeItemButton: true,
        }
    );

})();