# EvolutionCMS Extras

Консольный менеджер дополнений для EvolutionCMS. Позволяет устанавливать, удалять и обновлять дополнения через командную строку. Поддерживает загрузку дополнений с различных источников, включая GitHub репозитории.

## Установка

Добавьте пакет в `composer.json` вашего проекта EvolutionCMS:

```json
{
    "require": {
        "hkyss/extras": "~1.0.0"
    }
}
```

Затем выполните:

```bash
composer install
```

### Публикация конфигурации (опционально)

Если вы хотите кастомизировать настройки, опубликуйте конфигурационный файл:

```bash
php artisan vendor:publish --tag=extras-config
```

### Добавление дополнительных репозиториев

По умолчанию включен репозиторий [EvolutionCMS Extras](https://github.com/evolution-cms-extras) с популярными дополнениями:
- TinyMCE5 - редактор для EvolutionCMS 3
- DocLister - компонент для вывода документов  
- FormLister - обработчик форм
- multifields - кастомные TV поля
- evocms-resourceHistory - история ресурсов

Для добавления дополнительных GitHub репозиториев отредактируйте `config/extras.php`:

```php
'repositories' => [
    // Обязательный репозиторий EvolutionCMS Extras (уже включен)
    [
        'type' => 'github',
        'organization' => 'evolution-cms-extras',
        'name' => 'EvolutionCMS Extras'
    ],
    // Дополнительные репозитории
    [
        'type' => 'github',
        'organization' => 'your-org',
        'name' => 'Your Repository'
    ],
],
```

## Использование

### Список доступных дополнений

```bash
php artisan extras:list
```

**Опции:**
- `--installed, -i` - показать только установленные дополнения
- `--search="поиск"` - поиск по названию или описанию
- `--format=table|json` - формат вывода (по умолчанию: table)
- `--interactive` - интерактивный режим установки

### Установка дополнения

```bash
php artisan extras:install vendor/package-name
```

**Опции:**
- `--version=1.0.0` - указать версию для установки (по умолчанию: latest)
- `--force` - принудительная установка (даже если уже установлено)
- `--no-deps` - пропустить установку зависимостей

**Примеры:**
```bash
# Установка последней версии
php artisan extras:install evolution-cms/tinymce5

# Установка конкретной версии
php artisan extras:install evolution-cms/tinymce5 --version=5.7.1

# Принудительная переустановка
php artisan extras:install evolution-cms/tinymce5 --force
```

### Удаление дополнения

```bash
php artisan extras:remove vendor/package-name
```

**Опции:**
- `--force` - удалить без подтверждения
- `--keep-deps` - сохранить зависимости, если они не используются другими пакетами

### Обновление дополнений

```bash
# Обновить конкретное дополнение
php artisan extras:update vendor/package-name

# Обновить все дополнения
php artisan extras:update
```

**Опции:**
- `--version=1.0.0` - указать версию для обновления (по умолчанию: latest)
- `--force` - принудительное обновление
- `--check-only` - только проверить доступные обновления без установки

### Batch операции

#### Установка нескольких дополнений

```bash
# Установка из списка
php artisan extras:batch:install package1 package2 package3

# Установка из файла
php artisan extras:batch:install --file=packages.txt

# Принудительная установка без подтверждения
php artisan extras:batch:install package1 package2 --force

# Продолжить при ошибках
php artisan extras:batch:install package1 package2 --continue-on-error

# Тестовый запуск (dry run)
php artisan extras:batch:install package1 package2 --dry-run

# Параллельная установка
php artisan extras:batch:install package1 package2 --parallel=4
```

#### Обновление нескольких дополнений

```bash
# Обновить конкретные пакеты
php artisan extras:batch:update package1 package2

# Обновить все установленные пакеты
php artisan extras:batch:update

# Только проверить обновления
php artisan extras:batch:update --check-only

# Принудительное обновление
php artisan extras:batch:update --force
```

#### Удаление нескольких дополнений

```bash
# Удалить конкретные пакеты
php artisan extras:batch:remove package1 package2

# Удалить все установленные дополнения
php artisan extras:batch:remove --all

# Сохранить зависимости
php artisan extras:batch:remove package1 package2 --keep-deps
```

### Управление кешем

```bash
# Очистить кеш
php artisan extras:cache --clear

# Показать статус кеша
php artisan extras:cache --status

# Обновить кеш
php artisan extras:cache --refresh

# Показать статистику кеша
php artisan extras:cache --stats
```

### Информация о дополнении

```bash
# Подробная информация
php artisan extras:info vendor/package-name

# В формате JSON
php artisan extras:info vendor/package-name --format=json

# С зависимостями
php artisan extras:info vendor/package-name --dependencies

# С историей релизов
php artisan extras:info vendor/package-name --releases

# Подробный вывод
php artisan extras:info vendor/package-name --verbose
```

## Унифицированные опции

Все команды поддерживают следующие унифицированные опции:

### Общие опции
- `--version=VERSION` - указать версию
- `--force` - принудительное выполнение
- `--dry-run` - тестовый запуск без изменений
- `--verbose, -v` - подробный вывод
- `--quiet` - тихий режим

### Batch опции
- `--file=FILE` - файл со списком пакетов
- `--continue-on-error` - продолжить при ошибках
- `--parallel=N` - количество параллельных операций

### Специфичные опции
- `--search=QUERY` - поиск
- `--format=FORMAT` - формат вывода
- `--interactive` - интерактивный режим
- `--installed` - только установленные
- `--dependencies` - показать зависимости
- `--releases` - показать релизы
- `--check-only` - только проверка
- `--keep-deps` - сохранить зависимости
- `--no-deps` - пропустить зависимости

## Backward Compatibility

Для обеспечения совместимости со старыми версиями, все legacy опции продолжают работать:

### Legacy опции (устарели, но работают)
- `--install-version` → `--version`
- `--update-version` → `--version`
- `--install-force` → `--force`
- `--update-force` → `--force`
- `--remove-force` → `--force`
- `--batch-install-force` → `--force`
- `--batch-update-force` → `--force`
- `--batch-remove-force` → `--force`
- `--batch-install-dry-run` → `--dry-run`
- `--batch-update-dry-run` → `--dry-run`
- `--batch-remove-dry-run` → `--dry-run`
- `--install-file` → `--file`
- `--update-file` → `--file`
- `--remove-file` → `--file`
- `--list-format` → `--format`
- `--info-format` → `--format`

**Примечание:** Использование legacy опций вызовет предупреждение в логах. Рекомендуется перейти на новые унифицированные опции.

## Логирование

Все операции логируются с structured logging:

```php
// Примеры логов
extras.install_started: {"package": "vendor/package", "version": "1.0.0", "force": false}
extras.install_completed: {"package": "vendor/package", "version": "1.0.0"}
extras.legacy_option_used: {"legacy_option": "install-version", "modern_option": "version"}
```

## Валидация

Команды автоматически валидируют:
- Формат имени пакета (vendor/package)
- Формат версии (x.y.z или latest)
- Конфликтующие опции
- Наличие пакета в репозитории

## Обработка ошибок

Все команды используют единообразную обработку ошибок:
- Структурированное логирование
- Подробные сообщения об ошибках
- Stack trace в verbose режиме
- Graceful degradation при ошибках

## Примеры использования

### Типичный workflow

```bash
# Посмотреть доступные дополнения
php artisan extras:list

# Установить редактор
php artisan extras:install evolution-cms/tinymce5 --version=5.7.1

# Проверить установленные
php artisan extras:list --installed

# Обновить все дополнения
php artisan extras:batch:update --check-only

# Обновить с принудительным режимом
php artisan extras:batch:update --force

# Очистить кеш
php artisan extras:cache --clear
```

### Автоматизация

```bash
# Установка из файла без подтверждения
php artisan extras:batch:install --file=requirements.txt --force

# Обновление всех пакетов с продолжением при ошибках
php artisan extras:batch:update --continue-on-error

# Удаление всех дополнений
php artisan extras:batch:remove --all --force
```