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