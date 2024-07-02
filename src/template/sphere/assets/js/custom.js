$(document).ready(function () {
  "use strict";

  // Page loader
  function hideLoader() {
    $("#loader").addClass("d-none");
  }

  $(window).on("load", hideLoader);

  // Cover img
  $(".cover-image").each(function () {
    var attr = $(this).attr("data-bs-image-src");
    if (attr) {
      $(this).css("background", `url(${attr}) center center`);
    }
  });

  // Tooltip
  var tooltipTriggerList = $('[data-bs-toggle="tooltip"]');
  tooltipTriggerList.each(function () {
    new bootstrap.Tooltip(this);
  });

  // Popover
  var popoverTriggerList = $('[data-bs-toggle="popover"]');
  popoverTriggerList.each(function () {
    new bootstrap.Popover(this);
  });

  // Switcher color pickers
  var nanoThemes = [
    [
      "nano",
      {
        defaultRepresentation: "RGB",
        components: {
          preview: true,
          opacity: false,
          hue: true,
          interaction: {
            hex: false,
            rgba: true,
            hsva: false,
            input: true,
            clear: false,
            save: false,
          },
        },
      },
    ],
  ];

  var nanoButtons = [];
  var nanoPickr = null;

  nanoThemes.forEach(([theme, config]) => {
    var button = $("<button>").html(theme);
    nanoButtons.push(button);

    button.on("click", function () {
      var el = $("<p>");
      $(".pickr-container-primary").append(el);

      if (nanoPickr) {
        nanoPickr.destroyAndRemove();
      }

      nanoButtons.forEach((btn) => btn.removeClass("active"));
      button.addClass("active");

      nanoPickr = new Pickr(
        Object.assign(
          {
            el: el[0],
            theme: theme,
            default: "#6c5ffc",
          },
          config
        )
      );

      nanoPickr.on("changestop", function (source, instance) {
        let color = instance.getColor().toRGBA();
        $("html").css("--primary-rgb", `${Math.floor(color[0])}, ${Math.floor(color[1])}, ${Math.floor(color[2])}`);
        localStorage.setItem("primaryRGB", `${Math.floor(color[0])}, ${Math.floor(color[1])}, ${Math.floor(color[2])}`);
        updateColors();
      });
    });

    $(".theme-container-primary").append(button);
  });

  nanoButtons[0].click();

  var nanoButtons1 = [];
  var nanoPickr1 = null;

  nanoThemes.forEach(([theme, config]) => {
    var button = $("<button>").html(theme);
    nanoButtons1.push(button);

    button.on("click", function () {
      var el = $("<p>");
      $(".pickr-container-background").append(el);

      if (nanoPickr1) {
        nanoPickr1.destroyAndRemove();
      }

      nanoButtons1.forEach((btn) => btn.removeClass("active"));
      button.addClass("active");

      nanoPickr1 = new Pickr(
        Object.assign(
          {
            el: el[0],
            theme: theme,
            default: "#b4b4b4",
          },
          config
        )
      );

      nanoPickr1.on("changestop", function (source, instance) {
        let color = instance.getColor().toRGBA();
        $("html").css("--body-bg-rgb", `${color[0]}, ${color[1]}, ${color[2]}`);
        $("html").css("--body-bg-rgb2", `${color[0] + 14}, ${color[1] + 14}, ${color[2] + 14}`);
        $("html").css("--light-rgb", `${color[0]}, ${color[1]}, ${color[2]}`);
        $("html").css("--form-control-bg", `rgb(${color[0] + 14}, ${color[1] + 14}, ${color[2] + 14})`);
        localStorage.removeItem("bgtheme");
        updateColors();
        $("html").attr("data-theme-mode", "dark");
        $("html").attr("data-menu-styles", "dark");
        $("html").attr("data-header-styles", "dark");
        $("#switcher-dark-theme").prop("checked", true);
        localStorage.setItem("bodyBgRGB", `${color[0]}, ${color[1]}, ${color[2]}`);
        localStorage.setItem("bodylightRGB", `${color[0] + 14}, ${color[1] + 14}, ${color[2] + 14}`);
      });
    });

    $(".theme-container-background").append(button);
  });

  nanoButtons1[0].click();

  // Header theme toggle
  function toggleTheme() {
    let html = $("html");
    if (html.attr("data-theme-mode") === "dark") {
      html.attr("data-theme-mode", "light");
      html.attr("data-header-styles", "light");
      html.attr("data-menu-styles", "light");
      if (!localStorage.getItem("primaryRGB")) {
        html.removeAttr("style");
      }
      html.removeAttr("data-bg-theme");
      $("#switcher-light-theme").prop("checked", true);
      $("#switcher-menu-light").prop("checked", true);
      $("#switcher-header-light").prop("checked", true);
      html.css("--body-bg-rgb", localStorage.bodyBgRGB);
      html.css("--body-bg-rgb2", localStorage.bodyBgRGB);
      html.css("--light-rgb", localStorage.bodyBgRGB);
      html.css("--form-control-bg", localStorage.bodyBgRGB);
      html.css("--input-border", localStorage.bodyBgRGB);
      checkOptions();
      localStorage.removeItem("sashdarktheme");
      localStorage.removeItem("sashMenu");
      localStorage.removeItem("sashHeader");
      localStorage.removeItem("bodylightRGB");
      localStorage.removeItem("bodyBgRGB");
    } else {
      html.attr("data-theme-mode", "dark");
      html.attr("data-header-styles", "dark");
      html.attr("data-menu-styles", "dark");
      if (!localStorage.getItem("primaryRGB")) {
        html.removeAttr("style");
      }
      $("#switcher-dark-theme").prop("checked", true);
      $("#switcher-menu-dark").prop("checked", true);
      $("#switcher-header-dark").prop("checked", true);
      html.css("--body-bg-rgb", localStorage.bodyBgRGB);
      html.css("--body-bg-rgb2", localStorage.bodyBgRGB);
      html.css("--light-rgb", localStorage.bodyBgRGB);
      html.css("--form-control-bg", localStorage.bodyBgRGB);
      html.css("--input-border", localStorage.bodyBgRGB);
      checkOptions();
      localStorage.setItem("sashdarktheme", "true");
      localStorage.setItem("sashMenu", "dark");
      localStorage.setItem("sashHeader", "dark");
      localStorage.removeItem("bodylightRGB");
      localStorage.removeItem("bodyBgRGB");
    }
  }

  // Choices JS
  $("[data-trigger]").each(function () {
    new Choices(this, {
      allowHTML: true,
      placeholderValue: "This is a placeholder set in the config",
      searchPlaceholderValue: "Search",
    });
  });

  // Node waves
  Waves.attach(".btn-wave", ["waves-light"]);
  Waves.init();

  // Card with close button
  $('[data-bs-toggle="card-remove"]').on("click", function (e) {
    e.preventDefault();
    $(this).closest(".card").remove();
    return false;
  });

  // Card with fullscreen
  $('[data-bs-toggle="card-fullscreen"]').on("click", function (e) {
    e.preventDefault();
    $(this).closest(".card").toggleClass("card-fullscreen").removeClass("card-collapsed");
    return false;
  });

  // Count-up
  var i = 1;
  setInterval(() => {
    $(".count-up").each(function () {
      if ($(this).attr("data-count") >= i) {
        i = i + 1;
        $(this).text(i);
      }
    });
  }, 10);

  // Back to top
  var scrollToTop = $(".scrollToTop");
  var $rootElement = document.documentElement;

  $(window).on("scroll", function () {
    if ($(this).scrollTop() > 100) {
      scrollToTop.css("display", "flex");
    } else {
      scrollToTop.css("display", "none");
    }
  });

  scrollToTop.on("click", function () {
    $("html, body").animate({ scrollTop: 0 }, "slow");
  });

  // Header dropdown close button for cart dropdown
  $(".dropdown-item-close").on("click", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).closest(".cart-item").remove();
    $("#cart-icon-badge").text($(".dropdown-item-close").length);
    if ($(".dropdown-item-close").length === 0) {
      $(".empty-header-item").addClass("d-none");
      $(".empty-item").removeClass("d-none");
    }
  });

  // Header dropdown close button for notifications dropdown
  $(".dropdown-item-close1").on("click", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).closest(".notification-item").remove();
    $("#notifiation-data").text(`${$(".dropdown-item-close1").length} Unread`);


    if ($(".dropdown-item-close1").length === 0) {
      $(".empty-header-item1").addClass("d-none");
      $(".empty-item1").removeClass("d-none");
    }
  });

  // Header dropdown close button for messages dropdown
  $(".dropdown-item-close2").on("click", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).closest(".message-item").remove();
    $("#message-data").text(`${$(".dropdown-item-close2").length} Unread`);
    if ($(".dropdown-item-close2").length === 0) {
      $(".empty-header-item2").addClass("d-none");
      $(".empty-item2").removeClass("d-none");
    }
  });
});

function showSearchResult(event) {
  event.preventDefault();
  event.stopPropagation();
  $("#headersearch").addClass("searchdrop");
}

// Full screen
var elem = document.documentElement;
function openFullscreen() {
  let open = $(".full-screen-open");
  let close = $(".full-screen-close");

  if (
    !document.fullscreenElement &&
    !document.webkitFullscreenElement &&
    !document.msFullscreenElement
  ) {
    if (elem.requestFullscreen) {
      elem.requestFullscreen();
    } else if (elem.webkitRequestFullscreen) {
      /* Safari */
      elem.webkitRequestFullscreen();
    } else if (elem.msRequestFullscreen) {
      /* IE11 */
      elem.msRequestFullscreen();
    }
    close.addClass("d-block").removeClass("d-none");
    open.addClass("d-none");
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    } else if (document.webkitExitFullscreen) {
      /* Safari */
      document.webkitExitFullscreen();
    } else if (document.msExitFullscreen) {
      /* IE11 */
      document.msExitFullscreen();
    }
    close.removeClass("d-block").addClass("d-none");
    open.removeClass("d-none").addClass("d-block");
  }
}

// Toggle switches
$(".toggle").on("click", function () {
  $(this).toggleClass("on");
});
