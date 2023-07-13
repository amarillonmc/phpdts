<?php
define('CURSCRIPT', 'record_backend');

require './include/common.inc.php';

try {
    $jsonData = file_get_contents('php://input');
    $requestData = json_decode($jsonData, true);

    if ($requestData === null) {
        http_response_code(400);
        $response = ["message" => "Invalid JSON data"];
        echo json_encode($response);
        return;
    }

    global $gamenum;

    $lastRecord = $requestData['lastRecord'];
    $nickinfo = $requestData['nickinfo'];
    $directoryPath = "./records/$gamenum/$nickinfo";
    $filePath = "$directoryPath/record.txt";

    // 创建目录
    if (!is_dir($directoryPath) && !mkdir($directoryPath, 0777, true)) {
        throw new Exception('Failed to create directory');
    }

    // 写入数据到文件
    if (!file_put_contents($filePath, $lastRecord . "\n", FILE_APPEND)) {
        throw new Exception('Failed to write data to file');
    }

    // 返回信息
    echo json_encode([
        "message" => "Success",
    ]);
} catch (Exception $e) {
    http_response_code(500);
    $response = ["message" => $e->getMessage()];
    echo json_encode($response);
}
