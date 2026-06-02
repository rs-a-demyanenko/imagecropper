# План переноса ImageCropper с MODX на Bitrix

## Обзор задачи

Перенос **административного модуля** обрезки изображений ImageCropper из CMS MODX Revolution в CMS 1C-Bitrix.

**Важное уточнение:** Исходный модуль работает ТОЛЬКО в админке и не является фронтенд-компонентом. Он добавляет кастомный тип поля для редактирования свойств элементов.

**Цель:** Создать аналогичный функционал для Bitrix:
- Добавление кастомного типа свойства для инфоблоков с интерфейсом обрезки изображений
- Обрезка изображений через административную панель Bitrix
- Хранение JSON с данными об оригинале и обрезанных версиях в свойстве элемента ИБ
- Превью размеров только для визуального контроля в админке

**НЕ требуется:**
- Фронтенд-компонент для вывода изображений (это задача разработчика сайта)
- Шаблоны вывода, srcset и т.д.

---

## Этапы переноса

### Этап 1: Анализ и проектирование архитектуры Bitrix

#### 1.1 Соответствие концепций MODX → Bitrix

| MODX | Bitrix | Примечание |
|------|--------|------------|
| TV Fields + Input Render | Тип свойства ИБ "UserType" | Создаем кастомный тип свойства |
| TV Value (JSON) | Свойство элемента ИБ (тип текст/JSON) | Хранение JSON в свойстве |
| Plugins (OnTVInputRenderList) | class.php UserType | Регистрация типа свойства |
| Processors (crop.class.php) | ajax.php / component action | AJAX обработчик для обрезки |
| Lexicon | Language files | lang/*.php |
| Manager JS/CSS | Admin JS/CSS | /bitrix/js/imagecropper/ |

#### 1.2 Структура модуля для Bitrix

**Вариант: Модуль с кастомным типом свойства**

```
bitrix/
├── modules/
│   └── imagecropper/
│       ├── install/
│       │   ├── index.php           # Установка модуля
│       │   └── uninstall.php       # Удаление
│       ├── lib/
│       │   ├── cropper.php         # Класс обработки изображений (PHP GD)
│       │   ├── storage.php         # Работа с JSON данными
│       │   ├── helper.php          # Вспомогательные функции
│       │   └── propertytype.php    # Кастомный тип свойства (UserType)
│       ├── admin/
│       │   ├── property_type/      # UI для типа свойства
│       │   │   ├── editor.php      # Редактор свойства в элементе ИБ
│       │   │   ├── settings.php    # Настройки типа свойства
│       │   │   └── ajax.php        # AJAX обработчик обрезки
│       │   └── menu.php            # Меню модуля (опционально)
│       ├── js/
│       │   └── imagecropper/
│       │       ├── cropperjs/      # Cropper.js библиотека
│       │       │   ├── cropper.js
│       │       │   └── cropper.css
│       │       └── admin/
│       │           ├── editor.js   # JS редактора свойства
│       │           └── editor.css  # Стили редактора
│       ├── include.php             # Подключение модуля
│       ├── options.php             # Страница настроек модуля
│       └── lang/
│           └── ru/
│               ├── install/
│               ├── lib/
│               └── admin/
```

**Альтернативный вариант: Компонент + хелперы**

Если не создавать полноценный модуль, можно использовать компонент с хелперами:

```
bitrix/
├── components/
│   └── imagecropper/
│       ├── editor/                 # Компонент редактора (для админки)
│       │   ├── class.php
│       │   ├── ajax.php
│       │   └── templates/
│       │       └── .default/
│       ├── lib/                    # Библиотека классов
│       │   ├── cropper.php
│       │   └── storage.php
│       └── lang/
├── local/js/imagecropper/          # Frontend файлы
│   ├── cropperjs/
│   └── editor/
└── local/php_interface/            # Init file
    └── init.php                    # Подключение библиотеки
```

**Рекомендуемый подход:** Создать полноценный модуль (`modules/imagecropper/`) с кастомным типом свойства, так как это:
- Позволит интегрироваться непосредственно в форму редактирования элемента ИБ
- Даст возможность управлять настройками через админку
- Обеспечит правильную установку/удаление
- Будет соответствовать архитектуре Bitrix

---

### Этап 2: Реализация ядра модуля

#### 2.1 Класс обработки изображений (`lib/cropper.php`)

**Задачи:**
- Загрузка исходного изображения из Bitrix File API
- Обрезка по координатам (x, y, width, height)
- Поддержка flip (горизонтальный/вертикальный)
- Сохранение в WebP/PNG/JPG
- Генерация уникальных имен файлов (md5 хэш параметров)

**Методы:**
```php
class ImageCropperCore {
    private $sourceImage;
    private $imageWidth;
    private $imageHeight;
    private $imageType;
    
    public function __construct($options = [])
    public function load($filePath)         // Загрузка через CFile или прямой путь
    public function crop($x, $y, $width, $height, $canvasWidth, $canvasHeight)
    public function flip($horizontal = false, $vertical = false)
    public function save($destinationPath, $quality = 100, $format = 'webp')
    public function getImageWidth()
    public function getImageHeight()
    public function destroy()
}
```

**Особенности Bitrix:**
- Использовать `CFile::GetFilePath()` для получения пути к файлу
- Использовать `CFile::SaveFile()` для сохранения через Bitrix API
- Учитывать работу с cloud storage (S3 и т.д.)

#### 2.2 Класс хранения данных (`lib/storage.php`)

**Задачи:**
- Работа с JSON структурой данных
- Сохранение в свойство инфоблока
- Получение данных для вывода
- Валидация структуры

**Формат хранения:**
```json
{
    "image": "/upload/iblock/abc/original.jpg",
    "sizes": {
        "desktop": {
            "image": "/upload/imagecropper/2024/01/desktop-abc123.webp",
            "width": 1200,
            "height": 500
        },
        "mobile": {
            "image": "/upload/imagecropper/2024/01/mobile-abc123.webp",
            "width": 768,
            "height": 320
        }
    }
}
```

**Методы:**
```php
class ImageCropperStorage {
    public function __construct($propertyValue)  // JSON строка из свойства
    public function load()                       // Парсинг JSON
    public function save($elementId, $propertyCode)  // Сохранение в свойство ИБ
    public function getSize($sizeKey)            // Получить конкретный размер
    public function getAllSizes()                // Все размеры
    public function getOriginalImage()           // Путь к оригиналу
    public function addSize($key, $data)         // Добавить размер
    public function removeSize($key)             // Удалить размер
}
```

#### 2.3 Класс типа свойства (`lib/propertytype.php`)

**Назначение:** Регистрация кастомного типа свойства для инфоблоков

**Методы UserType API:**
```php
class ImageCropperPropertyType {
    // Отображение поля в форме редактирования элемента
    public function GetPropertyEditorHtml($arProperty, $value)
    
    // Настройки типа свойства при создании
    public function GetSettingsHTML($arProperty, $htmlControlName)
    
    // Подготовка данных перед сохранением
    public function PrepareData($arProperty, $value)
    
    // Возвращаемое значение для шаблонов
    public function GetUserValue($ID, $arProperty)
}
```

---

### Этап 3: AJAX обработчик и UI редактора

#### 3.1 AJAX обработчик (`admin/property_type/ajax.php`)

**Actions:**
- `crop` - обрезка изображения
- `delete_size` - удаление размера из JSON
- `get_preview` - получение превью для размера

**Параметры action=crop:**
```php
[
    'action' => 'crop',
    'sessid' => bitrix_sessid(),
    'image' => '/upload/iblock/abc/original.jpg',
    'size_key' => 'desktop',
    'crop_width' => 1200,
    'crop_height' => 500,
    'x' => 100,
    'y' => 50,
    'canvas_width' => 1200,
    'canvas_height' => 500,
    'scale_x' => 1,      // -1 для flip horizontal
    'scale_y' => 1,      // -1 для flip vertical
    'image_width' => 1920,
    'image_height' => 1080
]
```

**Ответ:**
```json
{
    "success": true,
    "data": {
        "image": "/upload/imagecropper/2024/01/desktop-abc123.webp",
        "width": 1200,
        "height": 500
    }
}
```

#### 3.2 JavaScript редактор (`js/admin/editor.js`)

**Назначение:** UI для работы с обрезкой изображений в форме элемента ИБ

**Основные компоненты:**

1. **ImageCropperField** - основной класс поля свойства
   - Отображение текущего значения JSON
   - Кнопка выбора изображения (BX.FileInput)
   - Кнопки для каждого размера (открытие кроппера)
   - Превью всех размеров

2. **ImageCropperModal** - модальное окно обрезки
   - Интеграция с Cropper.js
   - Выбор размера из конфигурации
   - Панель параметров (x, y, width, height, proportion)
   - Кнопки flip, reset, save

**Интеграция с Cropper.js:**
```javascript
class ImageCropperEditor {
    constructor(container, options) {
        this.container = container;
        this.sizes = options.sizes || {};  // Конфигурация размеров
        this.value = options.value || {};  // Текущее JSON значение
        this.cropper = null;
        this.propertyName = options.name;
    }
    
    // Инициализация поля
    init() {
        // Рендеринг UI поля
        // Подключение превью
        // Обработчики кнопок
    }
    
    // Открытие модалки с кроппером
    openCropModal(sizeKey) {
        // Показать BX.Dialog с Cropper.js
    }
    
    // Сохранение результата обрезки
    saveCrop(sizeKey, cropData) {
        // AJAX запрос к admin/property_type/ajax.php
        // Обновление this.value
        // Обновление превью
    }
    
    // Обновление отображения поля
    updateField() {
        // Сериализация JSON в hidden input
        // Обновление превью
    }
}
```

#### 3.3 Модальное окно обрезки

**Структура (аналог MODX):**
```html
<div class="imagecropper-modal">
    <div class="imagecropper-sidebar">
        <!-- Выбор размера -->
        <select class="size-selector">
            <option value="desktop">Desktop (1200x500)</option>
            <option value="mobile">Mobile (768x320)</option>
        </select>
        
        <!-- Превью -->
        <div class="previews">
            <!-- Динамически заполняется -->
        </div>
        
        <!-- Кнопки действий -->
        <button class="save-btn">Сохранить</button>
        <button class="reset-btn">Сбросить</button>
        <button class="flip-h-btn"><i class="icon-flip-h"></i></button>
        <button class="flip-v-btn"><i class="icon-flip-v"></i></button>
    </div>
    
    <div class="imagecropper-canvas">
        <img id="crop-image" src="">
    </div>
    
    <div class="imagecropper-details">
        <input type="number" id="crop-x" placeholder="X">
        <input type="number" id="crop-y" placeholder="Y">
        <input type="number" id="crop-width" placeholder="Width">
        <input type="number" id="crop-height" placeholder="Height">
    </div>
</div>
```

**Особенности Bitrix:**
- Использовать `BX.PopupWindow` или `BX.Dialog` для модалки
- Использовать `BX.FileInput` для выбора изображения
- Интеграция с админской темой Bitrix

---

### Этап 4: Конфигурация и настройки модуля

#### 4.1 Установка модуля (`install/index.php`)

```php
class imagecropper extends CModule {
    var $MODULE_ID = 'imagecropper';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    
    function __construct() {
        $arModuleVersion = [];
        include(__DIR__ . '/version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = 'ImageCropper';
        $this->MODULE_DESCRIPTION = 'Модуль обрезки изображений в административной панели';
        
        $this->PARTNER_NAME = 'Your Company';
        $this->PARTNER_URI = 'https://yourcompany.ru';
    }
    
    function DoInstall() {
        global $APPLICATION;
        
        // Регистрация типа свойства инфоблока
        $this->registerPropertyType();
        
        // Создание папки для обрезанных изображений
        $cropPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/imagecropper/';
        if (!file_exists($cropPath)) {
            mkdir($cropPath, 0755, true);
        }
        
        // Сохранение настроек по умолчанию
        COption::SetOptionString('imagecropper', 'crop_path', '/upload/imagecropper/');
        COption::SetOptionString('imagecropper', 'auto_open', 'N');
        COption::SetOptionString('imagecropper', 'quality', '100');
        
        return true;
    }
    
    function DoUninstall() {
        global $APPLICATION;
        
        // Удаление типа свойства
        $this->unregisterPropertyType();
        
        return true;
    }
    
    function registerPropertyType() {
        // Регистрация через IBlockPropertyEnum
        // или через AddEventHandler на OnIBlockPropertyTypeList
    }
}
```

#### 4.2 Страница настроек модуля (`options.php`)

**Настройки:**
- `crop_path` - путь для сохранения обрезанных изображений (default: `/upload/imagecropper/`)
- `auto_open` - автооткрытие кроппера после выбора изображения (default: N)
- `quality` - качество WebP сжатия (default: 100)
- `use_placeholders` - использовать плейсхолдеры в пути (default: Y)

#### 4.3 Плейсхолдеры в путях

Поддерживаемые плейсхолдеры для `crop_path`:
- `{year}` → 2024
- `{month}` → 01-12
- `{day}` → 01-31
- `{user_id}` → ID текущего пользователя
- `{user_login}` → Логин пользователя
- `{element_id}` → ID элемента ИБ
- `{iblock_id}` → ID инфоблока

Пример: `/upload/imagecropper/{year}/{month}/`

---

### Этап 5: Тестирование и документация

#### 5.1 Тесты

**Unit тесты:**
- Тест класса ImageCropperCore (обрезка, flip, save)
- Тест класса ImageCropperStorage (JSON валидация, сохранение)
- Тест класса ImageCropperPropertyType (рендеринг поля)

**Integration тесты:**
- Полный цикл: выбор изображения → обрезка → сохранение → получение данных
- Тест с разными форматами (JPG, PNG, WebP)
- Тест с прозрачностью (PNG + alpha channel)
- Тест работы с cloud storage

#### 5.2 Документация

**Файлы:**
- `README.md` - общая информация, установка
- `USAGE.md` - руководство пользователя (как создавать размеры, обрезать изображения)
- `API.md` - документация для разработчиков (классы, методы)
- `CHANGELOG.md` - история изменений

#### 5.3 Пример использования

**Создание свойства типа ImageCropper:**

1. В админке: Инфоблоки → Типы свойств → Добавить свойство
2. Выбрать тип "ImageCropper"
3. В настройках указать JSON конфигурации размеров:
```json
{
    "desktop": {"name": "Desktop", "size": "1200x500"},
    "mobile": {"name": "Mobile", "size": "768x320"},
    "thumbnail": {"name": "Thumbnail", "size": "300x300"}
}
```

**Использование в шаблоне компонента:**

```php
// Получение JSON из свойства
$propertyValue = $arResult['PROPERTIES']['IMAGE_CROPPER']['VALUE'];
$cropData = json_decode($propertyValue, true);

// Вывод оригинала
if (!empty($cropData['image'])) {
    echo '<img src="' . $cropData['image'] . '" alt="">';
}

// Вывод конкретного размера
if (!empty($cropData['sizes']['desktop']['image'])) {
    echo '<img src="' . $cropData['sizes']['desktop']['image'] . '" alt="">';
}

// Srcset для responsive
$srcset = [];
$sizes = [];
foreach ($cropData['sizes'] as $key => $size) {
    if (!empty($size['image'])) {
        $srcset[] = $size['image'] . ' ' . $size['width'] . 'w';
        $sizes[] = '(' . $key . ') ' . $size['width'] . 'px';
    }
}
echo '<img src="' . $cropData['image'] . '" srcset="' . implode(', ', $srcset) . '" alt="">';
```

---

## Детальный план работ по файлам

### Неделя 1: Ядро модуля

| День | Задача | Файлы |
|------|--------|-------|
| 1 | Создание структуры директорий | Все директории |
| 2-3 | Класс ImageCropperCore | `lib/cropper.php` |
| 4-5 | Класс ImageCropperStorage | `lib/storage.php` |

### Неделя 2: Компонент

| День | Задача | Файлы |
|------|--------|-------|
| 1-2 | Главный файл компонента | `components/class.php` |
| 3 | AJAX обработчик | `components/ajax.php` |
| 4 | Шаблон по умолчанию | `templates/.default/template.php` |
| 5 | Языковые файлы | `lang/*/class.php` |

### Неделя 3: Административный интерфейс

| День | Задача | Файлы |
|------|--------|-------|
| 1-2 | Интеграция в форму элемента | `admin/imagecropper/` |
| 3-4 | JavaScript редактор | `js/admin/editor.js` |
| 5 | Стили админки | `js/admin/editor.css` |

### Неделя 4: Финализация

| День | Задача | Файлы |
|------|--------|-------|
| 1-2 | Тестирование и багфиксы | Все файлы |
| 3 | Документация | README.md, USAGE.md |
| 4-5 | Установка в тестовый Bitrix | install/ |

---

## Технические требования

### Серверные:
- PHP 7.4+
- Bitrix D7
- GD Library или Imagick
- JSON extension

### Клиентские:
- Cropper.js 1.5.x
- jQuery 3.x (Bitrix стандарт)
- CSS Flexbox/Grid поддержка

### Совместимость:
- Bitrix Managed Portal 19+
- Bitrix Business Portal 19+
- Малое предприятие 19+

---

## Риски и решения

| Риск | Решение |
|------|---------|
| Несовместимость Cropper.js с Bitrix JS | Использовать изолированный namespace |
| Проблемы с правами доступа к файлам | Использовать Bitrix File API |
| Конфликты с другими модулями | Префиксовать все классы и функции |
| Производительность при большом числе изображений | Кэширование результатов, lazy loading |

---

## Критерии приемки

- [ ] Обрезка изображений работает корректно
- [ ] Поддержка множественных размеров
- [ ] Интеграция в карточку элемента ИБ
- [ ] Компонент вывода с поддержкой шаблонов
- [ ] Адаптивный frontend (srcset)
- [ ] Документация на русском языке
- [ ] Установка через маркетплейс Bitrix

---

## Приложения

### A. Пример вызова компонента

```php
$APPLICATION->IncludeComponent(
    'imagecropper:main',
    '',
    [
        'IMAGE_DATA' => $arResult['PROPERTIES']['IMAGE_CROPPER']['VALUE'],
        'SIZE' => 'desktop',
        'TEMPLATE' => '@FILE/site/templates/components/imagecropper/image.tpl',
        'ATTRIBUTES' => 'class,alt,title',
        'class' => 'my-image',
        'alt' => 'Описание изображения',
        'title' => 'Заголовок'
    ],
    false
);
```

### B. Пример шаблона

```php
<?php if (!empty($arResult['IMAGE'])): ?>
    <img 
        src="<?= $arResult['IMAGE'] ?>"
        srcset="<?= $arResult['MOBILE_IMAGE'] ?> 768w, <?= $arResult['DESKTOP_IMAGE'] ?> 958w"
        sizes="(max-width: 767px) 768px, 958px"
        alt="<?= $arResult['ALT'] ?>"
        <?= !empty($arResult['CLASS']) ? 'class="' . $arResult['CLASS'] . '"' : '' ?>
    />
