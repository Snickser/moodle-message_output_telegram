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

$config = get_config('message_telegram');

if ($headers['X-Telegram-Bot-Api-Secret-Token'] != $config->sitebotsecret) {
    http_response_code(200);
    echo "ERROR";
    die;
}

$telegrammanager = new message_telegram\manager();

$chatid = clean_param($data->message->from->id, PARAM_INT);
$key = clean_param($data->message->text, PARAM_TEXT);
$username = clean_param($data->message->from->username, PARAM_TEXT);

$telegrammanager->set_webhook_chatid($chatid, $key, $username);

http_response_code(200);
echo "OK";
