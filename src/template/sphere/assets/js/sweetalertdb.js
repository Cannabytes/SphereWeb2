

$(".connectionQualityCheck").on("click", function () {
    let type = $(this).attr("data-type");
    let serverId = $(this).attr("data-server-id");

    Swal.fire({
        title: 'Оценка качества соединения с Вашей БД',
        html: `
            <div id="loading-icon" style="display: flex; justify-content: center; align-items: center;">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        `,
        showCloseButton: true,
        showConfirmButton: false,
        allowOutsideClick: true,  // Разрешаем закрытие при клике за пределами окна
        didOpen: () => {
            // Блокируем клавишу ESC для предотвращения закрытия по нажатию
            Swal.getPopup().addEventListener('keydown', (e) => {
                if (e.key === "Escape") {
                    e.stopPropagation();
                }
            });
        }
    });

    // Выполнение AJAX-запроса
    $.ajax({
        url: '/admin/server/db/quality',
        data: {
            type: type,
            id: serverId,
        },
        method: 'POST',
        success: (response) => {
            let data;
            try {
                data = typeof response === 'string' ? JSON.parse(response) : response;
            } catch (e) {
                console.error("Ошибка парсинга JSON:", e);
                Swal.update({
                    html: `<p>Ошибка парсинга ответа сервера.</p>`,
                    showConfirmButton: true
                });
                return;
            }

            const formatTime = (ms) => (ms / 1000000).toFixed(3) + ' мсек';

            // Формируем HTML-контент на основе данных JSON
            let responseHtml = `
                <p><strong>Качество соединения:</strong> ${data.evaluate || 'Нет данных'}</p>
                <p><strong>Время соединения:</strong> ${data.connectionTime ? formatTime(data.connectionTime) : 'Нет данных'}</p>
                <p><strong>Время пинга:</strong> ${data.pingTime ? formatTime(data.pingTime) : 'Нет данных'}</p>
                <p><strong>Время выполнения запроса:</strong> ${data.queryTime ? formatTime(data.queryTime) : 'Нет данных'}</p>
            `;

            Swal.update({
                html: responseHtml,
                showConfirmButton: true
            });
        },
        error: () => {
            Swal.update({
                html: `<p>Ошибка соединения с сервером.</p>`,
                showConfirmButton: true
            });
        }
    });
});




$(".portQualityCheck").on("click", function () {
    let type = $(this).attr("data-type");
    let serverId = $(this).attr("data-server-id");
    let name = "GameServer"
    if (type==="login"){
        name = "LoginServer"
    }

    // Показываем SweetAlert с анимацией загрузки до выполнения запроса
    Swal.fire({
        title: 'Оценка качества соединения с ' + name,
        html: `
            <div id="loading-icon" style="display: flex; justify-content: center; align-items: center;">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        `,
        showCloseButton: true,
        showConfirmButton: false,
        allowOutsideClick: true,  // Разрешаем закрытие при клике за пределами окна
        didOpen: () => {
            // Блокируем клавишу ESC для предотвращения закрытия по нажатию
            Swal.getPopup().addEventListener('keydown', (e) => {
                if (e.key === "Escape") {
                    e.stopPropagation();
                }
            });
        }
    });

    // Выполнение AJAX-запроса
    $.ajax({
        url: '/admin/server/port/quality',
        data: {
            type: type,
            id: serverId,
        },
        method: 'POST',
        success: (response) => {
            let data;
            try {
                data = typeof response === 'string' ? JSON.parse(response) : response;
            } catch (e) {
                console.error("Ошибка парсинга JSON:", e);
                Swal.update({
                    html: `<p>Ошибка парсинга ответа сервера.</p>`,
                    showConfirmButton: true
                });
                return;
            }

            // Формируем HTML-контент на основе данных JSON
            let responseHtml = `
                <p><strong>Качество соединения:</strong> ${data.evaluate || 'Нет данных'}</p>
                <p><strong>Время пинга:</strong> ${data.pingTime ? data.pingTime  + ' мсек' : 'Нет данных'}</p>
            `;

            Swal.update({
                html: responseHtml,
                showConfirmButton: true
            });
        },
        error: () => {
            Swal.update({
                html: `<p>Ошибка соединения с сервером.</p>`,
                showConfirmButton: true
            });
        }
    });
});


