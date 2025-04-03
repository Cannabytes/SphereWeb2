// Функция открытия модального окна с кейсом
function openChestModal(chestId, chestName, chestPrice, chestIcon) {
    // Сбрасываем состояние модального окна
    resetModalState();

    // Устанавливаем данные кейса
    const imagePath = '/src/component/plugins/chests/tpl/images/chest/chest-' + chestIcon + '.webp';
    $('#modal-chest-image').attr('src', imagePath);
    $('#modal-chest-price').text(chestPrice + ' монет');
    $('#open-chest-button').data('chest-id', chestId);
    $('#modal-chest-name').text(chestName);

    // Загружаем список предметов в кейсе
    loadChestItems(chestId);

    // Принудительно открываем модальное окно Bootstrap
    var chestModal = new bootstrap.Modal(document.getElementById('chestModal'));
    chestModal.show();

    // Добавляем эффект появления модального окна
    setTimeout(function() {
        $('.chest-preview-container').addClass('animated');
    }, 300);
}

// Функция загрузки предметов из кейса
function loadChestItems(chestId) {
    // Очищаем список перед загрузкой новых предметов
    $('#chest-items-list').empty();

    // Получаем данные кейса
    const chest = getChestById(chestId);

    if (chest && chest.items) {
        // Добавляем предметы в список
        chest.items.forEach(function(item) {
            const itemHtml = createItemCard(item);
            $('#chest-items-list').append(itemHtml);
        });
    }
}

// Функция создания карточки предмета
function createItemCard(item) {
    // Формируем HTML для карточки предмета
    const enchantBadge = item.enchant > 0 ?
        `<div class="item-enchant">+${item.enchant}</div>` : '';

    return `
            <div class="item-card" data-item-id="${item.id}">
                <div class="item-image-container">
                    <img src="${item.icon}" alt="${item.name}" class="item-image">
                    ${enchantBadge}
                </div>
                <div class="item-details-n">
                    <p class="item-name">${item.name}</p>
                    <span class="item-count">x${item.count}</span>
                </div>
            </div>
        `;
}

// Функция получения кейса по ID
function getChestById(chestId) {
    return window.chestData[chestId] || null;
}

// Обработка клика по кейсу
$('.chest-item').on('click', function() {
    console.log('Клик по кейсу');

    const chestId = $(this).data('chest-id');
    const chestName = $(this).data('chest-name');
    const chestPrice = $(this).data('chest-price');
    const chestIcon = $(this).data('chest-icon');

    openChestModal(chestId, chestName, chestPrice, chestIcon);
});

$(document).ready(function() {
    // Клик по кнопке "Открыть кейс"
    $(document).on('click', '#open-chest-button', function() {
        const chestId = $(this).data('chest-id');
        console.log('Нажата кнопка открытия кейса', chestId);
        startOpeningAnimation(chestId);
    });

    // Клик по кнопке "Открыть ещё"
    $(document).on('click', '#open-again-btn, .btn-open-again', function() {
        console.log('Нажата кнопка "Открыть ещё"');
        resetModalState();
    });
});

// Сброс модального окна при закрытии
$('#chestModal').on('hidden.bs.modal', function() {
    resetModalState();
});

function resetModalState() {
    console.log('Сбрасываем состояние модального окна');

    // Сначала скрываем все вспомогательные контейнеры
    $('.opening-animation-container')
        .addClass('d-none')
        .css('opacity', '0')
        .hide();

    $('.winning-container')
        .removeClass('show')
        .addClass('d-none')
        .css('opacity', '0')
        .hide();

    $('.roulette-container')
        .addClass('d-none')
        .hide();

    // Удаляем контент рулетки
    $('#roulette-items').empty();

    // Останавливаем все анимации
    $('.roulette-track').stop(true, true);

    // Возвращаем кнопку в исходное состояние
    $('#open-chest-button').prop('disabled', false);

    // Затем показываем основные контейнеры
    $('.chest-preview-container').fadeIn(300);
    $('.chest-items-container').fadeIn(300);
}