<?php endif; ?>
```

### C. JSON структура для тестирования

```json
{
    "image": "/upload/iblock/a1b/test-image.jpg",
    "sizes": {
        "desktop": {
            "name": "Desktop",
            "size": "1200x500",
            "image": "/upload/imagecropper/2024/01/desktop-abc123.webp",
            "width": 1200,
            "height": 500
        },
        "mobile": {
            "name": "Mobile",
            "size": "768x320",
            "image": "/upload/imagecropper/2024/01/mobile-abc123.webp",
            "width": 768,
            "height": 320
        }
    }
}
```


---

## Реализация (статус на текущий момент)

Модуль **полностью реализован** и готов к установке. Все файлы находятся в `/workspace/bitrix/modules/imagecropper/`.

### Статус готовности: ✅ 100%

- [x] Класс модуля `CImageCropperModule` (install/index.php)
- [x] Тип свойства `CImageCropperPropertyType` (lib/propertytype/class.php)  
- [x] Хелпер `CImageCropperHelper` (lib/helper.php)
- [x] JS редактор `ImageCropperEditor` (install/js/imagecropper/admin.js)
- [x] Интеграция Cropper.js v1.5.13
- [x] CSS стили для админки
- [x] Локализация (RU/EN)
- [x] AJAX обработчик (ajax.php)
- [x] Документация (README.md)

### Краткое описание файлов:

| Файл | Назначение | Строк кода |
|------|------------|------------|
| install/index.php | Установка/удаление модуля, регистрация типа свойства | ~86 |
| lib/propertytype/class.php | Тип свойства ИБ, рендер форм | ~306 |
| lib/helper.php | Вспомогательные методы для работы с данными | ~192 |
| install/js/imagecropper/admin.js | JS-класс редактора ImageCropperEditor | ~307 |
| install/js/imagecropper/cropper.js | Библиотека Cropper.js v1.5.13 | ~9 (минифицировано) |
| install/css/imagecropper/style.css | Стили админского интерфейса | ~149 |
| ajax.php | AJAX endpoint для загрузки изображений | ~88 |
| include.php | Автозагрузка классов модуля | ~15 |
| lang/ru/install/index.php | Русская локализация | ~28 |
| lang/en/install/index.php | Английская локализация | ~28 |

### Как использовать:

1. **Установка:** Скопировать папку `imagecropper` в `/bitrix/modules/` и установить через админку Bitrix
2. **Создание свойства:** В настройках ИБ создать свойство типа "Обрезка изображений (Image Cropper)"
3. **Настройка:** Указать размеры обрезки, качество, размеры превью
4. **Использование:** При редактировании элемента ИБ загрузить изображение и обрезать его
5. **Получение данных:** Использовать `CImageCropperHelper` или распарсить JSON свойства

Подробная документация в файле `/workspace/bitrix/modules/imagecropper/README.md`

