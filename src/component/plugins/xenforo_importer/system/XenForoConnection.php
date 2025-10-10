<?php

namespace Ofey\Logan22\component\plugins\xenforo_importer\system;

use Exception;
use PDO;
use PDOException;

/**
 * Класс для работы с подключением к базе данных XenForo
 */
class XenForoConnection
{
    private ?PDO $connection = null;
    private array $config = [];

    /**
     * Конструктор
     * @param array $config Конфигурация подключения (host, database, username, password, port, prefix)
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Установить соединение с базой данных XenForo
     * @return bool
     * @throws Exception
     */
    public function connect(): bool
    {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4",
                $this->config['host'],
                $this->config['port'] ?? 3306,
                $this->config['database']
            );

            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            return true;
        } catch (PDOException $e) {
            throw new Exception("Ошибка подключения к XenForo БД: " . $e->getMessage());
        }
    }

    /**
     * Проверить подключение
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            if ($this->connection === null) {
                $this->connect();
            }
            $this->connection->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Получить объект PDO соединения
     * @return PDO|null
     */
    public function getConnection(): ?PDO
    {
        return $this->connection;
    }

    /**
     * Получить префикс таблиц
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->config['prefix'] ?? 'xf_';
    }

    /**
     * Закрыть соединение
     */
    public function close(): void
    {
        $this->connection = null;
    }

    /**
     * Получить имя таблицы с префиксом
     * @param string $table
     * @return string
     */
    public function getTableName(string $table): string
    {
        return $this->getPrefix() . $table;
    }
}
