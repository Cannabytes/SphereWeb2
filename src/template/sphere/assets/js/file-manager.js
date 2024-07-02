(function() {
    "use strict";

    var myElement1 = document.getElementById('files-main-nav');
    new SimpleBar(myElement1, { autoHide: true });

    var myElement2 = document.getElementById('file-folders-container');
    new SimpleBar(myElement2, { autoHide: true });

    var myElement3 = document.getElementById('filemanager-file-details');
    new SimpleBar(myElement3, { autoHide: true });


    document.querySelectorAll(".files-list > tr > th").forEach(((element)=>{
        element.onclick = ()=>{
            if(window.screen.width <= 1200){
                document.querySelector(".selected-file-details").classList.add("open")
            }
        }
    }))

    document.querySelector("#file-close-btn").onclick = ()=>{
        document.querySelector(".selected-file-details").classList.remove("open")
    }

    document.querySelectorAll(".files-type").forEach((element)=>{
        element.onclick = ()=>{
            if(window.screen.width <= 575){
                document.querySelector(".file-manager-folders").classList.add("open")
                document.querySelector(".file-manager-navigation").classList.add("close")
            }
        }
    })
    document.querySelector("#folders-close-btn").onclick = ()=>{
        document.querySelector(".file-manager-navigation").classList.remove("close")
        document.querySelector(".file-manager-folders").classList.remove("open")
    }

    window.addEventListener("resize",()=>{
        if(window.screen.width > 576){
            document.querySelector(".file-manager-navigation").classList.remove("close")
        }
    })


})();