#!/usr/bin/php

<?php

$token = '*:*';

$dir = '/var/www/moodledata/telegram/spool';

if ($dh = opendir($dir)) {
    while (($file = readdir($dh)) !== false) {
        if($file == '..' or $file=='.') continue;
        $fh = fopen($dir.'/'.$file, "r");
        $chat_id='';
        $chat_id = fgets($fh);
        $buff='';
        $text='';
        while(($buff = fgets($fh)) !== false){
            $text .= $buff;
        }
        $ret = SEND($chat_id, $text, $dir.'/'.$file);
        usleep(50000);
    }
    closedir($dh);
}

function SEND($chat_id, $text, $file){

$array = array(
    'chat_id' => $chat_id,
    'text' => $text,
    'link_preview_options' => '{"is_disabled":true}',
);

$ch = curl_init('https://api.telegram.org/bot'.$token.'/sendMessage');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array, '', '&'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = json_decode(curl_exec($ch));
curl_close($ch);

$buff = $today = date("Y-m-d H:i:s");

if($response->ok == true) {
    $buff .= " ".$response->result->message_id;
    unlink($file);
} else {
    $buff .= " ".$response->error_code." ".$response->description;
}

//print $buff."\n";

}

?>
