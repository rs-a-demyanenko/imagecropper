# ImageCropper MODX - Документация проекта

## Общая информация

**Название:** ImageCropper  
**Версия:** 1.3.3-pl  
**Автор:** Sterc (Oene Tjeerd de Bruin)  
**CMS:** MODX Revolution 2.4+  
**Тип:** Модуль для административной панели (НЕ фронтенд-компонент)  
**Описание:** Добавляет кастомный тип инпута для полей свойств ресурсов, позволяющий обрезать изображения прямо в админке

## Важное замечание

Этот модуль работает **ТОЛЬКО в административной панели**. Он не выводит изображения на фронтенде.

**Результат работы модуля:** JSON-массив с данными об изображении и его обрезанных версиях:

```json
{
    "image": "/path/to/original.jpg",
    "sizes": {
        "desktop": {
            "image": "/path/to/cropped-desktop.jpg",
            "width": 1000,
            "height": 500
        },
        "mobile": {
            "image": "/path/to/cropped-mobile.jpg",
            "width": 768,
            "height": 500
        }
    }
}
```

Этот JSON сохраняется в поле ресурса/элемента инфоблока и может быть использован разработчиком для вывода на сайте.

**Превью:** Создаются только для визуального контроля в интерфейсе админки, не являются основным функционалом.

## Структура проекта

```
/workspace
├── core/components/imagecropper/       # Основной код компонента
│   ├── docs/                           # Документация
│   │   ├── readme.txt
│   │   ├── license.txt
│   │   └── changelog.txt
│   ├── lexicon/                        # Языковые файлы
│   │   ├── en/                         # Английский
│   │   ├── nl/                         # Голландский
│   │   └── ru/                         # Русский
│   ├── model/imagecropper/             # Модель данных и классы
│   │   ├── imagecropper.class.php              # Базовый класс
│   │   ├── imagecropperplugins.class.php       # Класс плагинов
│   │   ├── imagecroppersnippets.class.php      # Класс сниппетов
│   │   ├── imagecroppermigrate.class.php       # Миграция
│   │   └── snippets/
│   │       └── imagecroppersnippet.class.php   # Сниппет ImageCropper
│   ├── processors/mgr/                 # Обработчики менеджера
│   │   └── crop.class.php              # Обработка обрезки
│   ├── elements/                       # Элементы MODX
│   │   ├── plugins/
│   │   │   └── imagecropper.plugin.php
│   │   ├── snippets/
│   │   │   └── imagecropper.snippet.php
│   │   ├── tvs/
│   │   │   ├── input/                  # Input render TV
│   │   │   │   ├── imagecropper.class.php
│   │   │   │   └── imagecropper.tpl
│   │   │   └── options/                # Input options TV
│   │   │       ├── imagecropper.php
│   │   │       └── imagecropper.tpl
│   │   └── chunks/                     # Чанки
│   │       ├── image.chunk.tpl         # Шаблон изображения (MODX tags)
│   │       └── imagefenom.chunk.tpl    # Шаблон изображения (Fenom tags)
│   └── templates/                      # Шаблоны
│       └── tvs/input/imagecropper.tpl
├── assets/components/imagecropper/     # Клиентские файлы
│   ├── libs/cropperjs/                 # Библиотека Cropper.js
│   │   ├── cropper.js
│   │   ├── cropper.min.js
│   │   ├── cropper.css
│   │   └── cropper.min.css
│   ├── js/mgr/                         # JavaScript менеджера
│   │   └── imagecropper.js             # Основной JS (895 строк)
│   ├── css/mgr/                        # CSS менеджера
│   │   └── imagecropper.css
│   ├── migration/                      # Скрипт миграции
│   │   └── imagecropper.migrate.php
│   └── connector.php                   # Коннектор для AJAX
└── _build/                             # Сборка пакета
    ├── config.json                     # Конфигурация сборки
    ├── build.config.php
    ├── setup.options.php
    └── resolvers/
        ├── setupoptions.resolver.php
        └── stercextra.resolver.php
```

## Основные компоненты

