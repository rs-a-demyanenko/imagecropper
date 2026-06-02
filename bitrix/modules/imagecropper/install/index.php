<?php
/**
 * ImageCropper Module for Bitrix
 * Adds custom property type for image cropping in admin panel
 */

class CImageCropperModule extends CModule
{
    public $MODULE_ID = 'imagecropper';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_GROUP_RIGHTS = 'Y';
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = GetMessage('IMAGECROPPER_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('IMAGECROPPER_MODULE_DESC');
        $this->PARTNER_NAME = GetMessage('IMAGECROPPER_PARTNER_NAME');
        $this->PARTNER_URI = GetMessage('IMAGECROPPER_PARTNER_URI');
    }

    public function DoInstall()
    {
        global $APPLICATION;
        $this->InstallFiles();
        $this->InstallDB();
        $this->RegisterPropertyType();
        $APPLICATION->SetTitle(GetMessage('IMAGECROPPER_INSTALL_TITLE'));
        return true;
    }

    public function DoUninstall()
    {
        global $APPLICATION;
        $this->UnRegisterPropertyType();
        $this->UnInstallFiles();
        $this->UnInstallDB();
        $APPLICATION->SetTitle(GetMessage('IMAGECROPPER_UNINSTALL_TITLE'));
        return true;
    }

    public function InstallFiles($arParams = [])
    {
        CopyDirFiles(__DIR__ . '/js', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/imagecropper', true, true);
        CopyDirFiles(__DIR__ . '/css', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/css/imagecropper', true, true);
        return true;
    }

    public function UnInstallFiles($arParams = [])
    {
        DeleteDirFilesEx('/bitrix/js/imagecropper/');
        DeleteDirFilesEx('/bitrix/css/imagecropper/');
        return true;
    }

    public function InstallDB($arParams = [])
    {
        // No database tables needed - data stored as JSON in property values
        return true;
    }

    public function UnInstallDB($arParams = [])
    {
        // No cleanup needed - IB properties will be removed automatically with module
        return true;
    }

    public function RegisterPropertyType()
    {
        \Bitrix\Main\UserTypeManager::getInstance()->register(
            'imagecropper',
            'CImageCropperPropertyType'
        );
        return true;
    }

    public function UnRegisterPropertyType()
    {
        \Bitrix\Main\UserTypeManager::getInstance()->unregister('imagecropper');
        return true;
    }
}
