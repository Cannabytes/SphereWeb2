
"use strict";
(() => {
  window.addEventListener('scroll', stickyFn);
  var navbar = document.getElementById("sidebar");
  var sticky = navbar.offsetTop;
  function stickyFn() {
    if (window.pageYOffset >= 75) {
      navbar.classList.add("sticky-pin")
    } else {
      navbar.classList.remove("sticky-pin");
    }
  }
})();

window.addEventListener('unload', () => {
  // removing the scroll function
  window.removeEventListener('scroll', stickyFn);
})
