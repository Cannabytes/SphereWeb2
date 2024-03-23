(function() {
    'use strict'

    document.querySelectorAll(".prism-toggle").forEach((element)=>{
        element.addEventListener("click",()=>{
            console.log(element.firstElementChild.childNodes);
            let carElement = element.parentNode.parentNode
            if(!carElement.children[1].classList.contains("d-none")){
                element.firstElementChild.childNodes[1].className = "ri-code-s-slash-line ms-2 align-middle d-inline-block"
                carElement.children[1].classList.add("d-none")
                carElement.children[2].classList.remove("d-none")
            }else{
                element.firstElementChild.childNodes[1].className = "ri-code-line ms-2 align-middle d-inline-block"
                carElement.children[1].classList.remove("d-none")
                carElement.children[2].classList.add("d-none")
            }
        })
    })

})();