// Создаем ленту рулетки из предметов
function createRouletteStrip(chestId, winningItemId) {
    const chest = getChestById(chestId);
    if (!chest || !chest.items) return;

    // Очищаем контейнер рулетки
    $('#roulette-items').empty();

    // Количество повторений предметов для более длинной ленты
    const repetitions = 10;

    // Массив для хранения индексов выигрышного предмета
    const winningIndices = [];

    console.log("Создаем ленту рулетки для chestId:", chestId);
    console.log("Ищем выигрышный предмет с ID:", winningItemId);

    // Создаем длинную ленту предметов
    for (let r = 0; r < repetitions; r++) {
        chest.items.forEach((item, index) => {
            const itemHtml = `
                <div class="roulette-item" data-item-id="${item.id}">
                    <div class="roulette-image-container">
                        <img src="${item.icon}" alt="${item.name}" class="roulette-image">
                        ${item.enchant > 0 ? `<div class="roulette-enchant">+${item.enchant}</div>` : ''}
                    </div>
                    <div class="roulette-item-name">${item.name}</div>
                    <div class="roulette-item-count">x${item.count}</div>
                </div>
            `;

            $('#roulette-items').append(itemHtml);

            // Если это выигрышный предмет, сохраняем его индекс
            if (parseInt(item.id) === parseInt(winningItemId)) {
                const currentIndex = r * chest.items.length + index;
                winningIndices.push(currentIndex);
                console.log("Найден выигрышный предмет! Индекс:", currentIndex);
            }
        });
    }

    console.log("Всего создано элементов:", repetitions * chest.items.length);
    console.log("Найдено выигрышных индексов:", winningIndices);

    return {
        totalItems: chest.items.length * repetitions,
        winningIndices: winningIndices
    };
}

// Функция запуска анимации открытия кейса
function startOpeningAnimation(chestId) {
    // Плавно скрываем предпросмотр и список предметов
    $('.chest-preview-container').fadeOut(300);
    $('.chest-items-container').fadeOut(300);

    // Отключаем кнопку открытия
    $('#open-chest-button').prop('disabled', true);

    // После того как элементы скрыты, показываем анимацию
    setTimeout(function() {
        // Сначала убеждаемся, что выигрыш скрыт
        $('.winning-container').removeClass('show').addClass('d-none');

        // Показываем контейнер анимации открытия
        $('.opening-animation-container')
            .removeClass('d-none')
            .css('opacity', '1')
            .show();

        // Устанавливаем изображение сундука
        const chest = getChestById(chestId);
        if (!chest) {
            console.error('Кейс не найден', chestId);
            resetModalState();
            return;
        }

        const chestImagePath = '/src/component/plugins/chests/tpl/images/chest/chest-' + chest.icon + '.webp';
        $('#chest-animation-img').attr('src', chestImagePath);

        console.log('Показываем анимацию открытия кейса');

        // Играем звук открытия кейса (если есть)
        try {
            playSound('chest_open');
        } catch (e) {
            console.log('Звук не загружен:', e);
        }

        // Добавляем анимацию открытия сундука
        animateChestOpening();

        // После анимации открытия сундука (через 2 секунды) запускаем рулетку
        setTimeout(function() {
            // Скрываем анимацию открытия
            $('.opening-animation-container').fadeOut(300);

            // Отправляем AJAX запрос для получения выигрышного предмета
            AjaxSend("/fun/chests/callback", "POST", {chest_id: chestId}, true)
                .then(function (response) {
                    responseAnalysis(response);
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch (e) {
                            console.error('Ошибка парсинга JSON:', e);
                            resetModalState();
                            return;
                        }
                    }

                    if (!response || (!response.success && !response.ok) || !response.item) {
                        console.error('Некорректные данные ответа:', response);
                        resetModalState();
                        return;
                    }

                    // Получаем данные о выигранном предмете
                    const winningItem = {
                        id: response.item.id,
                        count: response.item.count,
                        enchant: response.item.enchant || 0,
                        name: response.item.itemInfo.itemName,
                        add_name: response.item.itemInfo.addName || '',
                        icon: response.item.itemInfo.icon,
                        crystal_type: response.item.itemInfo.crystal_type || ''
                    };

                    // Запускаем анимацию рулетки, когда получили выигранный предмет
                    startRouletteAnimation(chestId, winningItem);
                });
        }, 2000);
    }, 350);
}

