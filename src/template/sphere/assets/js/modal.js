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
        document.querySelectorAll(".modal-effect").forEach(e => {
            e.addEventListener('click', function (e) {
                e.preventDefault();
                let effect = this.getAttribute('data-bs-effect');
                document.querySelector("#modaldemo8").classList.add(effect);
            });
        })
        /* hide modal effects */
        document.getElementById("modaldemo8").addEventListener('hidden.bs.modal', function (e) {
            let removeClass = this.classList.value.match(/(^|\s)effect-\S+/g);
            removeClass = removeClass[0].trim();
            this.classList.remove(removeClass);
        });
    // Animated modals 
    
})();