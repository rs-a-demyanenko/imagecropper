<?php
/**
 * ImageCropper Module Helper Class
 * Provides utility methods for working with cropped images
 */

class CImageCropperHelper
{
    /**
     * Get image data from property value
     * @param string $value JSON string from property
     * @return array|null Decoded image data or null on error
     */
    public static function getImageData($value)
    {
        if (empty($value)) {
            return null;
        }
        
        $data = json_decode($value, true);
        if (!is_array($data)) {
            return null;
        }
        
        return $data;
    }

    /**
     * Get cropped image URL
     * @param string $value JSON string from property
     * @return string|null Cropped image URL or null
     */
    public static function getCroppedUrl($value)
    {
        $data = self::getImageData($value);
        return $data['url'] ?? null;
    }

    /**
     * Get thumbnail URL
     * @param string $value JSON string from property
     * @return string|null Thumbnail URL or null
     */
    public static function getThumbUrl($value)
    {
        $data = self::getImageData($value);
        return $data['thumb_url'] ?? null;
    }

    /**
     * Get image dimensions
     * @param string $value JSON string from property
     * @return array|null Array with width and height or null
     */
    public static function getDimensions($value)
    {
        $data = self::getImageData($value);
        if (!$data) {
            return null;
        }
        
        return [
            'width' => $data['width'] ?? 0,
            'height' => $data['height'] ?? 0,
            'thumb_width' => $data['thumb_width'] ?? 0,
            'thumb_height' => $data['thumb_height'] ?? 0,
        ];
    }

    /**
     * Get crop coordinates
     * @param string $value JSON string from property
     * @return array|null Crop data or null
     */
    public static function getCropData($value)
    {
        $data = self::getImageData($value);
        return $data['crop'] ?? null;
    }

    /**
     * Get original image dimensions
     * @param string $value JSON string from property
     * @return array|null Original dimensions or null
     */
    public static function getOriginalDimensions($value)
    {
        $data = self::getImageData($value);
        if (!$data) {
            return null;
        }
        
        return [
            'width' => $data['original_width'] ?? 0,
            'height' => $data['original_height'] ?? 0,
        ];
    }

    /**
     * Save cropped image to permanent location
     * @param array $imageData Image data from property
     * @param string $destinationPath Path to save the image
     * @return bool Success status
     */
    public static function saveCroppedImage($imageData, $destinationPath)
    {
        if (empty($imageData['url'])) {
            return false;
        }

        $docRoot = \Bitrix\Main\Application::getDocumentRoot();
        $sourcePath = $docRoot . $imageData['url'];
        
        if (!file_exists($sourcePath)) {
            return false;
        }

        // Create directory if needed
        $destDir = dirname($destinationPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        return copy($sourcePath, $destinationPath);
    }

    /**
     * Delete associated images
     * @param string $value JSON string from property
     * @return bool Success status
     */
    public static function deleteImages($value)
    {
        $data = self::getImageData($value);
        if (!$data) {
            return false;
        }

        $docRoot = \Bitrix\Main\Application::getDocumentRoot();
        $deleted = false;

        // Delete cropped image
        if (!empty($data['url'])) {
            $croppedPath = $docRoot . $data['url'];
            if (file_exists($croppedPath)) {
                unlink($croppedPath);
                $deleted = true;
            }
        }

        // Delete thumbnail
        if (!empty($data['thumb_url'])) {
            $thumbPath = $docRoot . $data['thumb_url'];
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
                $deleted = true;
            }
        }

        return $deleted;
    }

    /**
     * Convert base64 data URL to file
     * @param string $dataURL Base64 data URL
     * @param string $destinationPath Path to save the file
     * @return bool|string Saved file path or false on error
     */
    public static function saveFromDataUrl($dataURL, $destinationPath)
    {
        if (strpos($dataURL, 'data:') !== 0) {
            return false;
        }

        $parts = explode(',', $dataURL, 2);
        if (count($parts) !== 2) {
            return false;
        }

        $data = base64_decode($parts[1]);
        if ($data === false) {
            return false;
        }

        // Create directory if needed
        $destDir = dirname($destinationPath);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (file_put_contents($destinationPath, $data) === false) {
            return false;
        }

        return $destinationPath;
    }
}