// Анимация открытия сундука
function animateChestOpening() {
    const chestImg = $('#chest-animation-img');

    // Добавляем класс для анимации
    chestImg.addClass('chest-opening');

    // Добавляем эффекты частиц
    createParticles();
}

// Создание эффекта частиц при открытии сундука
function createParticles() {
    const particleContainer = $('.opening-animation-container');

    // Создаем контейнер для частиц если его нет
    if (!$('.particles-container').length) {
        particleContainer.append('<div class="particles-container"></div>');
    }

    // Создаем частицы
    for (let i = 0; i < 50; i++) {
        const particle = $('<div class="particle"></div>');

        // Случайные параметры для частиц
        const size = Math.random() * 10 + 5;
        const posX = 50 + (Math.random() - 0.5) * 60; // относительно центра
        const posY = 50 + (Math.random() - 0.5) * 60; // относительно центра
        const speed = Math.random() * 2 + 1;
        const delay = Math.random() * 0.5;
        const opacity = Math.random() * 0.5 + 0.5;

        // Применяем стили
        particle.css({
            width: size + 'px',
            height: size + 'px',
            left: posX + '%',
            top: posY + '%',
            opacity: opacity,
            animation: `particle-fly ${speed}s ease-out ${delay}s`
        });

        // Добавляем частицу
        $('.particles-container').append(particle);
    }

    // Удаляем частицы через некоторое время
    setTimeout(() => {
        $('.particles-container').remove();
    }, 3000);
}

