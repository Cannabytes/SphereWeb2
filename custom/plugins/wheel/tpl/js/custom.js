function debounce(callee, timeoutMs) {
  return function perform(...args) {
    let previousCall = this.lastCall
    this.lastCall = Date.now()
    if (previousCall && this.lastCall - previousCall <= timeoutMs) {
      clearTimeout(this.lastCallTimer)
    }
    this.lastCallTimer = setTimeout(() => callee(...args), timeoutMs)
  }
}

// countdown

function countdownInit() {
  $("[data-countdown]").each(function (indx, element) {
    const __this = $(this);
    let dt = __this.attr('data-countdown');
    let austDay = new Date(dt * 1000);
    let layout = '{dn} {dl} {hnn}{sep}{mnn}{sep}{snn}';
    // __this.countdown('destroy');
    __this.countdown({
      until: austDay,
      layout: layout,
      alwaysExpire: true,
      onExpiry: function () {
        __this.parent().hide();
      },
    });
  });
}

if (true) {
  document.addEventListener("DOMContentLoaded", countdownInit);
}

/* filter */

$(function () {

  $(document).on('input', '[name="filter-type"], [name="filter-price"]', function () {
    let CHESTS = $('[data-chest]');
    const TYPE = $('[name="filter-type"]:checked').val();
    const RANGEPRICE = $('[name="filter-price"]:checked').val().split('-');
    CHESTS.hide();

    if (TYPE != 'all') {
      CHESTS = CHESTS.filter(`[data-chest-type="${TYPE}"]`);
    }

    if (RANGEPRICE[0] != 'all') {
      CHESTS = CHESTS.filter(function () {
        return (
          parseInt($(this).attr('data-chest-price')) >= parseInt(RANGEPRICE[0])
          &&
          parseInt($(this).attr('data-chest-price')) <= parseInt(RANGEPRICE[1])
        );
      });
    }

    CHESTS.show();

  });
});



function getRandomInt(min, max) {
  return Math.floor(Math.random() * (max - min)) + min;
}

function makeSound(soundURL) {
  var audio = new Audio(soundURL);
  audio.play();
}



window.raf = (function () {
  return (
    window.requestAnimationFrame ||
    window.webkitRequestAnimationFrame ||
    window.mozRequestAnimationFrame ||
    window.oRequestAnimationFrame ||
    window.msRequestAnimationFrame ||
    function ( /* function */ callback, /* DOMElement */ element) {
      window.setTimeout(callback, 1000 / 60);
    }
  );
})();

$(document).on('click', '[data-win-modal]', function () {
  const __this = $(this);
  __this.remove();
});

function ItemWinModal(item, heading, mod) {
  let enchant = item.enchant > 0 ? '+' + item.enchant : '';
  let count = item.count > 1 ? '<span class="color-accent-2">x' + item.count + '</span>' : '';
  let add_name = item.add_name ? '<span class="color-brown">(' + item.add_name + ')</span>' : '';

  Swal.fire({
    title: 'Поздравляем',
    html: `Вы выиграли <span class="text-success color-accent-2">${enchant} ${add_name} ${item.name} ${count}</span><br><br>Выигрыш отправлен Вам на склад`,
    imageUrl: item.icon,
    imageWidth: 64,
    imageHeight: 64,
  });

  return false;
}

