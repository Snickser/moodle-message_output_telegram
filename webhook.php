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

$langs = get_string_manager()->get_list_of_translations();

$tg = new message_telegram\manager();

if (isset($data->message)) {
    $chatid = clean_param($data->message->from->id, PARAM_INT);
    $text = clean_param($data->message->text, PARAM_TEXT);
    $username = clean_param($data->message->from->username, PARAM_TEXT);

    $userid = $tg->get_userid_by_chatid($chatid);

    $lang = get_user_preferences('message_processor_telegram_lang', null, $userid);
    force_current_language($lang);

    if (strpos($text, '/start') === 0) {
        $data = $tg->set_webhook_chatid($chatid, $text, $username);
    } else if (strpos($text, '/pay') === 0 && $config->sitebotpay) {
        if (!$cost = substr($text, 5)) {
            $keyboard = [
            'inline_keyboard' => [
            [
            ['text' => '800', 'callback_data' => '/pay 800'],
            ['text' => '2000', 'callback_data' => '/pay 2000'],
            ['text' => '5000', 'callback_data' => '/pay 5000'],
            ],
            ],
            ];
            $params = [
            'chat_id' => $chatid,
            'text' => 'Выберите сумму:',
            'reply_markup' => json_encode($keyboard),
            ];
            $data = $tg->send_api_command('sendMessage', $params);
        } else {
            $cost = $cost * 100;
            $data = $tg->send_api_command('sendInvoice', [
            "chat_id" => $chatid,
            "title" => "Пожертвование",
            "description" => "На поддержание учебной платформы",
            "payload" => "Donate",
            "provider_token" => $config->sitebotpay,
            "currency" => "RUB",
            "start_parameter" => "test",
            "prices" => json_encode([
            [
            "label"  => "К оплате",
            "amount" => $cost,
            ],
               ]),

            ]);
        }
    } else if (strpos($text, '/courses') === 0 && $userid) {
        $courses = get_courses(null, true);
        $list = null;
        foreach ($courses as $course) {
            if ($course->visible) {
                $buff = '🔸 <b>' . format_string($course->fullname, true) . '</b>' . PHP_EOL;
                if (!empty($course->summary) && mb_strlen($course->summary) + mb_strlen($buff) < 4080) {
                    $buff .= '<i>  ' . format_string($course->summary, false) . '</i>' . PHP_EOL;
                }
                if (mb_strlen($list) + mb_strlen($buff) < 4096) {
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
/lang - переключение языка
/courses - список курсов
",
            $userid
        );
    } else if (strpos($text, '/info') === 0 && $userid) {
        $tg->send_message(format_string($SITE->fullname) . "\n" . $CFG->wwwroot . "\n" . $CFG->supportemail, $userid);
    } else if (strpos($text, '/lang') === 0 && $userid) {
$buttons = [];
foreach ($langs as $langcode => $name) {
    $buttons[] = [
        'text' => $name,
        'callback_data' => '/lang '.$langcode
    ];
}
$keyboard = [
    'inline_keyboard' => [
        $buttons
    ]
];
            $params = [
            'chat_id' => $chatid,
            'text' => 'Выберите язык ('.get_user_preferences('message_processor_telegram_lang', null, $userid).'):',
            'reply_markup' => json_encode($keyboard),
            ];
            $data = $tg->send_api_command('sendMessage', $params);
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
            'text' => get_string('firstregister', 'message_telegram') . $CFG->wwwroot,
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
} else if (isset($data->callback_query->data)) {
    $chatid = clean_param($data->callback_query->from->id, PARAM_INT);
    $userid = $tg->get_userid_by_chatid($chatid);

    if (strpos($data->callback_query->data, '/pay') === 0 && $cost = substr($data->callback_query->data, 5)) {
        $cost = $cost * 100;
        $data = $tg->send_api_command('sendInvoice', [
        "chat_id" => $chatid,
        "title" => "Пожертвование",
        "description" => "На поддержание учебной платформы",
        "payload" => "Donate",
        "provider_token" => $config->sitebotpay,
        "currency" => "RUB",
        "start_parameter" => "test",
        "prices" => json_encode([
        [
            "label"  => "К оплате",
            "amount" => $cost,
        ],
           ]),

        ]);
    } else if (strpos($data->callback_query->data, '/lang') === 0 && $lang = substr($data->callback_query->data, 6)) {
	if ($userid) {
    	set_user_preference('message_processor_telegram_lang', $lang, $userid);
        $tg->send_api_command(
            'sendMessage',
            [
            'chat_id' => $chatid,
            'text' => $lang,
            ]
        );
	}
    }


}

file_put_contents($CFG->tempdir . '/telegram.log', serialize($data) . "\n\n", FILE_APPEND | LOCK_EX);

http_response_code(200);
echo "OK";