// Функция запуска анимации рулетки
function startRouletteAnimation(chestId, winningItem) {
    // Подготавливаем контейнер рулетки
    if (!$('.roulette-container').length) {
        const rouletteHTML = `
            <div class="roulette-container">
                <div class="roulette-viewport">
                    <div class="roulette-track" id="roulette-items"></div>
                </div>
                <div class="roulette-center-highlight"></div>
                <div class="roulette-shine-effect"></div>
            </div>
        `;

        // Добавляем HTML рулетки после контейнера анимации
        $('.opening-animation-container').after(rouletteHTML);
    }

    // Показываем контейнер рулетки
    $('.roulette-container')
        .removeClass('d-none')
        .css('opacity', '0')
        .show()
        .animate({opacity: 1}, 300);

    // Создаем ленту рулетки с предметами
    const rouletteData = createRouletteStrip(chestId, winningItem.id);

    if (!rouletteData || !rouletteData.winningIndices.length) {
        console.error('Ошибка создания рулетки', chestId, winningItem.id);
        resetModalState();
        return;
    }

    // Выбираем случайный индекс из возможных выигрышных позиций
    const winIndex = rouletteData.winningIndices[Math.floor(Math.random() * rouletteData.winningIndices.length)];

    // Переменная для хранения аудио элемента тикающего звука
    let tickSound = null;

    // Получаем ширину одного элемента
    const itemWidth = $('.roulette-item').first().outerWidth(true);

    // Рассчитываем, сколько нужно прокрутить, чтобы выигрышный предмет оказался по центру
    const scrollPosition = (winIndex * itemWidth) - ($('.roulette-viewport').width() / 2) + (itemWidth / 2);

    // Настраиваем начальное положение
    $('#roulette-items').css('left', '0');

    // Параметры анимации
    const initialDuration = 500; // Начальное ускорение (мс)
    const spinDuration = 2500;  // Вращение на полной скорости (мс)
    const slowdownDuration = 2000; // Замедление (мс)

    // Рассчитываем промежуточную позицию для ускорения
    const acceleratePosition = Math.min(scrollPosition * 0.3, 1000);

    // Функция добавления "дрожания" рулетки на последнем этапе
    function addShakeEffect(progress) {
        if (progress < 0.8) return 0;

        // Амплитуда дрожания уменьшается к концу
        const amplitude = (1 - progress) * 20;
        return (Math.random() - 0.5) * amplitude;
    }

    console.log("Запускаем анимацию рулетки");
    console.log("scrollPosition:", scrollPosition);
    console.log("acceleratePosition:", acceleratePosition);

    // Анимация ускорения
    $('#roulette-items').animate({
        left: -acceleratePosition
    }, {
        duration: initialDuration,
        easing: jQuery.easing.easeInQuad ? 'easeInQuad' : 'swing',
        complete: function() {
            console.log("Завершена первая фаза анимации");

            // Запускаем звук тиканья, зацикленный
            try {
                tickSound = playSound('tick', true);
            } catch (e) {
                console.log('Не удалось запустить звук тиканья:', e);
            }

            // Анимация основного вращения
            $('#roulette-items').animate({
                left: -(scrollPosition * 0.7)
            }, {
                duration: spinDuration,
                easing: 'linear',
                complete: function() {
                    console.log("Завершена вторая фаза анимации");

                    // Добавляем световые эффекты во время замедления
                    $('.roulette-center-highlight').addClass('active');

                    // Игнорируем первые несколько "щелчков"
                    let ticks = 0;

                    // Анимация замедления с "защелкиванием"
                    $('#roulette-items').animate({
                        left: -scrollPosition
                    }, {
                        duration: slowdownDuration,
                        easing: jQuery.easing.easeOutQuart ? 'easeOutQuart' : 'swing',
                        step: function(now, fx) {
                            // Добавляем эффект "защелкивания" на каждом предмете
                            if (fx.pos > 0.5) { // Начинаем эффект на второй половине анимации
                                const itemPosition = Math.round(Math.abs(now) / itemWidth);

                                // Если перешли к новому предмету
                                if (itemPosition !== ticks) {
                                    ticks = itemPosition;

                                    // Добавляем визуальный эффект
                                    $('.roulette-center-highlight').addClass('tick');
                                    setTimeout(() => {
                                        $('.roulette-center-highlight').removeClass('tick');
                                    }, 100);
                                }
                            }

                            // Добавляем дрожание к концу анимации
                            if (fx.pos > 0.8) {
                                const shake = addShakeEffect(fx.pos);
                                $(fx.elem).css({
                                    'left': now,
                                    'top': shake
                                });
                            }

                            // Замедляем звук тиканья постепенно на последних 20% анимации
                            if (tickSound && fx.pos > 0.8) {
                                // Постепенно уменьшаем громкость звука
                                const volume = 0.5 * (1 - (fx.pos - 0.8) * 5); // 0.5 до 0
                                tickSound.volume = Math.max(0, volume);
                            }
                        },
                        complete: function() {
                            console.log("Анимация завершена");

                            // Останавливаем звук тиканья
                            if (tickSound) {
                                tickSound.pause();
                                tickSound.currentTime = 0;
                            }

                            // Выделяем выигрышный предмет
                            highlightWinningItem();

                            // Проигрываем звук выигрыша
                            try {
                                playSound('win');
                            } catch (e) {
                                console.log('Звук не загружен:', e);
                            }

                            // Показываем выигрыш через некоторое время
                            setTimeout(function() {
                                $('.roulette-container').fadeOut(500, function() {
                                    showWinningItem(winningItem);
                                });
                            }, 1500);
                        }
                    });
                }
            });
        }
    });
}

// Функция выделения выигрышного предмета
function highlightWinningItem() {
    console.log("Выделяем выигрышный предмет");

    // Находим элемент в центре viewport
    const viewportCenter = $('.roulette-viewport').width() / 2;
    const viewportOffset = $('.roulette-viewport').offset().left;
    let closestItem = null;
    let minDistance = Infinity;

    // Перебираем все элементы и находим ближайший к центру
    $('.roulette-item').each(function() {
        const itemLeft = $(this).offset().left;
        const itemCenter = itemLeft + $(this).width() / 2;
        const distance = Math.abs(itemCenter - (viewportOffset + viewportCenter));

        if (distance < minDistance) {
            minDistance = distance;
            closestItem = $(this);
        }
    });

    console.log("Минимальное расстояние:", minDistance);
    console.log("Ближайший элемент:", closestItem ? closestItem.find('.roulette-item-name').text() : "не найден");

    if (closestItem) {
        // Добавляем класс для выигрышного предмета
        closestItem.addClass('winning-item-highlight');

        // Добавляем анимацию пульсации
        closestItem.css('animation', 'winner-pulse 0.6s infinite alternate');
    } else {
        console.error("Не удалось найти ближайший элемент для выделения");
    }
}

