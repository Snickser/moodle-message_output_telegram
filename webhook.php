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

$headers = getallheaders();

$update = file_get_contents("php://input");
$data = json_decode($update, false);

$config = get_config('message_telegram');

if ($config->telegramwebhookdump) {
    file_put_contents($CFG->tempdir . '/telegram.log', serialize($data) . "\n\n", FILE_APPEND | LOCK_EX);
}

if ($headers['X-Telegram-Bot-Api-Secret-Token'] != $config->sitebotsecret) {
    http_response_code(200);
    echo "OK";
    die;
}

$langs = get_string_manager()->get_list_of_translations();

$tg = new message_telegram\manager();

if (isset($data->message)) {
    $fromid = clean_param($data->message->from->id, PARAM_INT);
    $chatid = clean_param($data->message->chat->id, PARAM_INT);
    $text = clean_param($data->message->text, PARAM_TEXT);
    $username = clean_param($data->message->from->username, PARAM_TEXT);

    $userid = $tg->get_userid_by_chatid($fromid);

    if ($userid) {
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        \core\session\manager::set_user($user);
    }

    $lang = get_user_preferences('message_processor_telegram_lang', null, $userid);
    force_current_language($lang);

    if ($chatid < 0) {
        if ($user) {
            private_answer($tg, $config->sitebotusername, $chatid, $data->message->message_id);
        } else {
            private_answer($tg, $config->sitebotusername, $chatid, $data->message->message_id, "?start");
        }
    }

    if (strpos($text, '/start') === 0) {
        \core\session\manager::set_user(get_admin());
        $return = $tg->set_webhook_chatid($fromid, $text, $username);
    } else if (strpos($text, '/pay') === 0 && $config->sitebotpay) {
        if (!$cost = (int)substr($text, 5)) {
            $numbers = array_map('trim', explode(',', $config->sitebotpaycosts));
            $buttons = array_map(function ($n) {
                return [
                'text' => $n,
                'callback_data' => '/pay ' . $n,
                ];
            }, $numbers);
            $keyboard = [
            'inline_keyboard' => [ $buttons,
            ],
            ];
            $params = [
            'chat_id' => $fromid,
            'text' => get_string('botpay', 'message_telegram', $config->sitebotpaycurrency),
            'reply_markup' => json_encode($keyboard),
            ];
            $return = $tg->send_api_command('sendMessage', $params);
        } else {
            $fromid = clean_param($data->message->from->id, PARAM_INT);
            $cost = $cost * 100;
            $return = $tg->send_api_command('sendInvoice', [
            "chat_id" => $fromid,
            "title" => get_string('botpaytitle', 'message_telegram'),
            "description" => get_string('botpaydesc', 'message_telegram'),
            "payload" => "Donate",
            "provider_token" => $config->sitebotpay,
            "currency" => $config->sitebotpaycurrency,
            "start_parameter" => "test",
            "prices" => json_encode([
            [
            "label"  => get_string('botpaydesc', 'message_telegram'),
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
                if (!$list) {
                    $buff = '🏰 ';
                } else {
                    $buff = '🔸 ';
                }
                $buff .= '<b>' . format_string($course->fullname, true) . '</b>' . PHP_EOL;
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
    } else if (strpos($text, '/help') === 0) {
        $text = null;
        if ($userid) {
            $text = get_string('bothelp', 'message_telegram');
        } else {
            $text = get_string('bothelp_anonymous', 'message_telegram');
        }
        if (!empty($config->sitebotpay)) {
            $text .= "\n/pay - " . get_string('botpaytitle', 'message_telegram');
        }
        $params = [
            'chat_id' => $fromid,
            'text' => $text,
            ];
        $return = $tg->send_api_command('sendMessage', $params);
    } else if (strpos($text, '/info') === 0) {
            $params = [
            'chat_id' => $fromid,
            'text' => '<b>' . format_string($SITE->fullname) . '</b>' . "\n🌐 " . $CFG->wwwroot . "\n✉ ️ " . $CFG->supportemail .
            ($CFG->supportpage ? "\n🛠 " . $CFG->supportpage : '') .
            ($CFG->servicespage ? "\n⭐ " . $CFG->servicespage : ''),
            'parse_mode' => 'HTML',
            'link_preview_options' => '{"is_disabled":true}',
            ];
            $return = $tg->send_api_command('sendMessage', $params);
    } else if (strpos($text, '/faq') === 0) {
            $params = [
            'chat_id' => $fromid,
            'text' => get_string('botfaq', 'message_telegram') .
            ($CFG->supportpage ? "\n$CFG->supportpage" : null) . "\n\n" .
            format_string(get_string('botfaqtext', 'message_telegram'), true),
            'parse_mode' => 'HTML',
            'link_preview_options' => '{"is_disabled":true}',
            ];
            $return = $tg->send_api_command('sendMessage', $params);
    } else if (strpos($text, '/userid') === 0 && $userid) {
        $tg->send_message("{$user->id}", $userid);
    } else if (strpos($text, '/enrols') === 0 && $userid) {
        $courses = enrol_get_users_courses($userid);
        $keyboard = [];
        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            $keyboard[] = [[
                'text' => format_string($course->fullname),
                'url' => $CFG->wwwroot . '/course/view.php?id=' . $course->id,
            ]];
        }
        $tg->send_api_command('sendMessage', [
        'chat_id' => $fromid,
        'text' => get_string('botenrols', 'message_telegram'),
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]);
    } else if (strpos($text, '/events') === 0 && $userid) {
        require_once($CFG->dirroot . '/calendar/lib.php');
        $calendar = \calendar_information::create(time(), 0, 0);
        $view = calendar_get_view($calendar, 'upcoming');
        $events = $view[0]->events ?? [];
        $text = null;
        foreach ($events as $event) {
            $start = date('d.m.Y H:i', $event->timestart);
            $end = date('d.m.Y H:i', $event->timestart + $event->timeduration);
            $duration = $event->timeduration ? '(' . round($event->timeduration / 60) . ' мин)' : '';
            $text .= "• {$start} — {$event->name} {$duration}\n  Тема: {$event->description}\n";
        }
        $head = "🗓 Предстоящие события:\n";
        if ($text) {
            $text = $head . $text;
        } else {
            $text = $head . get_string('no');
        }
        $tg->send_message($text, $userid);
    } else if (strpos($text, '/lang') === 0 && $userid) {
        $buttons = [];
        foreach ($langs as $langcode => $name) {
            $buttons[] = [[
                'text' => $name,
                'callback_data' => '/lang ' . $langcode,
            ]];
        }
        $keyboard = [
        'inline_keyboard' => $buttons,
        ];
            $params = [
            'chat_id' => $fromid,
            'text' => get_string(
                'botlang',
                'message_telegram',
                get_user_preferences('message_processor_telegram_lang', get_string('no'), $userid),
            ),
            'reply_markup' => json_encode($keyboard),
            ];
            $return = $tg->send_api_command('sendMessage', $params);
    } else if (isset($data->message->successful_payment)) {
        http_response_code(200);
        echo "OK";
        die;
    } else if ($text && $userid) {
        $tg->send_message(get_string('botidontknow', 'message_telegram'), $userid);
    } else if ($text) {
        $tg->send_api_command(
            'sendMessage',
            [
            'chat_id' => $fromid,
            'text' => get_string('firstregister', 'message_telegram', $CFG->wwwroot),
            ]
        );
        http_response_code(200);
        echo "OK";
        die;
    }
} else if (isset($data->pre_checkout_query)) {
    if (isset($data->pre_checkout_query->id)) {
        $return = $tg->send_api_command('answerPreCheckoutQuery', [
           "pre_checkout_query_id" => $data->pre_checkout_query->id,
           "ok" => 'True',
        ]);
    }
} else if (isset($data->callback_query->data)) {
    $fromid = clean_param($data->callback_query->from->id, PARAM_INT);
    $chatid = clean_param($data->callback_query->message->chat->id, PARAM_INT);
    $userid = $tg->get_userid_by_chatid($fromid);

    if ($userid) {
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        \core\session\manager::set_user($user);
    }

    if (strpos($data->callback_query->data, '/pay') === 0 && $cost = substr($data->callback_query->data, 5)) {
        $fromid = clean_param($data->callback_query->from->id, PARAM_INT);
        $cost = $cost * 100;
        $return = $tg->send_api_command('sendInvoice', [
        "chat_id" => $fromid,
        "title" => get_string('botpaytitle', 'message_telegram'),
        "description" => get_string('botpaydesc', 'message_telegram'),
        "payload" => "Donate",
        "provider_token" => $config->sitebotpay,
        "currency" => $config->sitebotpaycurrency,
        "start_parameter" => "test",
        "prices" => json_encode([
        [
            "label"  => get_string('botpaydesc', 'message_telegram'),
            "amount" => $cost,
        ],
           ]),

        ]);
    } else if (strpos($data->callback_query->data, '/lang') === 0 && $lang = substr($data->callback_query->data, 6)) {
        $languages = [
        'ru' => ['name' => 'Русский', 'flag' => '🇷🇺'],
        'en' => ['name' => 'English', 'flag' => '🇺🇸'],
        'be' => ['name' => 'Беларуская', 'flag' => '🇧🇾'],
        'uk' => ['name' => 'Українська', 'flag' => '🇺🇦'],
        ];

        if ($userid) {
            set_user_preference('message_processor_telegram_lang', $lang, $userid);
            $tg->send_api_command(
                'sendMessage',
                [
                'chat_id' => $fromid,
                'text' => $languages[$lang]['flag'],
                ]
            );
            $user = new stdClass();
            $user->id = $userid;
            $user->lang = $lang;
            user_update_user($user, false, true);
        }
    }
}

if ($config->telegramwebhookdump) {
    file_put_contents($CFG->tempdir . '/telegram.log', ($return ? serialize($return) : serialize($data)) .
    "\n\n", FILE_APPEND | LOCK_EX);
}

http_response_code(200);
echo "OK";
die;

/**
 * Отправляет ответ в приватный чат пользователю или уведомление в группе.
 *
 * @param object $tg        Экземпляр клиента Telegram API с методом send_api_command.
 * @param string $botname   Имя бота в Telegram (без @).
 * @param int    $chatid    Идентификатор чата, куда отправляется сообщение.
 * @param int    $messageid ID сообщения, на которое будет дан reply.
 * @param string|null $start Дополнительный параметр (обычно payload для deep link).
 *
 * @return mixed Результат выполнения метода send_api_command (ответ Telegram API).
 */
function private_answer($tg, $botname, $chatid, $messageid, $start = null) {
    if ($start) {
        $text = "🤔 Ответил бы в привате, но мы пока не знакомы ☺️";
    } else {
        $text = "👍 Ответил в приват.";
    }
            $replymarkup = [
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Перейти',
                            'url' => "https://t.me/$botname$start",
                        ],
                    ],
                ],
            ];

            $options = [
                'chat_id' => $chatid,
                'text' => $text,
                'reply_to_message_id' => $messageid,
                'reply_markup' => json_encode($replymarkup),
            ];
            return $tg->send_api_command(
                'sendMessage',
                $options
            );
}
