<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     message_telegram
 * @copyright   2025 Alex Orlov <snickser@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php'); // @codingStandardsIgnoreLine

\core\session\manager::init_empty_session();
\core\session\manager::set_user(get_admin());

$headers = getallheaders();

$update = file_get_contents("php://input");
$data = json_decode($update, false);

file_put_contents($CFG->tempdir . '/telegram.log', serialize($data) . "\n\n", FILE_APPEND | LOCK_EX);

$config = get_config('message_telegram');

if ($headers['X-Telegram-Bot-Api-Secret-Token'] != $config->sitebotsecret) {
    http_response_code(200);
    echo "OK";
    die;
}

$tg = new message_telegram\manager();

if (isset($data->message)) {
    $chatid = clean_param($data->message->from->id, PARAM_INT);
    $text = clean_param($data->message->text, PARAM_TEXT);
    $username = clean_param($data->message->from->username, PARAM_TEXT);

    $userid = $tg->get_userid_by_chatid($chatid);

    if (strpos($text, '/start') === 0) {
        $data = $tg->set_webhook_chatid($chatid, $text, $username);
    } else if (strpos($text, '/donate') === 0) {
        $data = $tg->send_api_command('sendInvoice', [
           "chat_id" => $chatid,
        "title" => "Пожертвование",

        "description" => "На поддержание учебной платформы",

        "payload" => "Donate",

        "provider_token" => "381764678:TEST:141557",

        "currency" => "RUB",

        "start_parameter" => "test",

        "prices" => json_encode([
        [
            "label"  => "К оплате",
            "amount" => 99000,
        ],
           ]),

        ]);
    } else if (strpos($text, '/courses') === 0 && $userid) {
        $courses = get_courses(null, true);
        $list = null;
        foreach ($courses as $course) {
            if ($course->visible) {
                $buff = '🔸 <b>' . format_string($course->fullname, true) . '</b>' . PHP_EOL;
                if (!empty($course->summary) && strlen($course->summary) + strlen($buff) < 4080) {
                    $buff .= '<i>    ' . format_string($course->summary, false) . '</i>' . PHP_EOL;
                }
                if (strlen($list) + strlen($buff) < 4096) {
            	    $list .= $buff;
            	} else {
            	    $tg->send_message($list, $userid);
            	    $list = $buff;
            	}
            }
        }
        $tg->send_message($list, $userid);
    } else if (strpos($text, '/help') === 0 && $userid) {
        $tg->send_message(
            "Подсказки
/info - информация о платформе
/courses - список курсов
",
            $userid
        );
    } else if (strpos($text, '/info') === 0 && $userid) {
        $tg->send_message($CFG->wwwroot, $userid);
    } else if (isset($data->message->successful_payment)) {
        http_response_code(200);
        echo "OK";
        die;
    } else if ($userid) {
        $tg->send_message('Не знаю что это такое 🤷', $userid);
    } else {
        $tg->send_api_command(
            'sendMessage',
            [
            'chat_id' => $chatid,
            'text' => 'Вначале зарегистрируйтесь на сайте https://academy.bhaktilata.ru',
            ]
        );
        http_response_code(200);
        echo "OK";
        die;
    }
} else if (isset($data->pre_checkout_query)) {
    $chatid = clean_param($data->pre_checkout_query->from->id, PARAM_INT);
    if (isset($data->pre_checkout_query->id)) {
        $data = $tg->send_api_command('answerPreCheckoutQuery', [
           "pre_checkout_query_id" => $data->pre_checkout_query->id,
           "ok" => 'True',
        ]);
    }
}

file_put_contents($CFG->tempdir . '/telegram.log', serialize($data) . "\n\n", FILE_APPEND | LOCK_EX);

http_response_code(200);
echo "OK";
