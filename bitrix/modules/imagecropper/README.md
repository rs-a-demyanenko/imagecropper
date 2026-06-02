# ImageCropper Module for Bitrix

## Описание

Модуль **ImageCropper** добавляет кастомный тип свойства инфоблоков для обрезки изображений прямо в административной панели Bitrix.

## Структура модуля

```
bitrix/modules/imagecropper/
├── install/
│   ├── index.php              # Класс установки модуля
│   ├── version.php            # Версия модуля
│   ├── js/imagecropper/
│   │   ├── cropper.js         # Библиотека Cropper.js
│   │   └── admin.js           # JS-код редактора обрезки
│   ├── css/imagecropper/
│   │   ├── cropper.css        # Стили Cropper.js
│   │   └── style.css          # Пользовательские стили
│   └── components/            # (опционально) компоненты
├── lib/
│   ├── propertytype/
│   │   └── class.php          # Класс типа свойства
│   └── helper.php             # Вспомогательные функции
├── lang/
│   ├── ru/install/index.php   # Языковые константы (RU)
│   └── en/install/index.php   # Языковые константы (EN)
├── ajax.php                   # AJAX обработчик для загрузки
└── include.php                # Автозагрузка классов
```

## Установка

1. Скопируйте папку `imagecropper` в `/bitrix/modules/`
2. В административной панели перейдите: **Настройки → Настройки продукта → Модули**
3. Найдите модуль **Image Cropper** и нажмите **Установить**

## Использование

### Создание свойства с обрезкой изображений

1. Перейдите в настройки вашего инфоблока
2. Создайте новое свойство или отредактируйте существующее
3. В поле **Тип свойства** выберите **Обрезка изображений (Image Cropper)**
4. Настройте параметры:
   - **Ширина обрезки** - целевая ширина изображения (px)
   - **Высота обрезки** - целевая высота изображения (px)
   - **Качество JPEG** - качество сжатия (%)
   - **Ширина превью** - ширина миниатюры для админки (px)
   - **Высота превью** - высота миниатюры для админки (px)
   - **Разрешить изменение размера** - показать кнопки отражения
   - **Разрешить поворот** - показать кнопки поворота

### Работа с элементом инфоблока

При редактировании элемента инфоблока:

1. Нажмите кнопку **Выбрать изображение**
2. Загрузите изображение
3. Обрежьте его с помощью интерфейса Cropper.js
4. Используйте кнопки управления:
   - ↶ / ↷ - поворот влево/вправо
   - ↔ / ↕ - отражение горизонтально/вертикально
   - ⟲ - сбросить обрезку
   - ✕ - удалить изображение
5. Сохраните элемент

### Получение данных в коде

```php
// Подключите модуль
\Bitrix\Main\Loader::includeModule('imagecropper');

// Получите элемент инфоблока
$arElement = CIBlockElement::GetByID($elementId)->Fetch();
$props = CIBlockElement::GetProperty($iblockId, $elementId);

while ($prop = $props->Fetch()) {
    if ($prop['CODE'] === 'YOUR_PROPERTY_CODE') {
        // Раскодируйте JSON
        $imageData = json_decode($prop['VALUE'], true);
        
        // Доступные поля:
        // $imageData['url'] - URL обрезанного изображения
        // $imageData['thumb_url'] - URL превью
        // $imageData['width'] - ширина обрезанного изображения
        // $imageData['height'] - высота обрезанного изображения
        // $imageData['crop'] - данные обрезки (x, y, width, height, rotate, scaleX, scaleY)
        // $imageData['original_width'] - ширина оригинала
        // $imageData['original_height'] - высота оригинала
    }
}
```

### Использование хелпера

```php
use CImageCropperHelper;

// Получить URL обрезанного изображения
$croppedUrl = CImageCropperHelper::getCroppedUrl($propertyValue);

// Получить URL превью
$thumbUrl = CImageCropperHelper::getThumbUrl($propertyValue);

// Получить размеры
$dimensions = CImageCropperHelper::getDimensions($propertyValue);
// ['width' => 800, 'height' => 600, 'thumb_width' => 150, 'thumb_height' => 150]

// Получить данные обрезки
$cropData = CImageCropperHelper::getCropData($propertyValue);
// ['x' => 100, 'y' => 50, 'width' => 800, 'height' => 600, 'rotate' => 0, ...]

// Сохранить обрезанное изображение в постоянное место
CImageCropperHelper::saveCroppedImage($imageData, '/path/to/save/image.jpg');

// Удалить связанные изображения
CImageCropperHelper::deleteImages($propertyValue);
```

## Формат данных

Модуль хранит данные в формате JSON:

```json
{
    "url": "/upload/imagecropper/ic_5f8a9b2c3d4e5.jpg",
    "thumb_url": "/upload/imagecropper/ic_5f8a9b2c3d4e5_thumb.jpg",
    "width": 800,
    "height": 600,
    "thumb_width": 150,
    "thumb_height": 150,
    "crop": {
        "x": 100,
        "y": 50,
        "width": 800,
        "height": 600,
        "rotate": 0,
        "scaleX": 1,
        "scaleY": 1
    },
    "original_width": 1920,
    "original_height": 1080,
    "timestamp": 1704110400000
}
```

## Важные замечания

1. **Только для админки**: Модуль работает исключительно в административной панели. 
   Для вывода на фронтенде используйте стандартные методы получения свойств ИБ.

2. **Превью**: Миниатюры используются только для отображения в списке элементов админки.

3. **Хранение**: Данные хранятся как JSON-строка в значении свойства ИБ. 
   Изображения сохраняются в `/upload/imagecropper/`.

4. **Зависимости**: Используется библиотека [Cropper.js](https://github.com/fengyuanchen/cropperjs) (MIT License).

## Удаление

1. В административной панели: **Настройки → Настройки продукта → Модули**
2. Найдите **Image Cropper** и нажмите **Удалить**
3. Все свойства этого типа будут удалены из инфоблоков

## Требования

- Bitrix Framework 19+
- PHP 7.1+
- JavaScript ES6+

## Лицензия

MIT License
