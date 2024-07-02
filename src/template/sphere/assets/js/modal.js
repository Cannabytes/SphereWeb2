(function () {
    "use strict";

    var exampleModal = document.getElementById('formmodal')
    exampleModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget
        var recipient = button.getAttribute('data-bs-whatever')
        var modalTitle = exampleModal.querySelector('.modal-title')
        var modalBodyInput = exampleModal.querySelector('.modal-body input')
        modalTitle.textContent = 'New message to ' + recipient
        modalBodyInput.value = recipient
    })

    // Animated modals 
        /* showing modal effects */

    // Animated modals 
    
})();