### 1. Плагин ImageCropper (регистрация типа инпута)

**Файл:** `core/components/imagecropper/elements/plugins/imagecropper.plugin.php`

**Назначение:** Регистрация кастомного типа инпута для TV полей в админке MODX

**События MODX:**
- `OnManagerPageInit` - подключение JS/CSS файлов в админке
- `OnTVInputRenderList` - регистрация класса рендера инпута
- `OnTVInputPropertiesList` - регистрация класса настроек инпута
- `OnClientSettingsRegisterSettings` - интеграция с настройками ClientSettings

**Класс обработчиков:** `ImageCropperPlugins`

### 2. TV Input Render (кастомный тип поля)

**Файл:** `core/components/imagecropper/elements/tvs/input/imagecropper.class.php`

**Класс:** `ImageCropperInputRender extends modTemplateVarInputRender`

**Назначение:** Рендеринг кастомного поля ввода для TV в админке

**Параметры:**
- `sizes` - JSON конфигурация размеров обрезки
- `previews` - показывать превью (boolean, по умолчанию true)

**Конфигурация sizes:**
```json
{
    "desktop": {"name": "Desktop", "size": "1200x500"},
    "mobile": {"name": "Mobile", "size": "768x320"}
}
```

Или с пропорциями:
```json
{
    "desktop": {"name": "Desktop", "proportion": "3x1"},
    "mobile": {"name": "Mobile", "proportion": "3x1"}
}
```

**Методы:**
- `process($value, $params)` - подготовка данных для шаблона
- `getTemplate()` - возвращает путь к tpl шаблону

### 3. Процессор обрезки изображений

**Файл:** `core/components/imagecropper/processors/mgr/crop.class.php`

**Класс:** `ImageCropperCropProcessor extends modProcessor`

**Назначение:** Обработка AJAX запроса на обрезку изображения

**Входные параметры:**
- `image` - путь к исходному изображению
- `cropWidth`, `cropHeight` - размеры области обрезки
- `x`, `y` - координаты верхнего левого угла обрезки
- `canvasWidth`, `canvasHeight` - размеры итогового канваса
- `scaleX`, `scaleY` - масштабирование (-1 для flip по горизонтали/вертикали)
- `imageWidth`, `imageHeight` - размеры оригинала
- `source` - ID медиа источника MODX
- `resource` - ID ресурса (для плейсхолдеров в пути)

**Логика работы:**
1. Загружает изображение из медиа источника
2. Удаляет префиксы URL (site_url, base_url источника)
3. Применяет flip (горизонтальный/вертикальный) если scaleX/scaleY = -1
4. Создает обрезанное изображение с помощью PHP GD
5. Сохраняет в формате WebP с качеством 100%
6. Поддерживает плейсхолдеры в пути сохранения:
   - `[[+year]]` - год (YYYY)
   - `[[+month]]` - месяц (MM)
   - `[[+day]]` - день (DD)
   - `[[+user]]` - ID пользователя
   - `[[+username]]` - имя пользователя
   - `[[+resource]]` - ID ресурса

**Результат:** JSON ответ с URL обрезанного изображения и размерами

### 4. JavaScript интерфейс (ExtJS)

**Файл:** `assets/components/imagecropper/js/mgr/imagecropper.js` (~900 строк)

**Назначение:** UI для работы с обрезкой изображений в админке MODX

**Основные компоненты ExtJS:**

1. **ImageCropper.combo.Browser** (TwinTriggerField)
   - Два триггера: выбор файла + открытие кроппера
   - Подключение MODX browser для выбора изображения
   - Отображение превью всех размеров
   - Хранение значения в формате JSON

2. **ImageCropper.window.CropImage** (Модальное окно)
   - Левая панель: выбор размера/пропорции, превью, кнопки действий
   - Центральная панель: изображение с Cropper.js
   - Правая панель: точные параметры (x, y, width, height, proportion)
   - Кнопки: flip horizontal/vertical, reset, save, auto-save all sizes

