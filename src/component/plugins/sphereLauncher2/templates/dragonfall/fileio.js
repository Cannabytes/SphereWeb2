/**
 * FileIO - Библиотека для работы с файлами
 * Простой и удобный API для чтения, записи и управления файлами
 * 
 * Примеры использования:
 * 
 * // Чтение файла
 * const content = await FileIO.read('config.json');
 * 
 * // Запись с обработкой плейсхолдеров
 * await FileIO.write('log.txt', 'Запуск {now} от {username}', true);
 * 
 * // Добавление в конец файла
 * await FileIO.append('log.txt', '{now}: Событие произошло\n');
 * 
 * // Проверка существования
 * if (await FileIO.exists('config.json')) { ... }
 * 
 * // Удаление файла
 * await FileIO.delete('temp.txt');
 * 
 * // Получить список плейсхолдеров
 * const placeholders = await FileIO.getPlaceholders();
 */

class FileIOManager {
    constructor() {
        this.ready = false;
        this.initPromise = this.init();
    }

    /**
     * Инициализация библиотеки
     * @private
     */
    async init() {
        try {
            // Проверяем, что Go функции доступны
            if (typeof window.go === 'undefined' || typeof window.go.main === 'undefined' || typeof window.go.main.App === 'undefined') {
                console.warn('FileIO: Wails API еще не инициализировано, ожидаем...');
                
                // Ждем инициализации Wails
                await new Promise((resolve) => {
                    const checkInterval = setInterval(() => {
                        if (typeof window.go !== 'undefined' && 
                            typeof window.go.main !== 'undefined' && 
                            typeof window.go.main.App !== 'undefined') {
                            clearInterval(checkInterval);
                            resolve();
                        }
                    }, 100);
                });
            }

            this.ready = true;
            console.log('FileIO: Библиотека инициализирована');
        } catch (error) {
            console.error('FileIO: Ошибка инициализации:', error);
            throw error;
        }
    }

    /**
     * Ожидание готовности библиотеки
     * @private
     */
    async ensureReady() {
        if (!this.ready) {
            await this.initPromise;
        }
    }

    /**
     * Читает содержимое файла
     * @param {string} path - Путь к файлу (относительный или абсолютный)
     * @returns {Promise<string>} Содержимое файла
     * @throws {Error} Если произошла ошибка чтения
     */
    async read(path) {
        await this.ensureReady();

        try {
            const result = await window.go.main.App.ReadFile(path);
            
            if (!result.success) {
                throw new Error(result.error || 'Неизвестная ошибка чтения файла');
            }

            return result.data;
        } catch (error) {
            console.error('FileIO.read: Ошибка:', error);
            throw error;
        }
    }

    /**
     * Записывает содержимое в файл
     * @param {string} path - Путь к файлу (относительный или абсолютный)
     * @param {string} content - Содержимое для записи
     * @param {boolean} processPlaceholders - Обрабатывать ли плейсхолдеры (по умолчанию false)
     * @returns {Promise<string>} Сообщение об успехе
     * @throws {Error} Если произошла ошибка записи
     */
    async write(path, content, processPlaceholders = false) {
        await this.ensureReady();

        try {
            const result = await window.go.main.App.WriteFile(path, content, processPlaceholders);
            
            if (!result.success) {
                throw new Error(result.error || 'Неизвестная ошибка записи файла');
            }

            return result.data;
        } catch (error) {
            console.error('FileIO.write: Ошибка:', error);
            throw error;
        }
    }

    /**
     * Добавляет содержимое в конец файла
     * @param {string} path - Путь к файлу (относительный или абсолютный)
     * @param {string} content - Содержимое для добавления
     * @param {boolean} processPlaceholders - Обрабатывать ли плейсхолдеры (по умолчанию false)
     * @returns {Promise<string>} Сообщение об успехе
     * @throws {Error} Если произошла ошибка
     */
    async append(path, content, processPlaceholders = false) {
        await this.ensureReady();

        try {
            const result = await window.go.main.App.AppendFile(path, content, processPlaceholders);
            
            if (!result.success) {
                throw new Error(result.error || 'Неизвестная ошибка добавления в файл');
            }

            return result.data;
        } catch (error) {
            console.error('FileIO.append: Ошибка:', error);
            throw error;
        }
    }

    /**
     * Проверяет, существует ли файл
     * @param {string} path - Путь к файлу (относительный или абсолютный)
     * @returns {Promise<boolean>} true если файл существует, false иначе
     */
    async exists(path) {
        await this.ensureReady();

        try {
            const result = await window.go.main.App.FileExists(path);
            
            if (!result.success) {
                return false;
            }

            return result.data === 'true';
        } catch (error) {
            console.error('FileIO.exists: Ошибка:', error);
            return false;
        }
    }

