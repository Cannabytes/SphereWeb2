(function () {
    "use strict"

    document.querySelectorAll(".btn-delete").forEach((element) => {
        let i = document.querySelectorAll(".btn-delete").length

        element.onclick = () => {
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to remove from wishlist",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Remove'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire(
                        'Removed',
                        'Item removed from wishlist.',
                        'success'
                    )
                    element.parentElement.parentElement.parentElement.parentElement.remove()
                    i = document.querySelectorAll(".btn-delete").length
                }
            })
        }
    })

})();