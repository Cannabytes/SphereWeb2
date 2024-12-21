


if (__config.wheelInit) {
    document.addEventListener("DOMContentLoaded", wheelInit);
}

function wheelInit() {
    const cfg = __config.wheel;
    const wheel = $('[data-wheel]');
    const wrap = $('[data-wheel-wrap]');
    const circle = $('[data-wheel-circle]');
    const box = $('[data-wheel-box]');
    const btn = $('[data-wheel-start]');

    let activeApp = false;

    if (box.length == 0) return;
    if (circle.length == 0) return;

    box.append(wheelItemsBuilder(cfg.items));


    btn.on('click', function (e) {

        if (activeApp) return;
        activeApp = true;

        /* Деактивируем кнопку */
        btn.attr('data-wheel-start', 'false');
        /* Звук клика */
        wheelSoundCheck(
            function () {
                makeSound(cfg.urlSounds + 'click.mp3')
            }
        );
        AjaxSend('/fun/wheel/callback', 'POST', {
            id: cfg.id,
        }, true, 5).then(function (data) {
            let cost;
            if (data.sphereCoin) {
                cost = data.sphereCoin - $(".count_sphere_coin").text();
                animateCounter(cost)
            }

            if (data.success !== true) {
                console.log('Что-то пошло не так');
                /* Разрешаем запустить приложение вновь */
                activeApp = false;
                /* Активируем кнопку старт */
                btn.attr('data-wheel-start', 'true');
                return;
            }


            data = data.wheel;
            //  добавляем полученый номер выигранного итема в объект
            if (data.num && data.num != '' && Number.isInteger(data.num)) {
                cfg.win.num = data.num - 1;
                cfg.win.item = data;
                wrap.css('transform', 'rotate(' + (-18 * data.num - 7 + getRandomInt(1, 14)) + 'deg)');
            }

            setTimeout(() => {
                /* Запускаем анимацию */
                circle.attr('data-wheel-circle', 'animated');

                /* Запускаем звук анимации */
                wheelSoundCheck(
                  function () {
                      makeSound(cfg.urlSounds + 'ticking.mp3')
                  }
                );

            }, 1000);
        })

    });

    /* Вешаем событие на окончание анимации  */
    circle.on("animationend webkitAnimationEnd MSAnimationEnd oAnimationEnd", function () {


        /* Запускаем звук победы */
        wheelSoundCheck(
            function () {
                makeSound(cfg.urlSounds + 'win.mp3')
            }
        );

        /* Убираем анимацию */
        circle.attr('data-wheel-circle', '');

        /* Разрешаем запустить приложение вновь */
        activeApp = false;

        /* Активируем кнопку старт */
        btn.attr('data-wheel-start', 'true');

        ItemWinModal(cfg.win.item, cfg.win.heading, 'win_fixed')
        console.log(cfg.win.item)

        addToHistory(cfg.win.item, '[data-wheel-history-list]');
    });

    wheelSoundHandler();

}

function wheelItemsBuilder(items) {
    let html = '';

    let crystal_type;
    for (let i = 0; i < items.length; i++) {
        let crystal_type = '';

        if (items[i].crystal_type && items[i].crystal_type !== 'none') {
            console.log(items[i].crystal_type);
            crystal_type = '[' + items[i].crystal_type.toUpperCase() + ']';
        }


        let enchant = items[i].enchant > 0 ? '+' + items[i].enchant : '';
        let description = items[i].description ? '<br>' + items[i].description : '';

        let count = items[i].count > 1 ? 'x' + items[i].count : '';

        if (items[i].count_type===2){
           count = '[ ' + items[i].count_min + ' - ' + items[i].count_max + ' ]';
        }

        html += `<div class="wheel__el">
                    <img
                        src="${items[i].icon}"
                        alt="ico-${i}"
                        class="wheel__item" 
                        data-tlt="<b class='color-accent-2'>${enchant} ${crystal_type} ${items[i].name}</b> ${count}<br>${description}"
                    />
                </div>`;
    }

    return html;
}

function wheelSoundHandler() {
    /* sound */
    let wheelSound = $('[data-wheel-sound]');
    if (localStorage.getItem('wheelSound') == 'false') {
        wheelSound.attr('data-wheel-sound', 'false');
    }
    wheelSound.on('click', function () {
        const __this = $(this);

        //  localStorage.getItem('wheelSound') == null || localStorage.getItem('wheelSound') == 'false'
        if (localStorage.getItem('wheelSound') == 'false') {
            localStorage.setItem('wheelSound', 'true');
            __this.attr('data-wheel-sound', 'true');
        } else {
            localStorage.setItem('wheelSound', 'false');
            __this.attr('data-wheel-sound', 'false');
        }
    });
}


function wheelSoundCheck(fn) {
    if (localStorage.getItem('wheelSound') == null || localStorage.getItem('wheelSound') == 'true') {
        fn();

    }
}

function addToHistory(item, parent) {
    let enchant = item.enchant > 0 ? '+' + item.enchant : '';
    let crystal_type = item.crystal_type ? '[' + item.crystal_type.toUpperCase() + ']' : '';
    $(parent).prepend(`
          <li class="timeline-widget-list mb-3">
            <div class="d-flex align-items-top">
              <div class="me-4 text-center">
                <img class="avatar avatar-md me-0" src="${item.icon}">
              </div>
              <div class="d-flex flex-wrap flex-fill align-items-center justify-content-between">
                <div>
                  <p class="mb-1 text-truncate timeline-widget-content text-wrap">Вы выиграли <span class="text-danger fs-15 badge bg-success-transparent ">${enchant} ${crystal_type} ${item.name}</span> x${item.count}</p>
                  <p class="mb-0 fs-12 lh-1 text-muted">Только что</p>
                </div>
              </div>
            </div>
          </li>
    `);
}


var tippyTlt = function tippy_tlt() {
    tippy("[data-tlt]", {
        delay: 0,
        offset: [20, 50],
        flip: true,
        arrow: false,
        followCursor: true,
        placement: "right-start",
        theme: "tlt",
        allowHTML: true,
        maxWidth: "350px",
        content(reference) {
            return $(reference).find("[data-tlt-content]").html() || $(reference).attr("data-tlt");
        },
    });
};
document.addEventListener("DOMContentLoaded", tippyTlt);
