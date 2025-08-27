# EvolutionCMS Extras

Консольный менеджер дополнений для EvolutionCMS. Позволяет устанавливать, удалять и обновлять дополнения через командную строку.

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

## Использование

### Список доступных дополнений

```bash
php artisan extras:list
```

Опции:
- `--installed` - показать только установленные дополнения
- `--search="поиск"` - поиск по названию или описанию
- `--format=json` - вывод в формате JSON

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