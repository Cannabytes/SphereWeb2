(function () {
    'use strict';

    let loadFile = function (event) {
        var reader = new FileReader();
        reader.onload = function () {
            var output = document.getElementById('profile-img');
            if (event.target.files[0].type.match('image.*')) {
                output.src = reader.result;
            } else {
                event.target.value = '';
                alert('please select a valid image');
            }
        };
        reader.readAsDataURL(event.target.files[0]);
    };

    // for personal information language
    const multipleCancelButton = new Choices(
        '#language',
        {
            allowHTML: true,
            removeItemButton: true,
        }
    );

    // for mail language
    const multipleCancelButton1 = new Choices(
        '#mail-language',
        {
            allowHTML: true,
            removeItemButton: true,
        }
    );

    // for profile photo update
    let ProfileChange = document.querySelector('#profile-change');
    ProfileChange.addEventListener('change', loadFile);

})();