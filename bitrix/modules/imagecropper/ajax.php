<?php
/**
 * AJAX endpoint for image upload and processing
 * Handles server-side image saving after client-side cropping
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!check_bitrix_sessid() || !\Bitrix\Main\Loader::includeModule('iblock')) {
    echo json_encode(['error' => 'Access denied']);
    die();
}

global $APPLICATION;
$APPLICATION->RestartBuffer();
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

if ($action === 'upload') {
    handleUpload();
} elseif ($action === 'delete') {
    handleDelete();
} else {
    echo json_encode(['error' => 'Invalid action']);
}

function handleUpload()
{
    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'No image uploaded or upload error']);
        return;
    }

    $file = $_FILES['image'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['error' => 'Invalid file type']);
        return;
    }

    // Generate unique filename
    $uploadDir = \Bitrix\Main\Application::getDocumentRoot() . '/upload/imagecropper/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('ic_') . '.' . $extension;
    $filepath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['error' => 'Failed to save file']);
        return;
    }

    // Get image info
    $imageInfo = getimagesize($filepath);
    $url = '/upload/imagecropper/' . $filename;

    echo json_encode([
        'success' => true,
        'url' => $url,
        'width' => $imageInfo[0],
        'height' => $imageInfo[1],
        'type' => $file['type']
    ]);
}

function handleDelete()
{
    $url = $_POST['url'] ?? '';
    
    if (empty($url)) {
        echo json_encode(['error' => 'No URL provided']);
        return;
    }

    // Convert URL to filepath
    $filepath = \Bitrix\Main\Application::getDocumentRoot() . $url;
    
    if (file_exists($filepath)) {
        unlink($filepath);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'File not found']);
    }
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
