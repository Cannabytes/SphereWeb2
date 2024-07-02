(function () {
    'use strict'

    let checkAll = document.querySelector('.check-all');
    checkAll.addEventListener('click', checkAllFn)

    function checkAllFn() {
        if (checkAll.checked) {
            document.querySelectorAll('.product-checkbox input').forEach(function (e) {
                e.closest('.product-list').classList.add('selected');
                e.checked = true;
            });
        }
        else {
            document.querySelectorAll('.product-checkbox input').forEach(function (e) {
                e.closest('.product-list').classList.remove('selected');
                e.checked = false;
            });
        }
    }

    //delete Btn
    let productbtn = document.querySelectorAll(".product-btn");

    productbtn.forEach((eleBtn) => {
        eleBtn.onclick = () => {
            let product = eleBtn.closest(".product-list")
            product.remove();
        }
    })

})();