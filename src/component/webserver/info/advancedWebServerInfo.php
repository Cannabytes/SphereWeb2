<?php

namespace Ofey\Logan22\component\webserver\info;

class advancedWebServerInfo
{
    private array $systemInfo = [];
    protected bool $isLinux = false;
    private bool $hasSystemAccess = false;

    public function __construct()
    {
        $this->isLinux = PHP_OS_FAMILY === 'Linux';
        $this->checkSystemAccess();
        $this->initializeSystemInfo();
    }


    /**
     * Проверка доступа к системным файлам с учетом open_basedir
     */
    private function checkSystemAccess(): void
    {
        $this->hasSystemAccess = false;

        if ($this->isLinux) {
            $openBasedir = ini_get('open_basedir');
            if (empty($openBasedir)) {
                $this->hasSystemAccess = true;
            } else {
                $allowedPaths = explode(':', $openBasedir);
                foreach ($allowedPaths as $path) {
                    if (str_starts_with('/proc', $path)) {
                        $this->hasSystemAccess = true;
                        break;
                    }
                }
            }
        }
    }

    /**
     * Инициализация всех секций информации о системе
     */
    private function initializeSystemInfo(): void
    {
        $this->systemInfo = [
            'main' => [
                'title' => 'Основные параметры PHP',
                'items' => $this->getMainParameters()
            ],
            'security' => [
                'title' => 'Параметры безопасности',
                'items' => $this->getSecurityParameters()
            ],
            'extensions' => [
                'title' => 'Критические расширения PHP',
                'items' => $this->getExtensionsInfo()
            ],
            'uploads' => [
                'title' => 'Настройки загрузки файлов',
                'items' => $this->getUploadParameters()
            ],
            'sessions' => [
                'title' => 'Настройки сессий',
                'items' => $this->getSessionParameters()
            ],
            'system' => [
                'title' => 'Системная информация',
                'items' => $this->getSystemParameters()
            ],
            'system_resources' => [
                'title' => 'Системные ресурсы',
                'items' => $this->getSystemResources()
            ],
            'php_advanced' => [
                'title' => 'Расширенные настройки PHP',
                'items' => $this->getAdvancedPHPParameters()
            ],
            'network' => [
                'title' => 'Сетевые настройки',
                'items' => $this->getNetworkParameters()
            ]
        ];
    }

