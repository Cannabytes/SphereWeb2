(function() {
    // Константы
    const iconUrl = 'https://l2hub.info/s/icons/etc_adena_i00.png';
    const iconSize = 32;  // размер иконки в пикселях
    const triggerWord = 'хуй'; // Слово-триггер для запуска анимации

    // Переменная для отслеживания последовательности нажатий
    let keySequence = '';
    let animationActive = false;
    let animationTimeout = null;

    // Функция для анимации улета элементов сайта перед основной анимацией
    function animateSiteElementsFlyAway() {
        // Сохраняем оригинальные стили для последующего восстановления
        const elementsToAnimate = document.querySelectorAll('body > *:not(#word-container)');
        const originalStyles = [];

        // Добавляем плавность для всех элементов (увеличен время transition)
        const styleTag = document.createElement('style');
        styleTag.textContent = `
            body > *:not(#word-container) {
                transition: all 2.5s cubic-bezier(0.25, 0.1, 0.25, 1) !important;
            }
        `;
        document.head.appendChild(styleTag);

        // Сначала сохраняем оригинальные стили, затем применяем анимацию
        elementsToAnimate.forEach((el, index) => {
            if (el.id !== 'word-container') {
                // Сохраняем текущие стили
                originalStyles.push({
                    element: el,
                    position: el.style.position,
                    top: el.style.top,
                    left: el.style.left,
                    transform: el.style.transform,
                    opacity: el.style.opacity,
                    zIndex: el.style.zIndex
                });

                // Устанавливаем относительную позицию для элементов, если они статичны
                if (getComputedStyle(el).position === 'static') {
                    el.style.position = 'relative';
                }

                // Добавляем z-index для корректного наложения
                el.style.zIndex = 1000 + index;

                // Задержка перед анимацией для каждого элемента
                setTimeout(() => {
                    // Начинаем с небольшой анимации для привлечения внимания
                    el.style.transform = 'scale(1.05)';
                    el.style.opacity = '0.9';

                    // Затем с задержкой применяем основную анимацию улета
                    setTimeout(() => {
                        // Случайное направление улета, но с более сдержанным вращением
                        const direction = Math.floor(Math.random() * 4);
                        const distance = Math.max(window.innerWidth, window.innerHeight) * 1.2;
                        const rotation = (Math.random() - 0.5) * 180; // Более умеренное вращение ±90°

                        switch(direction) {
                            case 0: // вверх
                                el.style.transform = `translateY(-${distance}px) rotate(${rotation}deg)`;
                                break;
                            case 1: // вправо
                                el.style.transform = `translateX(${distance}px) rotate(${rotation}deg)`;
                                break;
                            case 2: // вниз
                                el.style.transform = `translateY(${distance}px) rotate(${rotation}deg)`;
                                break;
                            case 3: // влево
                                el.style.transform = `translateX(-${distance}px) rotate(${rotation}deg)`;
                                break;
                        }

                        // Плавное затухание
                        el.style.opacity = '0';
                    }, 400);
                }, index * 120); // Увеличенная задержка для более выраженного каскадного эффекта
            }
        });

        // Возвращаем элементы на место после основной анимации
        setTimeout(() => {
            // Создаем новый стиль для плавного возвращения элементов
            const returnStyleTag = document.createElement('style');
            returnStyleTag.textContent = `
                body > *:not(#word-container) {
                    transition: all 3s cubic-bezier(0.16, 1, 0.3, 1) !important;
                }
            `;
            document.head.appendChild(returnStyleTag);

            // Удаляем предыдущий стиль
            document.head.removeChild(styleTag);

            // Возвращаем элементы в исходное положение с каскадной задержкой
            originalStyles.forEach((item, i) => {
                setTimeout(() => {
                    // Сначала делаем элемент слегка видимым
                    item.element.style.opacity = '0.3';

                    // Затем с небольшой задержкой плавно возвращаем в исходное состояние
                    setTimeout(() => {
                        item.element.style.position = item.position;
                        item.element.style.top = item.top;
                        item.element.style.left = item.left;
                        item.element.style.transform = item.transform || 'none';
                        item.element.style.opacity = item.opacity || '1';
                        item.element.style.zIndex = item.zIndex;
                    }, 200);
                }, i * 100); // Каскадная задержка для возвращения
            });

            // Удаляем стиль возвращения после завершения
            setTimeout(() => {
                if (document.head.contains(returnStyleTag)) {
                    document.head.removeChild(returnStyleTag);
                }
            }, 5000);
        }, 8000); // Увеличенное время ожидания для завершения основной анимации
    }

    // Функция запуска анимации
    function startAnimation() {
        if (animationActive) return; // Предотвращаем повторный запуск, если анимация уже идет
        animationActive = true;

        // Очищаем предыдущую анимацию, если есть
        if (animationTimeout) {
            clearTimeout(animationTimeout);
        }

        // Удаляем предыдущий контейнер, если он существует
        const oldContainer = document.getElementById('word-container');
        if (oldContainer) {
            document.body.removeChild(oldContainer);
        }

        // Запускаем анимацию улета элементов сайта
        animateSiteElementsFlyAway();

        // Задержка перед основной анимацией - увеличена для более плавного эффекта
        setTimeout(() => {
            // Создаем контейнер
            const container = document.createElement('div');
            container.id = 'word-container';
            container.style.position = 'fixed';
            container.style.top = '0';
            container.style.left = '0';
            container.style.width = '100%';
            container.style.height = '100%';
            container.style.zIndex = '99999';
            container.style.pointerEvents = 'none';
            document.body.appendChild(container);

            // Получаем центр экрана
            const centerX = window.innerWidth / 2;
            const centerY = window.innerHeight / 2;

            // Жестко заданные точки для каждой буквы с повышенной плотностью
            const letterPositions = [
                // Буква "Х" (первая буква)
                // Диагональ слева вверху вправо вниз
                [-3, -2], [-2.8, -1.8], [-2.6, -1.6], [-2.4, -1.4], [-2.2, -1.2],
                [-2, -1], [-1.8, -0.8], [-1.6, -0.6], [-1.4, -0.4], [-1.2, -0.2],
                [-1, 0], [-0.8, 0.2], [-0.6, 0.4], [-0.4, 0.6], [-0.2, 0.8],
                [0, 1], [0.2, 1.2], [0.4, 1.4], [0.6, 1.6], [0.8, 1.8], [1, 2],

                // Диагональ слева внизу вправо вверх
                [-3, 2], [-2.8, 1.8], [-2.6, 1.6], [-2.4, 1.4], [-2.2, 1.2],
                [-2, 1], [-1.8, 0.8], [-1.6, 0.6], [-1.4, 0.4], [-1.2, 0.2],
                [-1, 0], [-0.8, -0.2], [-0.6, -0.4], [-0.4, -0.6], [-0.2, -0.8],
                [0, -1], [0.2, -1.2], [0.4, -1.4], [0.6, -1.6], [0.8, -1.8], [1, -2],

                // Буква "У" (вторая буква)
                // Левая ветвь
                [1.5, -2], [1.7, -1.7], [1.9, -1.4], [2.1, -1.1], [2.3, -0.8],
                [2.5, -0.5], [2.7, -0.2], [2.9, 0.1], [3.1, 0.4], [3.3, 0.7],

                // Правая ветвь
                [4.5, -2], [4.3, -1.7], [4.1, -1.4], [3.9, -1.1], [3.7, -0.8],
                [3.5, -0.5], [3.3, -0.2], [3.1, 0.1], [2.9, 0.4], [2.7, 0.7],

                // Вертикальная ножка
                [3, 0.6], [3, 0.8], [3, 1.0], [3, 1.2], [3, 1.4],
                [3, 1.6], [3, 1.8], [3, 2.0],

                // Буква "Й" (третья буква) - правильная кириллическая И с точкой
                // Левая вертикальная линия
                [5.5, -2], [5.5, -1.8], [5.5, -1.6], [5.5, -1.4], [5.5, -1.2],
                [5.5, -1], [5.5, -0.8], [5.5, -0.6], [5.5, -0.4], [5.5, -0.2],
                [5.5, 0], [5.5, 0.2], [5.5, 0.4], [5.5, 0.6], [5.5, 0.8],
                [5.5, 1], [5.5, 1.2], [5.5, 1.4], [5.5, 1.6], [5.5, 1.8], [5.5, 2],

                // Правая вертикальная линия
                [7.5, -2], [7.5, -1.8], [7.5, -1.6], [7.5, -1.4], [7.5, -1.2],
                [7.5, -1], [7.5, -0.8], [7.5, -0.6], [7.5, -0.4], [7.5, -0.2],
                [7.5, 0], [7.5, 0.2], [7.5, 0.4], [7.5, 0.6], [7.5, 0.8],
                [7.5, 1], [7.5, 1.2], [7.5, 1.4], [7.5, 1.6], [7.5, 1.8], [7.5, 2],

                // Соединительная наклонная линия в ПРАВИЛЬНОМ направлении (/)
                [5.5, 0], [5.7, -0.2], [5.9, -0.4], [6.1, -0.6], [6.3, -0.8],
                [6.5, -1.0], [6.7, -1.2], [6.9, -1.4], [7.1, -1.6], [7.3, -1.8], [7.5, -2],

                // Точка над Й (компактная и выше буквы)
                [6.4, -3.0], [6.5, -3.0], [6.6, -3.0],
                [6.4, -2.9], [6.5, -2.9], [6.6, -2.9],
                [6.4, -2.8], [6.5, -2.8], [6.6, -2.8]
            ];

            // Масштаб для букв
            const scale = iconSize * 2.5;

            // Функция для создания иконки в заданной позиции
            function createIcon(x, y, delay) {
                const icon = document.createElement('img');
                icon.src = iconUrl;
                icon.style.position = 'absolute';
                icon.style.width = iconSize + 'px';
                icon.style.height = iconSize + 'px';

                // Стартовая позиция (случайная по краям экрана)
                const startPos = getRandomStartPosition();
                icon.style.left = startPos.x + 'px';
                icon.style.top = startPos.y + 'px';
                icon.style.opacity = '0';
                icon.style.transition = 'all 0s';

                container.appendChild(icon);

                // Запускаем анимацию после задержки
                setTimeout(() => {
                    icon.style.transition = 'all 1s ease-in-out';
                    icon.style.left = x + 'px';
                    icon.style.top = y + 'px';
                    icon.style.opacity = '1';

                    // Анимация разлета через 4 секунды
                    setTimeout(() => {
                        const endPos = getRandomStartPosition();
                        icon.style.transition = 'all 1.5s ease-in-out';
                        icon.style.left = endPos.x + 'px';
                        icon.style.top = endPos.y + 'px';
                        icon.style.opacity = '0';

                        // Удаляем элемент после анимации
                        setTimeout(() => {
                            if (icon.parentNode) {
                                icon.parentNode.removeChild(icon);
                            }
                        }, 1500);
                    }, 4000);
                }, delay);
            }

            // Функция для получения случайной стартовой позиции по краю экрана
            function getRandomStartPosition() {
                const side = Math.floor(Math.random() * 4); // 0: top, 1: right, 2: bottom, 3: left
                let x, y;

                switch(side) {
                    case 0: // top
                        x = Math.random() * window.innerWidth;
                        y = -iconSize * 2;
                        break;
                    case 1: // right
                        x = window.innerWidth + iconSize * 2;
                        y = Math.random() * window.innerHeight;
                        break;
                    case 2: // bottom
                        x = Math.random() * window.innerWidth;
                        y = window.innerHeight + iconSize * 2;
                        break;
                    case 3: // left
                        x = -iconSize * 2;
                        y = Math.random() * window.innerHeight;
                        break;
                }

                return { x, y };
            }

            // Создаем все иконки для букв
            letterPositions.forEach((pos, index) => {
                const x = centerX + pos[0] * scale;
                const y = centerY + pos[1] * scale;
                createIcon(x, y, index * 20); // Более быстрое появление иконок
            });
        }, 3000); // Увеличена задержка до 3 секунд перед основной анимацией

        // Устанавливаем таймаут для окончания анимации
        animationTimeout = setTimeout(() => {
            animationActive = false;
        }, 16000); // Увеличено до 16 секунд для учета более длительных анимаций
    }

    // Функция для обработки нажатий клавиш
    function handleKeyPress(e) {
        // Получаем символ, соответствующий нажатой клавише
        let key = '';

        // Обрабатываем русские буквы
        if (e.key.length === 1) {
            key = e.key.toLowerCase();
        }

        // Добавляем символ к последовательности
        keySequence += key;

        // Ограничиваем длину последовательности
        if (keySequence.length > 10) {
            keySequence = keySequence.substring(keySequence.length - 10);
        }

        // Проверяем, содержит ли последовательность триггерное слово
        if (keySequence.includes(triggerWord)) {
            startAnimation();
            keySequence = ''; // Сбрасываем последовательность после запуска
        }
    }

    // Добавляем обработчик событий для отслеживания нажатий клавиш
    document.addEventListener('keydown', handleKeyPress);


    // Массив для хранения последних введенных символов
    let typedChars = [];
    const targetWord = "пизда";

// Функция для создания временного фона
    function createTemporaryBackground() {
        // Создаем элемент div для фона
        const backgroundElement = document.createElement('div');

        // Устанавливаем стили для фонового элемента
        backgroundElement.style.position = 'fixed';
        backgroundElement.style.top = '0';
        backgroundElement.style.left = '0';
        backgroundElement.style.width = '100%';
        backgroundElement.style.height = '100%';
        backgroundElement.style.backgroundImage = 'url("https://web-promo.ua/wp-content/uploads/2024/09/memi-klev-club-9ria-p-memi-udivlyayushchiisya-kot-1-1024x768.jpg")';
        backgroundElement.style.backgroundSize = 'cover';
        backgroundElement.style.backgroundPosition = 'center';
        backgroundElement.style.zIndex = '9999';

        // Добавляем элемент в body
        document.body.appendChild(backgroundElement);

        // Устанавливаем таймер на удаление через 1 секунду
        setTimeout(() => {
            if (document.body.contains(backgroundElement)) {
                document.body.removeChild(backgroundElement);
            }
        }, 1000);
    }

// Функция для проверки введенных символов
    function checkTypedWord() {
        const lastTyped = typedChars.join('').toLowerCase();

        // Проверяем, содержит ли последовательность введенных символов целевое слово
        if (lastTyped.includes(targetWord)) {
            // Очищаем массив
            typedChars = [];
            // Показываем изображение
            createTemporaryBackground();
        }
    }

// Обработчик события keydown для отслеживания вводимых символов
    document.addEventListener('keydown', function(event) {
        // Добавляем символ в массив
        if (event.key.length === 1) {
            typedChars.push(event.key);

            // Ограничиваем размер массива
            if (typedChars.length > 20) {
                typedChars.shift();
            }

            // Проверяем введенное слово
            checkTypedWord();
        }
    });

})();