**Функционал:**
- Выбор изображения через стандартный MODX media browser
- Автоматическое открытие кроппера после выбора (настраивается)
- Визуальная обрезка с помощью Cropper.js
- Превью всех заданных размеров обрезки
- Редактирование параметров обрезки вручную
- Flip изображения (горизонтально/вертикально)
- Сохранение нескольких размеров для одного изображения
- Валидация и предупреждение о несохранённых изменениях
- AJAX отправка данных на процессор обрезки

### 5. Система настроек

**Файл:** `_build/config.json`

**Системные настройки MODX:**
- `auto_open_cropper` (boolean, default: 1) - автоматически открывать кроппер после выбора изображения
- `crop_path` (string, default: "assets/crops/") - путь для сохранения обрезанных изображений
- `migration_hash` (string) - хэш для скрипта миграции

## Формат хранения данных

TV поле хранит JSON следующего формата:
```json
{
    "image": "/path/to/original/image.jpg",
    "sizes": {
        "desktop": {
            "image": "/path/to/cropped/desktop.jpg",
            "width": 1200,
            "height": 500
        },
        "mobile": {
            "image": "/path/to/cropped/mobile.jpg",
            "width": 768,
            "height": 320
        }
    }
}
```

**Важно:** В Bitrix этот JSON будет сохраняться в свойство элемента инфоблока типа "текст" или "JSON".

## Зависимости

### Клиентские:
- **Cropper.js v1.x** - библиотека для обрезки изображений (можно использовать как есть)
- **ExtJS 3.x** - фреймворк админ-интерфейса MODX (в Bitrix не используется)

### Серверные:
- **PHP GD** - для обработки изображений (imagecreatefromstring, imagecopyresampled, imagewebp)
- **MODX Revolution 2.4+** (в Bitrix не требуется)

## Локализация

Поддерживаемые языки:
- English (en)
- Dutch (nl)  
- Russian (ru)

## Changelog (ключевые изменения)

### 1.3.3
- Исправлена работа с удаленными медиа источниками

### 1.3.2
- Исправление пропорций
- Исправление дублирования сниппета
- Добавлен flip изображения (горизонтальный/вертикальный)

### 1.3.1
- Интеграция с ClientSettings
- Исправления багов

### 1.3.0
- Улучшения UI
- Сохранение пропорций и позиций X/Y

### 1.1.0
- Добавлен скрипт миграции

### 1.0.0
- Первый релиз

## Ключевые файлы для переноса на Bitrix

### 1. Обязательные к переносу:

**Бизнес-логика:**
- `imagecropper.class.php` - базовая конфигурация (адаптировать под Bitrix)
- `crop.class.php` - процессор обрезки изображений (основная логика на PHP GD)

**Frontend:**
- `cropperjs/*` - библиотека Cropper.js (использовать без изменений)
- `imagecropper.js` - требует ПОЛНОЙ переделки под Bitrix JS API / jQuery
- `imagecropper.css` - стили требуют адаптации под Bitrix admin theme

**Настройки:**
- Конфигурация размеров (JSON format) - переносится как есть

### 2. НЕ требуются в Bitrix:

- Плагины MODX событий (OnManagerPageInit, OnTVInputRenderList и т.д.)
- TV Input Render/Input Options классы
- Сниппет ImageCropper (это был фронтенд вывод, а нам нужен только админский модуль)
- Чанки шаблонов
- Коннекторы MODX
- Сборка _build
- Миграционные скрипты MODX

## Архитектурные отличия MODX vs Bitrix

| Компонент | MODX | Bitrix |
|-----------|------|--------|
| Тип расширения | Package с TV input render | Модуль с кастомным свойством |
| Хранение данных | TV field (JSON) | Свойство элемента ИБ (тип "текст"/"JSON") |
| UI в админке | ExtJS + custom input | Bitrix Admin JS + jQuery |
| AJAX обработчик | MODX Processor | Bitrix Component Action / ajax.php |
| Медиа источник | modMediaSource | Bitrix File API / CFile |
| События | Plugin events | Event handlers (AddEventHandler) |
