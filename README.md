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

Опции:
- `--installed` - показать только установленные дополнения
- `--search="поиск"` - поиск по названию или описанию
- `--format=json` - вывод в формате JSON
- `--interactive` - интерактивный режим установки

### Установка дополнения

```bash
php artisan extras:install vendor/package-name
```

Опции:
- `--version=1.0.0` - указать версию для установки
- `--force` - принудительная установка (даже если уже установлено)

### Удаление дополнения

```bash
php artisan extras:remove vendor/package-name
```

Опции:
- `--force` - удалить без подтверждения

### Обновление дополнений

```bash
# Обновить конкретное дополнение
php artisan extras:update vendor/package-name

# Обновить все дополнения
php artisan extras:update
```

Опции:
- `--version=1.0.0` - указать версию для обновления
- `--force` - принудительное обновление
- `--check-only` - только проверить доступные обновления

### Массовые операции

#### Массовая установка
```bash
# Установить несколько дополнений
php artisan extras:batch:install package1 package2 package3

# Установить из файла (по одному на строку)
php artisan extras:batch:install --file=packages.txt

# Предварительный просмотр без установки
php artisan extras:batch:install package1 package2 --dry-run
```

Опции:
- `--file=filename` - файл со списком пакетов
- `--force` - пропустить подтверждения
- `--continue-on-error` - продолжить при ошибках
- `--dry-run` - предварительный просмотр
- `--parallel=N` - количество параллельных установок

#### Массовое обновление
```bash
# Обновить конкретные дополнения
php artisan extras:batch:update package1 package2

# Обновить все установленные дополнения
php artisan extras:batch:update

# Проверить доступные обновления
php artisan extras:batch:update --check-only
```

Опции:
- `--file=filename` - файл со списком пакетов
- `--force` - пропустить подтверждения
- `--continue-on-error` - продолжить при ошибках
- `--dry-run` - предварительный просмотр
- `--check-only` - только проверить обновления
- `--parallel=N` - количество параллельных обновлений

#### Массовое удаление
```bash
# Удалить несколько дополнений
php artisan extras:batch:remove package1 package2

# Удалить все установленные дополнения
php artisan extras:batch:remove --all

# Удалить из файла
php artisan extras:batch:remove --file=packages.txt
```

Опции:
- `--file=filename` - файл со списком пакетов
- `--force` - пропустить подтверждения
- `--continue-on-error` - продолжить при ошибках
- `--dry-run` - предварительный просмотр
- `--all` - удалить все установленные дополнения
- `--keep-deps` - сохранить зависимости

### Информация о дополнении

```bash
# Основная информация
php artisan extras:info package-name

# Подробная информация
php artisan extras:info package-name --verbose

# С зависимостями
php artisan extras:info package-name --dependencies

# История релизов
php artisan extras:info package-name --releases

# В формате JSON
php artisan extras:info package-name --format=json

# В формате YAML
php artisan extras:info package-name --format=yaml
```

Опции:
- `--verbose, -v` - показать подробную информацию
- `--dependencies, -d` - показать зависимости
- `--releases, -r` - показать историю релизов
- `--format, -f` - формат вывода (table, json, yaml)

### Управление кэшем

```bash
# Очистить кэш
php artisan extras:cache --clear

# Показать статус кэша
php artisan extras:cache --status

# Обновить кэш
php artisan extras:cache --refresh

# Статистика кэша
php artisan extras:cache --stats
```

Опции:
- `--clear, -c` - очистить весь кэш
- `--status, -s` - показать статус кэша
- `--refresh, -r` - обновить кэш (очистить и перестроить)
- `--stats` - показать статистику кэша