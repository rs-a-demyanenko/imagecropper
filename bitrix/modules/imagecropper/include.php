<?php
/**
 * ImageCropper Module Autoloader
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(
    'imagecropper',
    [
        'CImageCropperPropertyType' => __DIR__ . '/lib/propertytype/class.php',
        'CImageCropperHelper' => __DIR__ . '/lib/helper.php',
    ]
);
