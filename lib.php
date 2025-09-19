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
 * Telegram message plugin version information.
 *
 * @package message_telegram
 * @author  Mike Churchward
 * @copyright  2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds navigation items to user profile.
 *
 * @param stdClass $navigation
 * @param stdClass $user The user object
 * @param stdClass $context
 */
function message_telegram_extend_navigation_user_settings($navigation, $user, $context) {
    global $USER;

    if ($USER->id !== $user->id) {
        return;
    }

    $manager = new \message_telegram\manager();
    $chatid = $manager->is_chatid_set($USER->id);

    $url = new moodle_url('/message/notificationpreferences.php');

    if ($chatid) {
        $navigation->add(
            get_string('alreadyconnected', 'message_telegram'),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'telegram_connected'
        );
    } else {
        $botname = get_config('message_telegram', 'sitebotname');
        if (empty($botname)) {
            return;
        }

        if ($manager->config('webhook')) {
            $key = $manager->set_usersecret($USER->id);
            $url = 'https://t.me/' . $manager->config('sitebotusername') . '?start=' . $key;
        }

        $navigation->add(
            get_string('connectmemenu', 'message_telegram'),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'telegram_disconnected'
        );
    }
}
