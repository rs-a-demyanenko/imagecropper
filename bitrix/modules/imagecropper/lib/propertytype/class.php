<?php
/**
 * ImageCropper Property Type for Bitrix Infoblocks
 * Handles image cropping functionality in admin panel
 */

class CImageCropperPropertyType
{
    /**
     * Get property type description
     */
    public static function GetUserTypeDescription()
    {
        return [
            'PROPERTY_TYPE' => 'imagecropper',
            'CLASS_NAME' => 'CImageCropperPropertyType',
            'DESCRIPTION' => GetMessage('IMAGECROPPER_PROPERTY_TYPE_DESC'),
            'SETTINGS_RENDERER' => ['CImageCropperPropertyType', 'renderSettings'],
            'EDIT_FORM_RENDER' => ['CImageCropperPropertyType', 'renderEditForm'],
            'ADMIN_LIST_VIEW' => ['CImageCropperPropertyType', 'renderAdminListView'],
            'PUBLIC_VIEW' => ['CImageCropperPropertyType', 'renderPublicView'],
        ];
    }

    /**
     * Render settings form for property type
     */
    public static function renderSettings($arProperty, $strHTMLControlName)
    {
        $arDefaultSettings = [
            'width' => 800,
            'height' => 600,
            'quality' => 90,
            'thumb_width' => 150,
            'thumb_height' => 150,
            'allow_resize' => true,
            'allow_rotate' => true,
            'formats' => ['original', 'crop', 'thumb']
        ];

        $arSettings = array_merge($arDefaultSettings, $arProperty['USER_VALUE_SETTINGS']);

        ob_start();
        ?>
        <table class="adm-detail-content-table edit-table">
            <tr>
                <td width="40%">
                    <label for="<?=$strHTMLControlName['NAME']?>[width]"><?=GetMessage('IMAGECROPPER_SETTING_WIDTH')?></label>
                </td>
                <td>
                    <input type="text" name="<?=$strHTMLControlName['NAME']?>[width]" 
                           value="<?=htmlspecialcharsbx($arSettings['width'])?>" size="10"> px
                </td>
            </tr>
            <tr>
                <td>
                    <label for="<?=$strHTMLControlName['NAME']?>[height]"><?=GetMessage('IMAGECROPPER_SETTING_HEIGHT')?></label>
                </td>
                <td>
                    <input type="text" name="<?=$strHTMLControlName['NAME']?>[height]" 
                           value="<?=htmlspecialcharsbx($arSettings['height'])?>" size="10"> px
                </td>
            </tr>
            <tr>
                <td>
                    <label for="<?=$strHTMLControlName['NAME']?>[quality]"><?=GetMessage('IMAGECROPPER_SETTING_QUALITY')?></label>
                </td>
                <td>
                    <input type="text" name="<?=$strHTMLControlName['NAME']?>[quality]" 
                           value="<?=htmlspecialcharsbx($arSettings['quality'])?>" size="3"> %
                </td>
            </tr>
            <tr>
                <td>
                    <label for="<?=$strHTMLControlName['NAME']?>[thumb_width]"><?=GetMessage('IMAGECROPPER_SETTING_THUMB_WIDTH')?></label>
                </td>
                <td>
                    <input type="text" name="<?=$strHTMLControlName['NAME']?>[thumb_width]" 
                           value="<?=htmlspecialcharsbx($arSettings['thumb_width'])?>" size="10"> px
                </td>
            </tr>
            <tr>
                <td>
                    <label for="<?=$strHTMLControlName['NAME']?>[thumb_height]"><?=GetMessage('IMAGECROPPER_SETTING_THUMB_HEIGHT')?></label>
                </td>
                <td>
                    <input type="text" name="<?=$strHTMLControlName['NAME']?>[thumb_height]" 
                           value="<?=htmlspecialcharsbx($arSettings['thumb_height'])?>" size="10"> px
                </td>
            </tr>
            <tr>
                <td>
                    <label><?=GetMessage('IMAGECROPPER_SETTING_ALLOW_RESIZE')?></label>
                </td>
                <td>
                    <input type="checkbox" name="<?=$strHTMLControlName['NAME']?>[allow_resize]" 
                           value="Y" <?=($arSettings['allow_resize'] === 'Y' || $arSettings['allow_resize'] === true) ? 'checked' : ''?>>
                </td>
            </tr>
            <tr>
                <td>
                    <label><?=GetMessage('IMAGECROPPER_SETTING_ALLOW_ROTATE')?></label>
                </td>
                <td>
                    <input type="checkbox" name="<?=$strHTMLControlName['NAME']?>[allow_rotate]" 
                           value="Y" <?=($arSettings['allow_rotate'] === 'Y' || $arSettings['allow_rotate'] === true) ? 'checked' : ''?>>
                </td>
            </tr>
        </table>
        <?php
        return ob_get_clean();
    }

