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

require_once(__DIR__ . '/lib.php');

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

    $record = $DB->get_record('message_telegram', ['chatid' => $chatid]);
    $lastmsgid = clean_param($data->message->message_id, PARAM_TEXT);
    $lastdata = clean_param($data->message->text, PARAM_TEXT);
    $step = 'command';

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
            $text .= PHP_EOL . get_string('botuseridhelp', 'message_telegram');
        }
        if (file_exists($CFG->dirroot . '/admin/tool/certificate/lib.php')) {
            $text .= PHP_EOL . get_string('botcertificates', 'message_telegram');
        }

        $courses = enrol_get_all_users_courses($userid, true, '*');
        $roleids = array_map('intval', explode(',', $config->sitebotmsgroles));
        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            $hasrole = false;
            foreach ($roleids as $roleid) {
                if (user_has_role_assignment($userid, $roleid, $context->id)) {
                    $hasrole = true;
                    break;
                }
            }
            if (!$hasrole) {
                continue;
            }
            $groups = groups_get_all_groups($course->id);
            foreach ($groups as $group) {
                $members = groups_get_members($group->id, 'u.id');
                if (isset($members[$userid])) {
                    $text .= PHP_EOL . get_string('botmessagehelp', 'message_telegram');
                }
            }
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
        'text' => get_string('botuserid', 'message_telegram', $userid),
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
            ($event->description ? ' ' . get_string('subject') . "{$event->description}\n" : null);
        }
        $head = get_string('botevents', 'message_telegram');
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
    } else if (strpos($text, '/message') === 0 && $userid) {
        $courses = enrol_get_all_users_courses($userid, false, '*');
        $buttons = [];
        foreach ($courses as $course) {
            $buttons[] = [[
                'text' => format_string($course->fullname),
                'callback_data' => '/message ' . $course->id,
            ]];
        }
        $keyboard = [
        'inline_keyboard' => $buttons,
        ];
        $response = $tg->send_api_command(
            'sendMessage',
            [
            'chat_id' => $fromid,
            'text' => '📚 ' . get_string('selectacourse'),
            'reply_markup' => json_encode($keyboard),
            ]
        );
    } else if (strpos($text, '/certificates') === 0 && $userid) {
        $certs = get_user_certificates($userid);
        $text = get_string('botcerts', 'message_telegram');
        $buff = '';
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
            'text' => get_string('botcertdownload', 'message_telegram'),
            'callback_data' => '/getcert',
            ]]],
            ];
        $params = [
            'chat_id' => $fromid,
            'text' => $text,
            'parse_mode' => 'HTML',
            'link_preview_options' => '{"is_disabled":true}',
            ];
        if ($buff) {
            $params['reply_markup'] = json_encode($keyboard);
        }
        $response = $tg->send_api_command('sendMessage', $params);
    } else if (isset($data->message->successful_payment)) {
        http_response_code(200);
        echo "OK";
        die;
    } else if ($text && $userid && $record->laststep == 'get_text') {
        if ($record->lastmsgid) {
            $tg->send_api_command(
                'deleteMessage',
                [
                'chat_id' => $fromid,
                'message_id' => $record->lastmsgid,
                ]
            );
        }

        $keyboard = [
        'inline_keyboard' => [[
        [
            'text' => '✉️ ' . get_string('submit'),
            'callback_data' => $record->lastdata . ' 1',
        ],
        [
            'text' => '❌ ' . get_string('cancel'),
            'callback_data' => $record->lastdata . ' 0',
        ],
        ]],
        ];
        $response = $tg->send_api_command(
            'sendMessage',
            [
            'chat_id' => $fromid,
            'text' => $text,
            'parse_mode' => 'HTML',
            'link_preview_options' => '{"is_disabled":true}',
            'reply_markup' => json_encode($keyboard),
            ]
        );
        $step = 'get_text';
        $lastmsgid = $response->result->message_id;
        $lastdata = $record->lastdata;
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

    if ($record) {
        $record->lastmsgid    = $lastmsgid;
        $record->lastdata     = $lastdata;
        $record->laststep     = $step;
        $record->timemodified = time();
        $DB->update_record('message_telegram', $record);
    } else {
        $record = new stdClass();
        $record->chatid       = $chatid;
        $record->lastmsgid    = $lastmsgid;
        $record->lastdata     = $lastdata;
        $record->laststep     = $step;
        $record->timemodified = time();
        $DB->insert_record('message_telegram', $record);
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

    $record = $DB->get_record('message_telegram', ['chatid' => $chatid]);
    $lastmsgid = clean_param($data->callback_query->message->message_id, PARAM_TEXT);
    $lastdata = clean_param($data->callback_query->data, PARAM_TEXT);
    $step = 'callback';

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
        'ru' => ['flag' => '🇷🇺'],
        'en' => ['flag' => '🇺🇸'],
        'be' => ['flag' => '🇧🇾'],
        'uk' => ['flag' => '🇺🇦'],
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
                'text' => ($languages[$lang]['flag'] ?? 'Ⓜ️'),
                ]
            );
            $user = new stdClass();
            $user->id = $userid;
            $user->lang = $lang;
            user_update_user($user, false, true);
        }
    } else if (strpos($data->callback_query->data, '/message') === 0 && $userid) {
        preg_match('/^\/message(?: (\d+))?(?: (\d+))?(?: (\d+))?/', $data->callback_query->data, $matches);
        $courseid = isset($matches[1]) ? (int)$matches[1] : null;
        $groupid  = isset($matches[2]) ? (int)$matches[2] : null;
        $submit   = isset($matches[3]) ? (int)$matches[3] : null;

        $keyboard = ['inline_keyboard' => []];

        $notify = false;

        $params = [
            'chat_id' => $chatid,
        ];

        if ($submit === 0) {
            $step = 'cancel';
            $params['text'] = '❎ ' . get_string('cancelled');
        } else if ($submit === 1) {
            $step = 'done';
            $params['text'] = '✅ ' . get_string('sent');
            $notify = true;
        } else if ($groupid === 0 || !empty($groupid)) {
            $params['text'] = get_string('botentertext', 'message_telegram');
            $lastmsgid = null;
            $step = 'get_text';
        } else if ($courseid) {
            $context = context_course::instance($courseid);
            $groups = groups_get_all_groups($courseid, $userid);
            $hasrole = false;
            foreach (explode(',', $config->sitebotmsgroles) as $roleid) {
                if (user_has_role_assignment($userid, $roleid, $context->id)) {
                    $hasrole = true;
                    break;
                }
            }
            if ($hasrole) {
                foreach ($groups as $group) {
                    $keyboard['inline_keyboard'][] = [[
                    'text' => $group->name,
                    'callback_data' => "/message {$courseid} {$group->id}",
                    ]];
                }
                if (!$groups) {
                    $keyboard['inline_keyboard'][] = [[
                    'text' => get_string('botmsgall', 'message_telegram'),
                    'callback_data' => "/message {$courseid} 0",
                    ]];
                }
                $params['text'] = '📖 ' . get_string('selectagroup');
                $params['reply_markup'] = json_encode($keyboard);
            } else {
                $params['text'] = '🙅 ' . get_string('no');
            }
        }

        $params['message_id'] = $data->callback_query->message->message_id;
        $response = $tg->send_api_command('editMessageText', $params);
        if ($notify) {
            notify_users($courseid, $groupid, $userid, $data->callback_query->message->text);
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
                    'caption' => get_string('botcertyour', 'message_telegram'),
                    ]);
            }
        } else {
            $keyboard = ['inline_keyboard' => []];
            foreach ($certs as $cert) {
                $keyboard['inline_keyboard'][] = [
                ['text' => $cert['name'] . ' - ' . $cert['date'], 'callback_data' => '/getcert ' . $cert['code']],
                ];
            }

            $response = $tg->send_api_command('editMessageText', [
            'chat_id' => $chatid,
            'message_id' => $data->callback_query->message->message_id,
            'text' => get_string('botcertselect', 'message_telegram'),
            'reply_markup' => json_encode($keyboard),
            ]);
        }
    } else if (strpos($data->callback_query->data, '/userid') === 0 && $id = substr($data->callback_query->data, 8)) {
        $userid = $userids[0];
        $uid = clean_param($id, PARAM_INT);
        if ($userid && $uid) {
            set_user_preference('message_processor_telegram_prefid', $uid, $userid);
            $response = $tg->send_api_command(
                'sendMessage',
                [
                'chat_id' => $fromid,
                'text' => '✅ ' . get_string('bulkselection', 'core', '🆔 ' . $uid),
                ]
            );
        }
    }
    if ($record) {
        $record->lastmsgid    = $lastmsgid;
        $record->lastdata     = $lastdata;
        $record->laststep     = $step;
        $record->timemodified = time();
        $DB->update_record('message_telegram', $record);
    } else {
        $record = new stdClass();
        $record->chatid       = $chatid;
        $record->lastmsgid    = $lastmsgid;
        $record->lastdata     = $lastdata;
        $record->laststep     = $step;
        $record->timemodified = time();
        $DB->insert_record('message_telegram', $record);
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