// Функция для воспроизведения звуков
function playSound(soundName, loop = false) {
    // Проверяем, есть ли предзагруженный аудио элемент
    const audioElement = document.getElementById('sound-' + soundName);

    if (audioElement) {
        // Сбрасываем звук на начало, если он уже проигрывался
        audioElement.currentTime = 0;

        // Настройки для звука
        audioElement.volume = 0.5; // Громкость 50%
        audioElement.loop = loop; // Установка зацикливания

        // Воспроизводим звук
        audioElement.play().catch(e => console.log('Ошибка воспроизведения звука:', e));

        // Возвращаем элемент для возможности управления им
        return audioElement;
    } else {
        // Запасной вариант, если предзагруженный элемент не найден
        // Пути к звуковым файлам
        const sounds = {
            'chest_open': '/src/component/plugins/chests/tpl/sounds/chest_open.mp3',
            'roulette_spin': '/src/component/plugins/chests/tpl/sounds/roulette_spin.mp3',
            'tick': '/src/component/plugins/chests/tpl/sounds/tick.mp3',
            'win': '/src/component/plugins/chests/tpl/sounds/win.mp3'
        };

        // Если звук существует в нашем объекте, воспроизводим его
        if (sounds[soundName]) {
            // Создаем аудио элемент
            const audio = new Audio(sounds[soundName]);

            // Настройки для звука
            audio.volume = 0.5; // Громкость 50%
            audio.loop = loop; // Установка зацикливания

            // Воспроизводим звук
            audio.play().catch(e => console.log('Ошибка воспроизведения звука:', e));

            // Возвращаем элемент для возможности управления им
            return audio;
        }

        return null;
    }
}

// Функция отображения выигрыша
function showWinningItem(item) {
    console.log('Показываем выигрыш:', item);

    // Устанавливаем данные выигрыша
    $('#winning-item-image').attr('src', item.icon);
    $('#winning-item-name').text(item.name + (item.add_name ? ' ' + item.add_name : ''));
    $('#winning-item-count').text('x' + item.count);

    // Добавляем информацию о зачаровании, если есть
    if (item.enchant > 0) {
        $('#winning-item-enchant').text('+' + item.enchant).removeClass('d-none');
    } else {
        $('#winning-item-enchant').addClass('d-none');
    }

    // Если у предмета есть свойство crystal_type со значением 's', добавляем специальные эффекты
    if (item.crystal_type === 's') {
        $('.winning-item').addClass('legendary-item');
        // Можно добавить дополнительные эффекты для редких предметов
    } else {
        $('.winning-item').removeClass('legendary-item');
    }

    // Показываем контейнер с выигрышем
    $('.winning-container')
        .removeClass('d-none')
        .css('opacity', '0')
        .show();

    // Анимация появления выигрыша
    setTimeout(function() {
        $('.winning-container').css('opacity', '1');

        // Добавляем конфетти для празднования
        if (item.crystal_type === 's') {
            createConfetti();
        }
    }, 50);
}

// Функция создания конфетти для редких предметов
function createConfetti() {
    // Проверяем, есть ли контейнер для конфетти
    if (!$('.confetti-container').length) {
        $('.winning-container').append('<div class="confetti-container"></div>');
    }

    // Создаем конфетти
    for (let i = 0; i < 150; i++) {
        const confetti = $('<div class="confetti"></div>');

        // Случайные параметры для конфетти
        const size = Math.random() * 10 + 5;
        const posX = Math.random() * 100;
        const delay = Math.random() * 5;
        const duration = Math.random() * 3 + 3;
        const color = getRandomConfettiColor();

        // Применяем стили
        confetti.css({
            width: size + 'px',
            height: size + 'px',
            left: posX + '%',
            top: '-20px',
            'background-color': color,
            animation: `confetti-fall ${duration}s linear ${delay}s`
        });

        // Добавляем конфетти
        $('.confetti-container').append(confetti);
    }
}

// Генерация случайного цвета для конфетти
function getRandomConfettiColor() {
    const colors = [
        '#f1c40f', // золотой
        '#e74c3c', // красный
        '#3498db', // синий
        '#2ecc71', // зеленый
        '#9b59b6', // фиолетовый
        '#1abc9c', // бирюзовый
        '#f39c12', // оранжевый
    ];

    return colors[Math.floor(Math.random() * colors.length)];
}