    /**
     * Render edit form in admin panel
     */
    public static function renderEditForm($arProperty, $value, $strHTMLControlName, $strHTMLControlID)
    {
        global $APPLICATION;
        
        // Load required CSS and JS
        $APPLICATION->SetAdditionalCSS('/bitrix/css/imagecropper/cropper.css');
        $APPLICATION->SetAdditionalCSS('/bitrix/css/imagecropper/style.css');
        $APPLICATION->AddHeadScript('/bitrix/js/imagecropper/cropper.js');
        $APPLICATION->AddHeadScript('/bitrix/js/imagecropper/admin.js');
        
        $arSettings = $arProperty['USER_VALUE_SETTINGS'];
        $arDefaultSettings = [
            'width' => 800,
            'height' => 600,
            'quality' => 90,
            'thumb_width' => 150,
            'thumb_height' => 150,
            'allow_resize' => true,
            'allow_rotate' => true,
            'formats' => ['original', 'crop', 'thumb']
        ];
        $arSettings = array_merge($arDefaultSettings, $arSettings);
        
        // Parse existing value
        $arValue = !empty($value) ? json_decode($value, true) : [];
        if (!is_array($arValue)) {
            $arValue = [];
        }
        
        $uniqueId = uniqid('ic_');
        $fileInputId = $strHTMLControlName['VALUE'] . '_file';
        $previewContainerId = $uniqueId . '_preview';
        $dataFieldName = $strHTMLControlName['VALUE'];
        
        ob_start();
        ?>
        <div class="imagecropper-container" id="<?=$uniqueId?>" 
             data-settings='<?=htmlspecialcharsbx(json_encode($arSettings))?>'>
            
            <!-- Hidden field to store JSON data -->
            <input type="hidden" name="<?=$dataFieldName?>" 
                   class="imagecropper-data" 
                   value="<?=htmlspecialcharsbx($value)?>">
            
            <!-- File input -->
            <div class="imagecropper-file-input">
                <input type="file" id="<?=$fileInputId?>" 
                       accept="image/*" 
                       class="imagecropper-file">
                <label for="<?=$fileInputId?>" class="imagecropper-file-label">
                    <?=GetMessage('IMAGECROPPER_SELECT_IMAGE')?>
                </label>
            </div>
            
            <!-- Preview container -->
            <div class="imagecropper-preview-container" id="<?=$previewContainerId?>" 
                 style="display: none;">
                
                <!-- Main image for cropping -->
                <div class="imagecropper-main-wrapper">
                    <img src="" class="imagecropper-main-image" alt="">
                </div>
                
                <!-- Thumbnail preview -->
                <div class="imagecropper-thumb-preview">
                    <div class="imagecropper-thumb-label"><?=GetMessage('IMAGECROPPER_PREVIEW')?></div>
                    <div class="imagecropper-thumb-image"></div>
                </div>
                
                <!-- Controls -->
                <div class="imagecropper-controls">
                    <?php if ($arSettings['allow_rotate']): ?>
                    <button type="button" class="imagecropper-btn imagecropper-rotate-left" 
                            title="<?=GetMessage('IMAGECROPPER_ROTATE_LEFT')?>">
                        ↶ <?=GetMessage('IMAGECROPPER_ROTATE_LEFT')?>
                    </button>
                    <button type="button" class="imagecropper-btn imagecropper-rotate-right" 
                            title="<?=GetMessage('IMAGECROPPER_ROTATE_RIGHT')?>">
                        ↷ <?=GetMessage('IMAGECROPPER_ROTATE_RIGHT')?>
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($arSettings['allow_resize']): ?>
                    <button type="button" class="imagecropper-btn imagecropper-flip-h" 
                            title="<?=GetMessage('IMAGECROPPER_FLIP_H')?>">
                        ↔ <?=GetMessage('IMAGECROPPER_FLIP_H')?>
                    </button>
                    <button type="button" class="imagecropper-btn imagecropper-flip-v" 
                            title="<?=GetMessage('IMAGECROPPER_FLIP_V')?>">
                        ↕ <?=GetMessage('IMAGECROPPER_FLIP_V')?>
                    </button>
                    <?php endif; ?>
                    
                    <button type="button" class="imagecropper-btn imagecropper-reset" 
                            title="<?=GetMessage('IMAGECROPPER_RESET')?>">
                        ⟲ <?=GetMessage('IMAGECROPPER_RESET')?>
                    </button>
                    <button type="button" class="imagecropper-btn imagecropper-remove" 
                            title="<?=GetMessage('IMAGECROPPER_REMOVE')?>">
                        ✕ <?=GetMessage('IMAGECROPPER_REMOVE')?>
                    </button>
                </div>
                
                <!-- Crop dimensions info -->
                <div class="imagecropper-dimensions">
                    <span class="imagecropper-dim-label"><?=GetMessage('IMAGECROPPER_DIMENSIONS')?></span>
                    <span class="imagecropper-dim-width">0</span> × 
                    <span class="imagecropper-dim-height">0</span> px
                </div>
            </div>
            
            <!-- Existing image display (if value exists) -->
            <?php if (!empty($arValue['url'])): ?>
            <script>
                BX.ready(function() {
                    var container = BX('<?=$uniqueId?>');
                    if (container) {
                        var imageData = <?=json_encode($arValue)?>;
                        container.imageCropperInstance = new ImageCropperEditor(container, imageData);
                    }
                });
            </script>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render view in admin list
     */
    public static function renderAdminListView($arProperty, $value)
    {
        $arValue = !empty($value) ? json_decode($value, true) : [];
        
        if (empty($arValue) || empty($arValue['thumb_url'])) {
            return '-';
        }
        
        $thumbUrl = htmlspecialcharsbx($arValue['thumb_url']);
        $dimensions = isset($arValue['width']) && isset($arValue['height']) 
            ? $arValue['width'] . '×' . $arValue['height'] 
            : '';
        
        return '<div class="imagecropper-admin-thumb">' .
               '<img src="' . $thumbUrl . '" alt="" style="max-width: 100px; max-height: 100px;">' .
               ($dimensions ? '<br><small>' . $dimensions . '</small>' : '') .
               '</div>';
    }

    /**
     * Render public view (for frontend developers)
     */
    public static function renderPublicView($arProperty, $value)
    {
        $arValue = !empty($value) ? json_decode($value, true) : [];
        
        if (empty($arValue)) {
            return '';
        }
        
        // Returns JSON for frontend developers to use
        return htmlspecialcharsbx(json_encode($arValue));
    }

    /**
     * Prepare property value for saving
     */
    public static function onBeforeSave($arProperty, $value)
    {
        // Value is already in JSON format from JavaScript
        return $value;
    }

    /**
     * Get searchable content for property
     */
    public static function getSearchContent($arProperty, $value)
    {
        return '';
    }

    /**
     * Get property value for REST API
     */
    public static function getRestValue($arProperty, $value)
    {
        return !empty($value) ? json_decode($value, true) : [];
    }
}
