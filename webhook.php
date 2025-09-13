<?php

require_once(__DIR__ . '/../../../config.php');

$headers = getallheaders();

$update = file_get_contents("php://input");
//$update = json_decode($update, true);

file_put_contents('/tmp/tttt', $headers['X-Telegram-Bot-Api-Secret-Token']."\n".$update."\n\n", FILE_APPEND|LOCK_EX);

$telegrammanager = new message_telegram\manager();


http_response_code(200);
echo "OK";
