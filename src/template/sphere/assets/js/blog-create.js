(function () {
    "use strict"

    // for blog content
    var toolbarOptions = [
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        [{ 'font': [] }],
        ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
        ['blockquote', 'code-block'],

        [{ 'header': 1 }, { 'header': 2 }],               // custom button values
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],

        [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
        [{ 'align': [] }],

        ['image', 'video'],
        ['clean']                                         // remove formatting button
    ];
    var quill = new Quill('#blog-content', {
        modules: {
            toolbar: toolbarOptions
        },
        theme: 'snow'
    });

    // for blog images
    const MultipleElement = document.querySelector('.blog-images');
    FilePond.create(MultipleElement,);
    
    // for publish date picker
    flatpickr("#publish-date", {});

    // for publish time
    flatpickr("#publish-time", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
    });

    // for blog tags
    const multipleCancelButton1 = new Choices(
        '#blog-tags',
        {
            allowHTML: true,
            removeItemButton: true,
        }
    );

})();