// Функция для инициализации всех необходимых вещей при загрузке страницы
$(document).ready(function() {
    // Добавляем все CSS анимации
    addRouletteStyles();

    // Добавляем расширенные функции easing для jQuery
    addJQueryEasing();
});

// Добавление расширенных функций плавности (easing) для jQuery
function addJQueryEasing() {
    // Добавляем функции easing, если они не определены
    if (typeof jQuery.easing.easeInQuad !== 'function') {
        // Расширяем объект jQuery.easing нужными функциями
        jQuery.extend(jQuery.easing, {
            easeInQuad: function(x, t, b, c, d) {
                return c * (t /= d) * t + b;
            },
            easeOutQuad: function(x, t, b, c, d) {
                return -c * (t /= d) * (t - 2) + b;
            },
            easeInOutQuad: function(x, t, b, c, d) {
                if ((t /= d / 2) < 1) return c / 2 * t * t + b;
                return -c / 2 * ((--t) * (t - 2) - 1) + b;
            },
            easeInCubic: function(x, t, b, c, d) {
                return c * (t /= d) * t * t + b;
            },
            easeOutCubic: function(x, t, b, c, d) {
                return c * ((t = t / d - 1) * t * t + 1) + b;
            },
            easeInOutCubic: function(x, t, b, c, d) {
                if ((t /= d / 2) < 1) return c / 2 * t * t * t + b;
                return c / 2 * ((t -= 2) * t * t + 2) + b;
            },
            easeInQuart: function(x, t, b, c, d) {
                return c * (t /= d) * t * t * t + b;
            },
            easeOutQuart: function(x, t, b, c, d) {
                return -c * ((t = t / d - 1) * t * t * t - 1) + b;
            },
            easeInOutQuart: function(x, t, b, c, d) {
                if ((t /= d / 2) < 1) return c / 2 * t * t * t * t + b;
                return -c / 2 * ((t -= 2) * t * t * t - 2) + b;
            }
        });
    }
}

