'use strict'
function clickEvent(first,last){
    if(first.value.length){
        document.getElementById(last).focus();
    }
}