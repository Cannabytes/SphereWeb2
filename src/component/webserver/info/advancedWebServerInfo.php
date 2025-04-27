<?php

namespace Ofey\Logan22\component\webserver\info;

use Ofey\Logan22\component\lang\lang;

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
                'title' => lang::get_phrase('main_php_parameters'),
                'items' => $this->getMainParameters()
            ],
            'security' => [
                'title' => lang::get_phrase('security_parameters'),
                'items' => $this->getSecurityParameters()
            ],
            'extensions' => [
                'title' => lang::get_phrase('critical_php_extensions'),
                'items' => $this->getExtensionsInfo()
            ],
            'uploads' => [
                'title' => lang::get_phrase('file_upload_settings'),
                'items' => $this->getUploadParameters()
            ],
            'sessions' => [
                'title' => lang::get_phrase('session_settings'),
                'items' => $this->getSessionParameters()
            ],
            'system' => [
                'title' => lang::get_phrase('system_information'),
                'items' => $this->getSystemParameters()
            ],
            'system_resources' => [
                'title' => lang::get_phrase('system_resources'),
                'items' => $this->getSystemResources()
            ],
            'php_advanced' => [
                'title' => lang::get_phrase('advanced_php_settings'),
                'items' => $this->getAdvancedPHPParameters()
            ],
            'network' => [
                'title' => lang::get_phrase('network_settings'),
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
                'status' => version_compare(PHP_VERSION, '8.2.0', '>=') ? 'success' : 'danger',
                'description' => lang::get_phrase('php_version'),
                'recommendation' => version_compare(PHP_VERSION, '8.2.0', '>=') ?
                    lang::get_phrase('php_version_is_up_to_date')
                    :
                    lang::get_phrase('php_version_update_recommended')

            ],
            'php_sapi' => [
                'value' => php_sapi_name(),
                'status' => 'info',
                'description' => lang::get_phrase('php_interface'),
                'recommendation' => lang::get_phrase('informational_parameter')
            ],
            'memory_limit' => [
                'value' => ini_get('memory_limit'),
                'status' => $this->evaluateMemoryLimit(ini_get('memory_limit')),
                'description' => lang::get_phrase('php_memory_limit'),
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
                'value' => ini_get('expose_php') ? 'ON' : 'OFF',
                'status' => ini_get('expose_php') ? 'warning' : 'success',
                'description' => lang::get_phrase('php_version_display'),
                'recommendation' => ini_get('expose_php') ?
                    lang::get_phrase('recommended_to_disable_for_security') :
                    lang::get_phrase('properly_configured')
            ],
            'display_errors' => [
                'value' => ini_get('display_errors') ? 'ON' : 'OFF',
                'status' => ini_get('display_errors') ? 'warning' : 'success',
                'description' => lang::get_phrase('error_display'),
                'recommendation' => ini_get('display_errors') ?
                    lang::get_phrase('disable_on_production_server') :
                    lang::get_phrase('properly_configured')

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
            'mysql' => ['mysqli', 'MySQL (mysqli)', lang::get_phrase('required_for_working_with_mysql')],
            'pdo' => ['pdo', 'PDO', lang::get_phrase('required_for_secure_database_operation')],
            'gd' => ['gd', 'GD Library', lang::get_phrase('required_for_working_with_images')],
            'curl' => ['curl', 'cURL', lang::get_phrase('required_for_external_api_requests')],
            'openssl' => ['openssl', 'OpenSSL', lang::get_phrase('critical_for_security')]
        ];

        $result = [];
        foreach ($extensions as $key => [$ext, $name, $recommendation]) {
            $loaded = extension_loaded($ext);
            $version = $loaded ? phpversion($ext) : null;

            $result[$key] = [
                'value' => $loaded ? ($version ? lang::get_phrase('installed_t', $version) : lang::get_phrase('installed')) : lang::get_phrase('not_installed'),
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
                'description' => lang::get_phrase('maximum_file_size'),
                'recommendation' => lang::get_phrase('at_least_10mb_is_recommended')
            ],
            'post_max_size' => [
                'value' => ini_get('post_max_size'),
                'status' => $this->evaluateSize(ini_get('post_max_size'), 10),
                'description' => lang::get_phrase('maximum_post_size'),
                'recommendation' => lang::get_phrase('must_be_greater_than_upload_max_filesize')
            ],
            'max_file_uploads' => [
                'value' => ini_get('max_file_uploads'),
                'status' => (intval(ini_get('max_file_uploads')) >= 20) ? 'success' : 'warning',
                'description' => lang::get_phrase('maximum_number_of_files'),
                'recommendation' => lang::get_phrase('20_or_more_is_recommended')
            ],
            'allow_url_fopen' => [
                'value' => ini_get('allow_url_fopen') ? 'ON' : 'OFF',
                'status' => ini_get('allow_url_fopen') ? 'success' : 'warning',
                'description' => lang::get_phrase('url_wrappers_permission'),
                'recommendation' => lang::get_phrase('it_is_recommended_to_enable')
            ],
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
                'description' => lang::get_phrase('session_lifetime'),
                'recommendation' => lang::get_phrase('1440_seconds_or_more_is_recommended')
            ],
            'session.cookie_secure' => [
                'value' => ini_get('session.cookie_secure') ? 'Yes' : 'No',
                'status' => ini_get('session.cookie_secure') ? 'success' : 'warning',
                'description' => lang::get_phrase('secure_cookies'),
                'recommendation' => !ini_get('session.cookie_secure') ? lang::get_phrase('enable_for_https') : 'OK'
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
                'description' => lang::get_phrase('operating_system'),
                'recommendation' => lang::get_phrase('informational_parameter')
            ],
            'server_software' => [
                'value' => $_SERVER['SERVER_SOFTWARE'] ?? lang::get_phrase('unknown'),
                'status' => 'info',
                'description' => lang::get_phrase('web_server'),
                'recommendation' => lang::get_phrase('recommended_apache_or_nginx_latest_versions')
            ],
            'architecture' => [
                'value' => php_uname('m'),
                'status' => 'info',
                'description' => lang::get_phrase('system_architecture'),
                'recommendation' => lang::get_phrase('informational_parameter')
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
                'description' => lang::get_phrase('php_memory_usage'),
                'recommendation' => lang::get_phrase('current_memory_usage_by_script')
            ],
            'memory_peak' => [
                'value' => $this->formatBytes(memory_get_peak_usage(true)),
                'status' => 'info',
                'description' => lang::get_phrase('peak_memory_usage'),
                'recommendation' => lang::get_phrase('maximum_memory_usage_by_script')
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
            error_log(lang::get_phrase('error_reading_file') . " {$filepath}: " . $e->getMessage());
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
                    'description' => lang::get_phrase('memory_information'),
                    'recommendation' => lang::get_phrase('open_basedir_restriction_prevents_retrieving_information')
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
                    'description' => lang::get_phrase('processor_information'),
                    'recommendation' => lang::get_phrase('open_basedir_restriction_prevents_retrieving_information')
                ];
            }
        } else {
            $resources['system_access'] = [
                'value' => lang::get_phrase('limited'),
                'status' => 'warning',
                'description' => lang::get_phrase('access_to_system_information'),
                'recommendation' => lang::get_phrase('check_open_basedir_settings_for_access_to_proc')
            ];
        }

        // Добавление доступной системной информации
        if (function_exists('sys_getloadavg')) {
            try {
                $resources = array_merge($resources, $this->getLoadAverageInfo());
            } catch (\Throwable $e) {
                $resources['load_average'] = [
                    'value' => lang::get_phrase('error_retrieving_data'),
                    'status' => 'warning',
                    'description' => lang::get_phrase('average_load'),
                    'recommendation' => lang::get_phrase('check_permissions_and_system_limits')
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
                'recommendation' => lang::get_phrase('recommended_to_enable_for_performance_improvement')
            ],
            'max_input_vars' => [
                'value' => ini_get('max_input_vars'),
                'status' => (intval(ini_get('max_input_vars')) >= 3000) ? 'success' : 'warning',
                'description' => lang::get_phrase('maximum_input_variables'),
                'recommendation' => lang::get_phrase('3000_plus_is_recommended_for_cms')
            ],
            'max_execution_time' => [
                'value' => ini_get('max_execution_time') . ' сек.',
                'status' => (intval(ini_get('max_execution_time')) >= 30) ? 'success' : 'warning',
                'description' => lang::get_phrase('max_execution_time'),
                'recommendation' => lang::get_phrase('30_plus_seconds_is_recommended')
            ],
            'error_reporting' => [
                'value' => $this->getErrorReportingLevel(),
                'status' => 'info',
                'description' => lang::get_phrase('error_reporting_level'),
                'recommendation' => lang::get_phrase('e_all_is_recommended_for_development')
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
                'description' => lang::get_phrase('socket_timeout'),
                'recommendation' => lang::get_phrase('60_plus_seconds_is_recommended')
            ],
            'output_buffering' => [
                'value' => ini_get('output_buffering') ? 'ON' : 'OFF',
                'status' => ini_get('output_buffering') ? 'success' : 'warning',
                'description' => lang::get_phrase('output_buffering'),
                'recommendation' => lang::get_phrase('it_is_recommended_to_enable')
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
            return lang::get_phrase('great_memory_limit');
        }
        if (str_contains($memoryLimit, 'M')) {
            return $value >= 256 ? lang::get_phrase('sufficient_memory') : lang::get_phrase('it_is_recommended_to_increase_to_256m');
        }
        return lang::get_phrase('critical_memory_shortage_increase_limit');
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
                'description' => lang::get_phrase('total_system_memory'),
                'recommendation' => lang::get_phrase('server_physical_memory_size')
            ];

            if ($availableMem > 0) {
                $memoryUsagePercent = round(($totalMem - $availableMem) / $totalMem * 100, 2);

                $resources['available_memory'] = [
                    'value' => $this->formatBytes($availableMem),
                    'status' => ($availableMem > $totalMem * 0.2) ? 'success' : 'warning',
                    'description' => lang::get_phrase('available_memory'),
                    'recommendation' => ($availableMem > $totalMem * 0.2) ?
                        lang::get_phrase('sufficient_free_memory') :
                        lang::get_phrase('it_is_recommended_to_free_up_memory')
                ];

                $resources['memory_usage_percent'] = [
                    'value' => $memoryUsagePercent . '%',
                    'status' => $memoryUsagePercent < 80 ? 'success' : 'warning',
                    'description' => lang::get_phrase('memory_usage'),
                    'recommendation' => $memoryUsagePercent < 80 ?
                        lang::get_phrase('normal_usage') : lang::get_phrase('high_memory_usage')
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
                'description' => lang::get_phrase('processor_model'),
                'recommendation' => lang::get_phrase('server_cpu_information')
            ];

            $cores = count($matches[1]);
            $resources['cpu_cores'] = [
                'value' => $cores,
                'status' => ($cores >= 4) ? 'success' : 'warning',
                'description' => lang::get_phrase('number_of_cpu_cores'),
                'recommendation' => ($cores >= 4) ?
                    lang::get_phrase('sufficient_cores_for_processing_requests') :
                    lang::get_phrase('it_is_recommended_to_increase_the_number_of_cores')
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
                'description' => lang::get_phrase('average_load_1_5_15_min'),
                'recommendation' => ($load[0] < $cores) ?
                    lang::get_phrase('normal_system_load') :
                    lang::get_phrase('high_load_optimization_required')
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
                error_log(lang::get_phrase('error_retrieving_cpu_cores_count') . " " . $e->getMessage());
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
            return lang::get_phrase('not_installed');
        }
        return ini_get('opcache.enable') ? lang::get_phrase('enabled') : lang::get_phrase('installed_but_disabled');
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