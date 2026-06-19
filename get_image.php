<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require_once 'vendor/autoload.php'; 

$client = new Google\Client();
$client->setAuthConfig('secure_keys/drive_key.json'); 
$client->addScope(Google\Service\Drive::DRIVE_READONLY); 

$service = new Google\Service\Drive($client);

if (!isset($_GET['studyId']) || empty($_GET['studyId'])) {
    http_response_code(400);
    echo "Error: Missing Study ID";
    exit;
}

$studyId = $_GET['studyId'];
$type = isset($_GET['type']) ? $_GET['type'] : '';

// จัดลำดับการค้นหาไฟล์ (หากหาแบบระบุมุมมองไม่เจอ จะถอยกลับไปหาไฟล์เวอร์ชันเก่าให้อัตโนมัติ)
$searchNames = [];
if ($type === 'pre_ap') {
    $searchNames = [$studyId . "_pre_ap.png", $studyId . "_pre.png", $studyId . ".png"];
} else if ($type === 'pre_lat') {
    $searchNames = [$studyId . "_pre_lat.png", $studyId . "_pre.png", $studyId . ".png"];
} else if ($type === 'post_ap') {
    $searchNames = [$studyId . "_post_ap.png", $studyId . "_post.png", $studyId . ".png"];
} else if ($type === 'post_lat') {
    $searchNames = [$studyId . "_post_lat.png", $studyId . "_post.png", $studyId . ".png"];
} else {
    $searchNames = [$studyId . ".png"];
}

// 🔴 อย่าลืมนำรหัสโฟลเดอร์ Google Drive ของคุณมาวางแทนที่ตรงนี้ครับ
$folderId = "17EmcfajSNRFcTlEn9laGCL8el6P3dHWL"; 

try {
    $fileId = null;
    foreach ($searchNames as $filename) {
        $query = "name = '$filename' and '$folderId' in parents and trashed = false";
        $results = $service->files->listFiles([
            'q' => $query,
            'fields' => 'files(id, name)'
        ]);
        if (count($results->getFiles()) > 0) {
            $fileId = $results->getFiles()[0]->id;
            break; // เจอไฟล์แล้วให้หยุดลูปทันที
        }
    }
    
    if (!$fileId) {
        http_response_code(404);
        echo "Image not found in the secure Google Drive folder.";
        exit;
    }
    
    $response = $service->files->get($fileId, ['alt' => 'media']);
    
    header("Content-Type: image/png");
    echo $response->getBody()->getContents();

} catch (Exception $e) {
    http_response_code(500);
    echo "Internal Server Error: " . $e->getMessage();
}
?>