// Функция добавления стилей для рулетки и анимаций
function addRouletteStyles() {
    if (!$('#roulette-styles').length) {
        const styles = `
            /* Стили для контейнера рулетки */
            .roulette-container {
                width: 100%;
                padding: 30px 0;
                position: relative;
                overflow: hidden;
                margin: 20px 0;
            }
            
            /* Viewport для рулетки - видимая область */
            .roulette-viewport {
                width: 100%;
                overflow: hidden;
                position: relative;
                padding: 20px 0;
            }
            
            /* Трек рулетки - лента с предметами */
            .roulette-track {
                display: flex;
                position: relative;
                white-space: nowrap;
                will-change: transform;
                transform: translateX(0);
            }
            
            /* Предмет в рулетке */
            .roulette-item {
                display: inline-flex;
                flex-direction: column;
                align-items: center;
                margin: 0 5px;
                padding: 15px 10px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 10px;
                min-width: 100px;
                transition: all 0.3s ease;
                text-align: center;
            }
            
            /* Контейнер для изображения */
            .roulette-image-container {
                position: relative;
                width: 60px;
                height: 60px;
                margin-bottom: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            /* Изображение предмета */
            .roulette-image {
                max-width: 100%;
                max-height: 100%;
                filter: drop-shadow(0 3px 5px rgba(0, 0, 0, 0.3));
            }
            
            /* Значок зачарования */
            .roulette-enchant {
                position: absolute;
                top: -5px;
                right: -5px;
                width: 22px;
                height: 22px;
                border-radius: 50%;
                background: linear-gradient(135deg, #2ecc71, #27ae60);
                color: white;
                font-size: 12px;
                font-weight: bold;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            }
            
            /* Название предмета */
            .roulette-item-name {
                font-size: 14px;
                font-weight: 600;
                margin-bottom: 5px;
                white-space: normal;
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                height: 36px;
            }
            
            /* Количество предметов */
            .roulette-item-count {
                font-size: 12px;
                opacity: 0.8;
            }
            
            /* Центральный маркер для выделения текущего предмета */
            .roulette-center-highlight {
                position: absolute;
                top: 0;
                left: 50%;
                width: 110px;
                height: 100%;
                transform: translateX(-50%);
                border-left: 2px solid rgba(255, 215, 0, 0.7);
                border-right: 2px solid rgba(255, 215, 0, 0.7);
                pointer-events: none;
                z-index: 100;
                box-shadow: 0 0 15px rgba(255, 215, 0, 0.3);
                transition: all 0.3s ease;
            }
            
            /* Активное состояние центрального маркера */
            .roulette-center-highlight.active {
                border-left: 2px solid rgba(255, 215, 0, 0.9);
                border-right: 2px solid rgba(255, 215, 0, 0.9);
                box-shadow: 0 0 20px rgba(255, 215, 0, 0.6), 
                            inset 0 0 20px rgba(255, 215, 0, 0.3);
            }
            
            /* Эффект тика для центрального маркера */
            .roulette-center-highlight.tick {
                border-left: 3px solid rgba(255, 215, 0, 1);
                border-right: 3px solid rgba(255, 215, 0, 1);
                box-shadow: 0 0 30px rgba(255, 215, 0, 0.8), 
                            inset 0 0 30px rgba(255, 215, 0, 0.5);
            }
            
            /* Эффект свечения для рулетки */
            .roulette-shine-effect {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, 
                                          rgba(255, 255, 255, 0) 0%, 
                                          rgba(255, 255, 255, 0.1) 25%, 
                                          rgba(255, 255, 255, 0.1) 75%, 
                                          rgba(255, 255, 255, 0) 100%);
                pointer-events: none;
                z-index: 50;
            }
            
            /* Выделение выигрышного предмета */
            .winning-item-highlight {
                transform: scale(1.1);
                background: rgba(255, 215, 0, 0.2);
                box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
                z-index: 90;
                position: relative;
            }
            
            @keyframes winner-pulse {
                0% { box-shadow: 0 0 10px rgba(255, 215, 0, 0.5); }
                100% { box-shadow: 0 0 25px rgba(255, 215, 0, 0.8); }
            }
            
            /* Анимация открытия сундука */
            .chest-opening {
                animation: chest-open 2s forwards;
            }
            
            @keyframes chest-open {
                0% { transform: scale(1) translateY(0); filter: brightness(1); }
                10% { transform: scale(1.05) translateY(-10px); filter: brightness(1.2); }
                20% { transform: scale(1) translateY(0); filter: brightness(1); }
                30% { transform: scale(1.1) translateY(-15px); filter: brightness(1.3); }
                40% { transform: scale(1.05) translateY(-5px) rotate(-5deg); filter: brightness(1.2); }
                50% { transform: scale(1.15) translateY(-20px) rotate(5deg); filter: brightness(1.4); }
                60% { transform: scale(1.1) translateY(-10px); filter: brightness(1.3); }
                70% { transform: scale(1.2) translateY(-25px); filter: brightness(1.5); }
                80% { transform: scale(1.15) translateY(-15px) rotate(-3deg); filter: brightness(1.4); }
                90% { transform: scale(1.25) translateY(-30px) rotate(3deg); filter: brightness(1.6); }
                100% { transform: scale(1.2) translateY(-20px); filter: brightness(1.5); }
            }
            
            /* Частицы для анимации открытия сундука */
            .particles-container {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: 10;
            }
            
            .particle {
                position: absolute;
                background-color: rgba(255, 215, 0, 0.8);
                border-radius: 50%;
                pointer-events: none;
                opacity: 0;
            }
            
            @keyframes particle-fly {
                0% { transform: scale(0) translate(0, 0); opacity: 0; }
                10% { opacity: 1; }
                100% { transform: scale(0.2) translate(var(--tx, 100px), var(--ty, -100px)); opacity: 0; }
            }
            
            /* Стили для конфетти */
            .confetti-container {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: 20;
                overflow: hidden;
            }
            
            .confetti {
                position: absolute;
                background-color: #f39c12;
                border-radius: 3px;
                pointer-events: none;
                opacity: 0;
            }
            
            @keyframes confetti-fall {
                0% { transform: translateY(0) rotate(0) scale(1); opacity: 1; }
                100% { transform: translateY(500px) rotate(720deg) scale(0.5); opacity: 0; }
            }
        `;

        $('<style id="roulette-styles"></style>').text(styles).appendTo('head');
    }
}