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
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->libdir . '/completionlib.php');
use core_completion\progress;

\core\session\manager::init_empty_session();

$headers = getallheaders();

$update = file_get_contents("php://input");
$data = json_decode($update, false);

$config = get_config('message_telegram');

if ($config->telegramwebhookdump) {
    file_put_contents($CFG->tempdir . '/telegram.log', serialize($data) . "\n\n", FILE_APPEND | LOCK_EX);
}

if (!isset($headers['X-Telegram-Bot-Api-Secret-Token']) || $headers['X-Telegram-Bot-Api-Secret-Token'] != $config->sitebotsecret) {
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

    $userids = $tg->get_userids_by_chatid($fromid);
    if ($userids) {
        if (count($userids) > 1) {
            $userid = get_user_preferences('message_processor_telegram_prefid', $userids[0], $userids[0]);
        } else {
            $userid = $userids[0];
        }
        if ($user = $DB->get_record('user', ['id' => $userid])) {
            \core\session\manager::set_user($user);
        }
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
        $response = $tg->set_webhook_chatid($fromid, $text, $username);
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
            $response = $tg->send_api_command('sendMessage', $params);
        } else {
            $fromid = clean_param($data->message->from->id, PARAM_INT);
            $cost = $cost * 100;
            $response = $tg->send_api_command('sendInvoice', [
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
        $list = '';
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
                if (!$list) {
                    $buff .= PHP_EOL;
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
        if (count($userids) > 1) {
            $text .= PHP_EOL . get_string('botuserid', 'message_telegram');
        }
        if (file_exists($CFG->dirroot . '/admin/tool/certificate/lib.php')) {
            $text .= PHP_EOL . get_string('botcertificates', 'message_telegram');
        }
        if (!empty($config->sitebotpay)) {
            $text .= "\n/pay - " . get_string('botpaytitle', 'message_telegram');
        }
        $params = [
            'chat_id' => $fromid,
            'text' => $text,
            ];
        $response = $tg->send_api_command('sendMessage', $params);
    } else if (strpos($text, '/info') === 0) {
        $params = [
            'chat_id' => $fromid,
            'text' => '<b>' . format_string($SITE->fullname) . '</b>' . "\n🌐 " . $CFG->wwwroot . "\n✉ ️ " . $CFG->supportemail .
            ($CFG->supportpage ? "\n🛠 " . $CFG->supportpage : '') .
            ($CFG->servicespage ? "\n⭐ " . $CFG->servicespage : ''),
            'parse_mode' => 'HTML',
            'link_preview_options' => '{"is_disabled":true}',
            ];
            $response = $tg->send_api_command('sendMessage', $params);
    } else if (strpos($text, '/faq') === 0) {
        $params = [
            'chat_id' => $fromid,
            'text' => get_string('botfaq', 'message_telegram') .
            ($CFG->supportpage ? "\n$CFG->supportpage" : null) . "\n\n" .
            format_string(get_string('botfaqtext', 'message_telegram'), true),
            'parse_mode' => 'HTML',
            'link_preview_options' => '{"is_disabled":true}',
            ];
            $response = $tg->send_api_command('sendMessage', $params);
    } else if (strpos($text, '/userid') === 0 && $userid) {
        $buttons = [];
        foreach ($userids as $id) {
            $user = $DB->get_record('user', ['id' => $id]);
            $buttons[] = [[
                'text' => fullname($user),
                'callback_data' => '/userid ' . $id,
            ]];
        }
        $keyboard = [
        'inline_keyboard' => $buttons,
        ];
        $params = [
        'chat_id' => $fromid,
        'text' => "👑 Пользователь ID: {$userid}",
        'reply_markup' => json_encode($keyboard),
        ];
        $response = $tg->send_api_command('sendMessage', $params);
    } else if (strpos($text, '/enrols') === 0 && $userid) {
        $courses = enrol_get_users_courses($userid);
        $text = '';
        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            $completion = new completion_info($course);
            if ($completion->is_enabled()) {
                $progress = \core_completion\progress::get_course_progress_percentage($course, $userid) ?? 0;
            }
            $url = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
            $text .= PHP_EOL . '• ' . "<a href='{$url}'>" . format_string($course->fullname) . '</a>' .
            (floor($progress) ? ' (' . floor($progress) . '%)' : null);
        }
        $tg->send_message(get_string('botenrols', 'message_telegram') . PHP_EOL . $text, $userid);
    } else if (strpos($text, '/events') === 0 && $userid) {
        $calendar = \calendar_information::create(time(), 0, 0);
        $view = calendar_get_view($calendar, 'upcoming');
        $events = $view[0]->events ?? [];
        $text = null;
        foreach ($events as $event) {
            $start = date('d.m.Y H:i', $event->timestart);
            $end = date('d.m.Y H:i', $event->timestart + $event->timeduration);
            $duration = $event->timeduration ? '(' . round($event->timeduration / 60) . ' мин)' : '';
            $text .= "• {$start} — <a href='{$event->viewurl}'>{$event->name}</a> {$duration}\n" .
            ($event->description ? " Тема: {$event->description}\n" : null);
        }
        $head = "🗓 Предстоящие события:\n\n";
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
                get_user_preferences('message_processor_telegram_lang', get_string('none'), $userid),
            ),
            'reply_markup' => json_encode($keyboard),
            ];
            $response = $tg->send_api_command('sendMessage', $params);
    } else if (strpos($text, '/certificates') === 0 && $userid) {
        $certs = get_user_certificates($userid);
        $text = "📜 Ваши сертификаты:\n\n";
        $buff = null;
        foreach ($certs as $cert) {
            $buff .= '• ' . "<a href='{$cert['url']}'>{$cert['name']}</a>" . ' — ' . $cert['date'] . PHP_EOL;
        }
        if (!$buff) {
            $text .= get_string('none');
        } else {
            $text .= $buff;
        }
        $keyboard = [
            'inline_keyboard' => [[[
            'text' => '📥 Скачать',
            'callback_data' => '/getcert',
            ]]],
        ];
        $response = $tg->send_api_command(
            'sendMessage',
            [
            'chat_id' => $fromid,
            'text' => $text,
            'reply_markup' => json_encode($keyboard),
            'parse_mode' => 'HTML',
            'link_preview_options' => '{"is_disabled":true}',
            ]
        );
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
        $response = $tg->send_api_command('answerPreCheckoutQuery', [
           "pre_checkout_query_id" => $data->pre_checkout_query->id,
           "ok" => 'True',
        ]);
    }
} else if (isset($data->callback_query->data)) {
    $fromid = clean_param($data->callback_query->from->id, PARAM_INT);
    $chatid = clean_param($data->callback_query->message->chat->id, PARAM_INT);

    $userids = $tg->get_userids_by_chatid($fromid);
    if ($userids) {
        $userid = $userids[0];
        if ($user = $DB->get_record('user', ['id' => $userid])) {
            \core\session\manager::set_user($user);
        }
    }

    if (strpos($data->callback_query->data, '/pay') === 0 && $cost = substr($data->callback_query->data, 5)) {
        $fromid = clean_param($data->callback_query->from->id, PARAM_INT);
        $cost = $cost * 100;
        $response = $tg->send_api_command('sendInvoice', [
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

        if (count($userids) > 1) {
            $userid = get_user_preferences('message_processor_telegram_prefid', $userids[0], $userids[0]);
        }
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
    } else if (strpos($data->callback_query->data, '/getcert') === 0 && $userid) {
        $certs = get_user_certificates($userid);
        if ($id = substr($data->callback_query->data, 9)) {
            $issue = \tool_certificate\template::get_issue_from_code($id);
            $context = \context_course::instance($issue->courseid, IGNORE_MISSING) ?: null;
            $template = $issue ? \tool_certificate\template::instance($issue->templateid) : null;
            if (
                $template && (\tool_certificate\permission::can_verify() ||
                \tool_certificate\permission::can_view_issue($template, $issue, $context))
            ) {
                $certurl = $template->get_issue_file_url($issue);
                    $response = $tg->send_api_command('sendDocument', [
                    'chat_id' => $chatid,
                    'document' => $certurl,
                    'caption' => '📄 Ваш сертификат',
                    ]);
                    $response->description .= $certurl;
            }
        } else {
            $keyboard = ['inline_keyboard' => []];
            foreach ($certs as $cert) {
                $keyboard['inline_keyboard'][] = [
                ['text' => $cert['name'] . ' ' . $cert['date'], 'callback_data' => '/getcert ' . $cert['code']],
                ];
            }

            $response = $tg->send_api_command('editMessageText', [
            'chat_id' => $chatid,
            'message_id' => $data->callback_query->message->message_id,
            'text' => 'Выберите сертификат:',
            'reply_markup' => json_encode($keyboard),
            ]);
        }
    } else if (strpos($data->callback_query->data, '/userid') === 0 && $id = substr($data->callback_query->data, 8)) {
        $uid = clean_param($id, PARAM_INT);
        if ($userid && $uid) {
            set_user_preference('message_processor_telegram_prefid', $uid, $userid);
            $tg->send_api_command(
                'sendMessage',
                [
                'chat_id' => $fromid,
                'text' => $uid,
                ]
            );
        }
    }
}

if ($config->telegramwebhookdump) {
    file_put_contents($CFG->tempdir . '/telegram.log', (!empty($response) ? serialize($response) : serialize($data)) .
    "\n\n", FILE_APPEND | LOCK_EX);
}
if ($fromid && isset($response->error_code)) {
     $tg->send_api_command(
         'sendMessage',
         [
                'chat_id' => $fromid,
                'text' => serialize($response->description),
                ]
     );
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

/**
 * Получает список сертификатов пользователя.
 *
 * @param int $userid Идентификатор пользователя в Moodle
 * @return array Массив сертификатов пользователя
 */
function get_user_certificates(int $userid) {
    global $DB, $CFG;

    $sql = "SELECT ci.id, ci.timecreated, ci.code, t.name
              FROM {tool_certificate_issues} ci
              JOIN {tool_certificate_templates} t ON t.id = ci.templateid
             WHERE ci.userid = :userid
          ORDER BY ci.timecreated DESC";
    $records = $DB->get_records_sql($sql, ['userid' => $userid]);

    $certs = [];
    foreach ($records as $rec) {
        $date = date('d.m.Y', $rec->timecreated);
        $url = $CFG->wwwroot . '/admin/tool/certificate/view.php?code=' . $rec->code;
        $certs[] = [
            'name' => $rec->name,
            'date' => $date,
            'code' => $rec->code,
            'url'  => $url,
        ];
    }
    return $certs;
}