    /**
     * Получение основных параметров PHP
     * @return array
     */
    private function getMainParameters(): array
    {
        return [
            'php_version' => [
                'value' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '7.4.0', '>=') ? 'success' : 'danger',
                'description' => 'Версия PHP',
                'recommendation' => version_compare(PHP_VERSION, '7.4.0', '>=') ?
                    'Версия PHP актуальна' :
                    'Рекомендуется обновить PHP до версии 7.4 или выше'
            ],
            'php_sapi' => [
                'value' => php_sapi_name(),
                'status' => 'info',
                'description' => 'Интерфейс PHP',
                'recommendation' => 'Информационный параметр'
            ],
            'memory_limit' => [
                'value' => ini_get('memory_limit'),
                'status' => $this->evaluateMemoryLimit(ini_get('memory_limit')),
                'description' => 'Лимит памяти PHP',
                'recommendation' => $this->getMemoryLimitRecommendation(ini_get('memory_limit'))
            ]
        ];
    }

    /**
     * Получение параметров безопасности
     * @return array
     */
    private function getSecurityParameters(): array
    {
        return [
            'expose_php' => [
                'value' => ini_get('expose_php') ? 'Включено' : 'Выключено',
                'status' => ini_get('expose_php') ? 'danger' : 'success',
                'description' => 'Отображение версии PHP',
                'recommendation' => ini_get('expose_php') ?
                    'Рекомендуется отключить для безопасности' :
                    'Правильно настроено'
            ],
            'allow_url_fopen' => [
                'value' => ini_get('allow_url_fopen') ? 'Включено' : 'Выключено',
                'status' => ini_get('allow_url_fopen') ? 'warning' : 'success',
                'description' => 'Разрешение URL-врапперов',
                'recommendation' => 'Отключить, если не используется'
            ],
            'display_errors' => [
                'value' => ini_get('display_errors') ? 'Включено' : 'Выключено',
                'status' => ini_get('display_errors') ? 'danger' : 'success',
                'description' => 'Отображение ошибок',
                'recommendation' => ini_get('display_errors') ?
                    'Отключить на производственном сервере' :
                    'Правильно настроено'
            ]
        ];
    }

    /**
     * Получение информации о расширениях PHP
     * @return array
     */
    private function getExtensionsInfo(): array
    {
        $extensions = [
            'mysql' => ['mysqli', 'MySQL (mysqli)', 'Требуется для работы с MySQL'],
            'pdo' => ['pdo', 'PDO', 'Требуется для безопасной работы с БД'],
            'gd' => ['gd', 'GD Library', 'Требуется для работы с изображениями'],
            'curl' => ['curl', 'cURL', 'Требуется для внешних API запросов'],
            'openssl' => ['openssl', 'OpenSSL', 'Критично для безопасности']
        ];

        $result = [];
        foreach ($extensions as $key => [$ext, $name, $recommendation]) {
            $loaded = extension_loaded($ext);
            $version = $loaded ? phpversion($ext) : null;

            $result[$key] = [
                'value' => $loaded ? ($version ? "Установлено ($version)" : 'Установлено') : 'Не установлено',
                'status' => $loaded ? 'success' : 'danger',
                'description' => $name,
                'recommendation' => $loaded ? 'OK' : $recommendation
            ];
        }

        return $result;
    }

    /**
     * Получение параметров загрузки файлов
     * @return array
     */
    private function getUploadParameters(): array
    {
        return [
            'upload_max_filesize' => [
                'value' => ini_get('upload_max_filesize'),
                'status' => $this->evaluateSize(ini_get('upload_max_filesize'), 10),
                'description' => 'Максимальный размер файла',
                'recommendation' => 'Рекомендуется минимум 10MB'
            ],
            'post_max_size' => [
                'value' => ini_get('post_max_size'),
                'status' => $this->evaluateSize(ini_get('post_max_size'), 10),
                'description' => 'Максимальный размер POST',
                'recommendation' => 'Должен быть больше upload_max_filesize'
            ],
            'max_file_uploads' => [
                'value' => ini_get('max_file_uploads'),
                'status' => (intval(ini_get('max_file_uploads')) >= 20) ? 'success' : 'warning',
                'description' => 'Максимальное количество файлов',
                'recommendation' => 'Рекомендуется 20 или больше'
            ]
        ];
    }

    /**
     * Получение параметров сессий
     * @return array
     */
    private function getSessionParameters(): array
    {
        return [
            'session.gc_maxlifetime' => [
                'value' => ini_get('session.gc_maxlifetime') . ' секунд',
                'status' => (intval(ini_get('session.gc_maxlifetime')) >= 1440) ? 'success' : 'warning',
                'description' => 'Время жизни сессии',
                'recommendation' => 'Рекомендуется 1440 секунд или больше'
            ],
            'session.cookie_secure' => [
                'value' => ini_get('session.cookie_secure') ? 'Да' : 'Нет',
                'status' => ini_get('session.cookie_secure') ? 'success' : 'warning',
                'description' => 'Безопасные cookie',
                'recommendation' => !ini_get('session.cookie_secure') ? 'Включить для HTTPS' : 'OK'
            ]
        ];
    }

    /**
     * Получение системных параметров
     * @return array
     */
    private function getSystemParameters(): array
    {
        return [
            'os' => [
                'value' => PHP_OS,
                'status' => 'info',
                'description' => 'Операционная система',
                'recommendation' => 'Информационный параметр'
            ],
            'server_software' => [
                'value' => $_SERVER['SERVER_SOFTWARE'] ?? 'Неизвестно',
                'status' => 'info',
                'description' => 'Веб-сервер',
                'recommendation' => 'Рекомендуется Apache или Nginx последних версий'
            ],
            'architecture' => [
                'value' => php_uname('m'),
                'status' => 'info',
                'description' => 'Архитектура системы',
                'recommendation' => 'Информационный параметр'
            ]
        ];
    }

    /**
     * Получение информации о системных ресурсах
     * @return array
     */
    private function getSystemResources(): array
    {
        $resources = [
            'memory_usage' => [
                'value' => $this->formatBytes(memory_get_usage(true)),
                'status' => (memory_get_usage(true) < memory_get_peak_usage(true) * 0.75) ? 'success' : 'warning',
                'description' => 'Использование памяти PHP',
                'recommendation' => 'Текущее использование памяти скриптом'
            ],
            'memory_peak' => [
                'value' => $this->formatBytes(memory_get_peak_usage(true)),
                'status' => 'info',
                'description' => 'Пиковое использование памяти',
                'recommendation' => 'Максимальное использование памяти скриптом'
            ]
        ];

        if ($this->isLinux) {
            $resources = array_merge($resources, $this->getLinuxSystemResources());
        }

        return $resources;
    }

    private function safeReadFile(string $filepath): ?string
    {
        if (!$this->hasSystemAccess) {
            return null;
        }

        try {
            if (!file_exists($filepath)) {
                return null;
            }
            return @file_get_contents($filepath);
        } catch (\Throwable $e) {
            error_log("Ошибка при чтении файла {$filepath}: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Получение специфичной для Linux информации о ресурсах
     * @return array
     */
    private function getLinuxSystemResources(): array
    {
        $resources = [];

        if ($this->hasSystemAccess) {
            // Чтение информации о памяти
            $memInfo = $this->safeReadFile('/proc/meminfo');
            if ($memInfo !== null) {
                $resources = array_merge($resources, $this->parseMemInfo($memInfo));
            } else {
                $resources['memory_info'] = [
                    'value' => 'Недоступно',
                    'status' => 'warning',
                    'description' => 'Информация о памяти',
                    'recommendation' => 'Ограничение open_basedir препятствует получению информации'
                ];
            }

            // Чтение информации о CPU
            $cpuInfo = $this->safeReadFile('/proc/cpuinfo');
            if ($cpuInfo !== null) {
                $resources = array_merge($resources, $this->parseCpuInfo($cpuInfo));
            } else {
                $resources['cpu_info'] = [
                    'value' => 'Недоступно',
                    'status' => 'warning',
                    'description' => 'Информация о процессоре',
                    'recommendation' => 'Ограничение open_basedir препятствует получению информации'
                ];
            }
        } else {
            $resources['system_access'] = [
                'value' => 'Ограничено',
                'status' => 'warning',
                'description' => 'Доступ к системной информации',
                'recommendation' => 'Проверьте настройки open_basedir для доступа к /proc'
            ];
        }

        // Добавление доступной системной информации
        if (function_exists('sys_getloadavg')) {
            try {
                $resources = array_merge($resources, $this->getLoadAverageInfo());
            } catch (\Throwable $e) {
                $resources['load_average'] = [
                    'value' => 'Ошибка получения данных',
                    'status' => 'warning',
                    'description' => 'Средняя нагрузка',
                    'recommendation' => 'Проверьте права доступа и системные ограничения'
                ];
            }
        }

        return $resources;
    }

    /**
     * Получение расширенных параметров PHP
     * @return array
     */
    private function getAdvancedPHPParameters(): array
    {
        return [
            'opcache' => [
                'value' => $this->getOpcacheStatus(),
                'status' => extension_loaded('Zend OPcache') && ini_get('opcache.enable') ?
                    'success' : 'warning',
                'description' => 'OPcache',
                'recommendation' => 'Рекомендуется включить для повышения производительности'
            ],
            'max_input_vars' => [
                'value' => ini_get('max_input_vars'),
                'status' => (intval(ini_get('max_input_vars')) >= 3000) ? 'success' : 'warning',
                'description' => 'Максимум входных переменных',
                'recommendation' => 'Рекомендуется 3000+ для CMS'
            ],
            'max_execution_time' => [
                'value' => ini_get('max_execution_time') . ' сек.',
                'status' => (intval(ini_get('max_execution_time')) >= 30) ? 'success' : 'warning',
                'description' => 'Макс. время выполнения',
                'recommendation' => 'Рекомендуется 30+ секунд'
            ],
            'error_reporting' => [
                'value' => $this->getErrorReportingLevel(),
                'status' => 'info',
                'description' => 'Уровень отчетов об ошибках',
                'recommendation' => 'E_ALL рекомендуется для разработки'
            ]
        ];
    }

    /**
     * Получение сетевых параметров
     * @return array
     */
    private function getNetworkParameters(): array
    {
        return [
            'default_socket_timeout' => [
                'value' => ini_get('default_socket_timeout') . ' сек.',
                'status' => (intval(ini_get('default_socket_timeout')) >= 60) ? 'success' : 'warning',
                'description' => 'Тайм-аут сокета',
                'recommendation' => 'Рекомендуется 60+ секунд'
            ],
            'output_buffering' => [
                'value' => ini_get('output_buffering') ? 'Включено' : 'Выключено',
                'status' => ini_get('output_buffering') ? 'success' : 'warning',
                'description' => 'Буферизация вывода',
                'recommendation' => 'Рекомендуется включить'
            ]
        ];
    }

    /**
     * Вспомогательные методы
     */

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }

    /**
     * Оценка достаточности лимита памяти
     * @param string $memoryLimit
     * @return string
     */
    private function evaluateMemoryLimit(string $memoryLimit): string
    {
        $value = (int)$memoryLimit;
        if (str_contains($memoryLimit, 'G')) {
            return 'success';
        }
        if (str_contains($memoryLimit, 'M')) {
            return $value >= 256 ? 'success' : 'warning';
        }
        return 'danger';
    }

    /**
     * Получение рекомендации по лимиту памяти
     * @param string $memoryLimit
     * @return string
     */
    private function getMemoryLimitRecommendation(string $memoryLimit): string
    {
        $value = (int)$memoryLimit;
        if (str_contains($memoryLimit, 'G')) {
            return 'Отличный лимит памяти';
        }
        if (str_contains($memoryLimit, 'M')) {
            return $value >= 256 ? 'Достаточно памяти' : 'Рекомендуется увеличить до 256M';
        }
        return 'Критически мало памяти, увеличьте лимит';
    }

    /**
     * Парсинг информации о памяти Linux
     * @param string $memInfo
     * @return array
     */
    private function parseMemInfo(string $memInfo): array
    {
        $resources = [];
        preg_match('/MemTotal:\s+(\d+)/', $memInfo, $totalMatches);
        preg_match('/MemFree:\s+(\d+)/', $memInfo, $freeMatches);
        preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $availableMatches);

        if (isset($totalMatches[1])) {
            $totalMem = $totalMatches[1] * 1024;
            $availableMem = isset($availableMatches[1]) ? $availableMatches[1] * 1024 : 0;

            $resources['total_memory'] = [
                'value' => $this->formatBytes($totalMem),
                'status' => 'info',
                'description' => 'Общая память системы',
                'recommendation' => 'Объём физической памяти сервера'
            ];

            if ($availableMem > 0) {
                $memoryUsagePercent = round(($totalMem - $availableMem) / $totalMem * 100, 2);

                $resources['available_memory'] = [
                    'value' => $this->formatBytes($availableMem),
                    'status' => ($availableMem > $totalMem * 0.2) ? 'success' : 'warning',
                    'description' => 'Доступная память',
                    'recommendation' => ($availableMem > $totalMem * 0.2) ?
                        'Достаточно свободной памяти' :
                        'Рекомендуется освободить память'
                ];

                $resources['memory_usage_percent'] = [
                    'value' => $memoryUsagePercent . '%',
                    'status' => $memoryUsagePercent < 80 ? 'success' : 'warning',
                    'description' => 'Использование памяти',
                    'recommendation' => $memoryUsagePercent < 80 ?
                        'Нормальное использование' :
                        'Высокая нагрузка на память'
                ];
            }
        }

        return $resources;
    }

    /**
     * Парсинг информации о CPU
     * @param string $cpuInfo
     * @return array
     */
    private function parseCpuInfo(string $cpuInfo): array
    {
        $resources = [];
        preg_match_all('/model name\s+:\s+(.+)/', $cpuInfo, $matches);

        if (isset($matches[1][0])) {
            $resources['cpu_model'] = [
                'value' => $matches[1][0],
                'status' => 'info',
                'description' => 'Модель процессора',
                'recommendation' => 'Информация о CPU сервера'
            ];

            $cores = count($matches[1]);
            $resources['cpu_cores'] = [
                'value' => $cores,
                'status' => ($cores >= 4) ? 'success' : 'warning',
                'description' => 'Количество ядер CPU',
                'recommendation' => ($cores >= 4) ?
                    'Достаточно ядер для обработки запросов' :
                    'Рекомендуется увеличить количество ядер'
            ];
        }

        return $resources;
    }

    /**
     * Получение информации о средней нагрузке
     * @return array
     */
    private function getLoadAverageInfo(): array
    {
        $load = sys_getloadavg();
        $cores = $this->getCpuCores();

        return [
            'load_average' => [
                'value' => implode(' / ', array_map(function($val) {
                    return number_format($val, 2);
                }, $load)),
                'status' => ($load[0] < $cores) ? 'success' : 'warning',
                'description' => 'Средняя нагрузка (1/5/15 мин)',
                'recommendation' => ($load[0] < $cores) ?
                    'Нормальная нагрузка на систему' :
                    'Высокая нагрузка, требуется оптимизация'
            ]
        ];
    }

    /**
     * Получение количества ядер CPU
     * @return int
     */
    private function getCpuCores(): int
    {
        if ($this->isLinux && $this->hasSystemAccess) {
            $cpuInfo = $this->safeReadFile('/proc/cpuinfo');
            if ($cpuInfo !== null) {
                preg_match_all('/^processor/m', $cpuInfo, $matches);
                return count($matches[0]);
            }
        }

        // Альтернативный метод определения количества ядер
        if (function_exists('shell_exec') && function_exists('trim')) {
            try {
                $cores = shell_exec('nproc');
                if ($cores !== null) {
                    return (int)trim($cores);
                }
            } catch (\Throwable $e) {
                error_log("Ошибка при получении количества ядер CPU: " . $e->getMessage());
            }
        }

        return 1;
    }

    /**
     * Оценка размера в мегабайтах
     * @param string $size
     * @param int $minMB
     * @return string
     */
    private function evaluateSize(string $size, int $minMB): string
    {
        $value = (int)$size;
        if (str_contains($size, 'G')) {
            return 'success';
        }
        if (str_contains($size, 'M')) {
            return $value >= $minMB ? 'success' : 'warning';
        }
        return 'warning';
    }

    /**
     * Получение статуса OPcache
     * @return string
     */
    private function getOpcacheStatus(): string
    {
        if (!extension_loaded('Zend OPcache')) {
            return 'Не установлен';
        }
        return ini_get('opcache.enable') ? 'Включен' : 'Установлен, но выключен';
    }

    /**
     * Получение уровня отчетов об ошибках
     * @return string
     */
    private function getErrorReportingLevel(): string
    {
        $level = error_reporting();
        $levels = [];

        if (($level & E_ALL) === E_ALL) {
            $levels[] = 'E_ALL';
        } else {
            if ($level & E_ERROR) $levels[] = 'E_ERROR';
            if ($level & E_WARNING) $levels[] = 'E_WARNING';
            if ($level & E_PARSE) $levels[] = 'E_PARSE';
            if ($level & E_NOTICE) $levels[] = 'E_NOTICE';
        }

        return implode(' | ', $levels);
    }

    /**
     * Получение всей информации о системе
     * @return array
     */
    public function getInfo(): array
    {
        return $this->systemInfo;
    }

    /**
     * Получение конкретной секции информации
     * @param string $section
     * @return array|null
     */
    public function getSection(string $section): ?array
    {
        return $this->systemInfo[$section] ?? null;
    }
}