(function () {
    "use strict"

    // for color selection
    const multipleCancelButton = new Choices(
        '#product-color-add',
        {
            allowHTML: true,
            removeItemButton: true,
        }
    );

    // for product tags
    const multipleCancelButton1 = new Choices(
        '#product-tags',
        {
            allowHTML: true,
            removeItemButton: true,
        }
    );

    // for product features
    var toolbarOptions = [
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        [{ 'font': [] }],
        ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
        ['blockquote', 'code-block'],

        [{ 'header': 1 }, { 'header': 2 }],               // custom button values
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],

        [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
        [{ 'align': [] }],
        ['clean']                                         // remove formatting button
    ];
    var quill = new Quill('#product-features', {
        modules: {
            toolbar: toolbarOptions
        },
        theme: 'snow'
    });

    // for publish date picker
    flatpickr("#publish-date", {
        defaultDate: ["2023-04-17"]
    });

    // for publish time
    flatpickr("#publish-time", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        defaultDate: "13:45"
    });

    // for product images upload
    const MultipleElement1 = document.querySelector('.product-Images');
    FilePond.create(MultipleElement1,);

    // for documents upload
    
     // for image uploads
     FilePond.registerPlugin(
        FilePondPluginImagePreview,
        FilePondPluginImageExifOrientation,
        FilePondPluginFileValidateSize,
        FilePondPluginFileEncode,
        FilePondPluginImageEdit,
        FilePondPluginFileValidateType,
        FilePondPluginImageCrop,
        FilePondPluginImageResize,
        FilePondPluginImageTransform
    );
    const MultipleElement = document.querySelector('.product-documents');
    FilePond.create(MultipleElement,);


})();