    /**
     * Удаляет файл
     * @param {string} path - Путь к файлу (относительный или абсолютный)
     * @returns {Promise<string>} Сообщение об успехе
     * @throws {Error} Если произошла ошибка удаления
     */
    async delete(path) {
        await this.ensureReady();

        try {
            const result = await window.go.main.App.DeleteFile(path);
            
            if (!result.success) {
                throw new Error(result.error || 'Неизвестная ошибка удаления файла');
            }

            return result.data;
        } catch (error) {
            console.error('FileIO.delete: Ошибка:', error);
            throw error;
        }
    }

    /**
     * Получает список доступных плейсхолдеров с примерами
     * @returns {Promise<Object>} Объект с плейсхолдерами и их описаниями
     */
    async getPlaceholders() {
        await this.ensureReady();

        try {
            const placeholders = await window.go.main.App.GetAvailablePlaceholders();
            return placeholders;
        } catch (error) {
            console.error('FileIO.getPlaceholders: Ошибка:', error);
            throw error;
        }
    }

    /**
     * Читает JSON файл и возвращает объект
     * @param {string} path - Путь к JSON файлу
     * @returns {Promise<Object>} Распарсенный объект JSON
     * @throws {Error} Если произошла ошибка чтения или парсинга
     */
    async readJSON(path) {
        const content = await this.read(path);
        try {
            return JSON.parse(content);
        } catch (error) {
            throw new Error(`Ошибка парсинга JSON из ${path}: ${error.message}`);
        }
    }

    /**
     * Записывает объект в JSON файл
     * @param {string} path - Путь к файлу
     * @param {Object} data - Объект для записи
     * @param {boolean} pretty - Форматировать ли JSON (по умолчанию true)
     * @returns {Promise<string>} Сообщение об успехе
     * @throws {Error} Если произошла ошибка
     */
    async writeJSON(path, data, pretty = true) {
        const content = pretty ? JSON.stringify(data, null, 2) : JSON.stringify(data);
        return await this.write(path, content, false);
    }

    /**
     * Читает файл построчно
     * @param {string} path - Путь к файлу
     * @returns {Promise<string[]>} Массив строк
     */
    async readLines(path) {
        const content = await this.read(path);
        return content.split('\n');
    }

    /**
     * Записывает массив строк в файл
     * @param {string} path - Путь к файлу
     * @param {string[]} lines - Массив строк
     * @param {boolean} processPlaceholders - Обрабатывать ли плейсхолдеры
     * @returns {Promise<string>} Сообщение об успехе
     */
    async writeLines(path, lines, processPlaceholders = false) {
        const content = lines.join('\n');
        return await this.write(path, content, processPlaceholders);
    }

    /**
     * Создает лог с временной меткой
     * @param {string} path - Путь к лог-файлу
     * @param {string} message - Сообщение для логирования
     * @param {string} level - Уровень лога (INFO, ERROR, WARNING, DEBUG)
     * @returns {Promise<string>} Сообщение об успехе
     */
    async log(path, message, level = 'INFO') {
        const logEntry = `[{now}] [${level}] ${message}{newline}`;
        return await this.append(path, logEntry, true);
    }

    /**
     * Копирует файл
     * @param {string} sourcePath - Путь к исходному файлу
     * @param {string} destPath - Путь к файлу назначения
     * @returns {Promise<string>} Сообщение об успехе
     */
    async copy(sourcePath, destPath) {
        const content = await this.read(sourcePath);
        return await this.write(destPath, content, false);
    }

    /**
     * Создает резервную копию файла с временной меткой
     * @param {string} path - Путь к файлу
     * @returns {Promise<string>} Путь к созданной резервной копии
     */
    async backup(path) {
        const content = await this.read(path);
        const backupPath = `${path}.backup.{timestamp}`;
        
        // Сначала обрабатываем плейсхолдер в имени файла
        const timestamp = Date.now();
        const actualBackupPath = backupPath.replace('{timestamp}', timestamp);
        
        await this.write(actualBackupPath, content, false);
        return actualBackupPath;
    }
}

// Создаем глобальный экземпляр для удобства использования
const FileIO = new FileIOManager();

// Делаем доступным глобально
if (typeof window !== 'undefined') {
    window.FileIO = FileIO;
}

// Экспорт для модульных систем
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FileIO;
}

