<?php

namespace Ofey\Logan22\component\error;

/**
 * Класс для обработки ошибок с использованием статических методов
 */
class error
{
    private static $projectRoot;
    const GITHUB_REPO = 'https://github.com/Cannabytes/SphereWeb2/blob/main';

    /**
     * Функция для замены абсолютных путей в сообщении ошибки
     */
    static function clean_error_message($message)
    {
        return str_replace(self::$projectRoot . '/', '', $message);
    }

    /**
     * Получение относительного пути от корня проекта
     */
    static function get_relative_path($file)
    {
        return str_replace(self::$projectRoot . '/', '', $file);
    }

    /**
     * Функция для генерации ссылки с кнопками "Копировать" и "Перейти"
     */
    private static function generate_file_link($file, $line)
    {
        $relativePath = self::get_relative_path($file) . ':' . (int)$line;
        $githubUrl = self::GITHUB_REPO . '/' . self::get_relative_path($file) . '#L' . (int)$line;

        return '
        <span class="file-path">' . htmlspecialchars($relativePath) . '</span>
        <span class="file-actions">
            <a href="' . htmlspecialchars($githubUrl) . '" target="_blank" class="icon-link" title="Открыть в GitHub">🔗</a>
            <button onclick="copyToClipboard(\'' . htmlspecialchars($relativePath) . '\')" class="icon-copy" title="Копировать путь">📋</button>
        </span>';
    }

    /**
     * Записывает ошибку в файл лога
     */
    private static function log_error($type, $message, $file, $line, $trace = null)
    {
        $timestamp = date('Y-m-d H:i:s');
        $relativePath = self::get_relative_path($file);

        $logMessage = sprintf(
            "[%s] %s: %s in %s:%d\n",
            $timestamp,
            $type,
            $message,
            $relativePath,
            $line
        );

        if ($trace && is_array($trace)) {
            $logMessage .= "Stack trace:\n";
            foreach ($trace as $index => $traceItem) {
                $function = isset($traceItem['function']) ? $traceItem['function'] : 'Unknown';
                $traceFile = isset($traceItem['file']) ? self::get_relative_path($traceItem['file']) : 'Unknown file';
                $traceLine = isset($traceItem['line']) ? $traceItem['line'] : 'Unknown line';

                $logMessage .= sprintf(
                    "  #%d %s() called at %s:%s\n",
                    $index,
                    $function,
                    $traceFile,
                    $traceLine
                );
            }
        }

        $logMessage .= str_repeat('-', 80) . "\n";

        // Записываем в файл лога
        error_log($logMessage, 3, ini_get('error_log'));
    }

    /**
     * Красивый HTML-шаблон ошибки
     */
    static function render_error_page($title, $message, $type, $file, $line, $trace = [])
    {
        ?>
		<!DOCTYPE html>
		<html lang="ru">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo htmlspecialchars($title); ?></title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f8f8f8;
                    color: #333;
                    margin: 0;
                    padding: 20px;
                }

                .error-container {
                    max-width: 800px;
                    margin: 0 auto;
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
                }

                h2 {
                    color: #d9534f;
                }

                .error-message {
                    background: #ffebee;
                    padding: 15px;
                    border-left: 4px solid #d9534f;
                    margin-bottom: 20px;
                    word-wrap: break-word;
                }

                .error-details {
                    background: #f3f3f3;
                    padding: 10px;
                    border-radius: 5px;
                }

                .stack-trace {
                    background: #e3f2fd;
                    padding: 10px;
                    border-radius: 5px;
                    font-size: 14px;
                    line-height: 1.4;
                    word-wrap: break-word;
                    font-family: monospace;
                }

                .stack-trace ul {
                    padding: 0;
                    list-style: none;
                }

                .stack-trace li {
                    padding: 5px;
                    border-bottom: 1px solid #ddd;
                    display: flex;
                    align-items: center;
                }

                .stack-trace li:last-child {
                    border-bottom: none;
                }

                .file-actions {
                    display: inline-flex;
                    gap: 3px;
                }

                .icon-link, .icon-copy {
                    text-decoration: none;
                    cursor: pointer;
                    font-size: 16px;
                }

                .icon-copy {
                    background: none;
                    border: none;
                    cursor: pointer;
                }

                .function-name {
                    min-width: 300px;
                    display: inline-block;
                    text-align: left;
                }
            </style>
            <script>
                function copyToClipboard(text) {
                    navigator.clipboard.writeText(text).then(() => {
                        // Можно добавить визуальную обратную связь
                    }).catch(err => {
                        console.error('Ошибка копирования: ', err);
                    });
                }
            </script>
        </head>
        <body>
            <div class="error-container">
                <h2>Произошла ошибка</h2>
                <div class="error-message">
                    <p><strong>Тип:</strong> <?= htmlspecialchars($type) ?></p>
                    <p><strong>Сообщение:</strong> <?= htmlspecialchars(self::clean_error_message($message)) ?></p>
                </div>
                <div class="error-details">
                    <p><strong>Файл:</strong> <?= self::generate_file_link($file, $line) ?></p>
                </div>
                <?php if (!empty($trace)): ?>
		            <h3>Стек вызовов:</h3>
		            <div class="stack-trace">
                        <ul>
                            <?php foreach ($trace as $index => $traceItem): ?>
	                            <li>
                                    <span class="function-name">
                                        <?= ($index + 1) ?>. <strong><?= htmlspecialchars($traceItem['function'] ?? 'Неизвестная функция') ?></strong>
                                    </span>
                                    <?= isset($traceItem['file']) ? self::generate_file_link($traceItem['file'], $traceItem['line'] ?? '0') : 'Файл неизвестен' ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Обработчик исключений
     */
    public static function exception_handler($exception)
    {
        // Сначала записываем в лог
        self::log_error(
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTrace()
        );

        // Затем отображаем страницу ошибки
        http_response_code(500);

        // Очищаем буфер вывода, если он есть
        if (ob_get_level()) {
            ob_clean();
        }

        self::render_error_page(
            "Произошла ошибка",
            self::clean_error_message($exception->getMessage()),
            get_class($exception),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTrace()
        );
    }

    /**
     * Обработчик фатальных ошибок
     */
    public static function fatal_error_handler()
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            // Сначала записываем в лог
            self::log_error(
                "PHP Fatal Error",
                $error['message'],
                $error['file'],
                $error['line']
            );

            // Затем отображаем страницу ошибки
            http_response_code(500);

            // Очищаем буфер вывода, если он есть
            if (ob_get_level()) {
                ob_clean();
            }

            self::render_error_page(
                "Фатальная ошибка",
                self::clean_error_message($error['message']),
                "PHP Fatal Error",
                $error['file'],
                $error['line']
            );
        }
    }

    /**
     * Инициализация обработчиков ошибок
     */
    public static function init(): void
    {
        self::$projectRoot = dirname(__DIR__, 3);
        set_exception_handler([self::class, 'exception_handler']);
        register_shutdown_function([self::class, 'fatal_error_handler']);
    }
}