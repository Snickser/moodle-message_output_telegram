<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Telegram message plugin settings.
 *
 * @package message_telegram
 * @author  Mike Churchward
 * @copyright  2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'message_telegram_head',
        null,
        get_string('customfield', 'message_telegram')
    ));

    $telegrammanager = new message_telegram\manager();

    $sitebottoken = $telegrammanager->config('sitebottoken');
    $sitebotsecret = $telegrammanager->config('sitebotsecret');
    $botname = $telegrammanager->config('sitebotname');
    $botusername = $telegrammanager->config('sitebotusername');

    if (empty($sitebotsecret)) {
        $sitebotsecret = bin2hex(random_bytes(32));
        set_config('sitebotsecret', $sitebotsecret, 'message_telegram');
    }

    if (!empty($sitebottoken)) {
        $telegrammanager->update_bot_info();
    }


    $telegrammanager = new message_telegram\manager();
    if (empty($sitebottoken)) {
        $site = get_site();
        $uniquename = $site->fullname . ' ' . get_string('notifications');
        $sitehostname = parse_url($CFG->wwwroot, PHP_URL_HOST);
        $lastdot = strrpos($sitehostname, '.');
        if ($lastdot !== false) {
            $sitehostname = substr($sitehostname, 0, $lastdot);
        }
        $botusername = strrchr($sitehostname, '.');
        if ($botusername === false) {
            $botusername = $sitehostname;
        } else {
            $botusername = str_replace('.', '', $botusername);
        }
        // The username cannot be longer than 32 characters total, and must end in "bot".
        $botusername = substr($botusername, 0, 29) . 'Bot';

        $url = 'https://telegram.me/botfather';
        $link = '<p><a href="' . $url . '" target="_blank">' . $url . '</a></p>';
        $a = new stdClass();
        $a->name = $uniquename;
        $a->username = $botusername;
        $text = get_string('setupinstructions', 'message_telegram', $a);
        $settings->add(new admin_setting_heading('setuptelegram', '', $text . $link));
    }

    $settings->add(new admin_setting_configtext(
        'message_telegram/sitebottoken',
        get_string('sitebottoken', 'message_telegram'),
        get_string('configsitebottoken', 'message_telegram'),
        null,
        PARAM_TEXT
    ));

    $url = new moodle_url('/message/output/telegram/telegramconnect.php', ['sesskey' => sesskey(), 'action' => 'setwebhook']);
    $link = html_writer::tag(
        'a',
        get_string('setwebhook', 'message_telegram'),
        ['href' => $url, 'class' => 'btn btn-danger']
    );

    $setting = new admin_setting_configcheckbox(
        'message_telegram/webhook',
        get_string('telegramwebhook', 'message_telegram'),
        $link . '<br>' . get_string('configtelegramwebhook', 'message_telegram'),
        false
    );
    $settings->add($setting);

    $settings->add(new admin_setting_configtext(
        'message_telegram/sitebotsecret',
        get_string('sitebotsecret', 'message_telegram'),
        get_string('configsitebotsecret', 'message_telegram'),
        null,
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'message_telegram/sitebotpay',
        get_string('sitebotpay', 'message_telegram'),
        get_string('configsitebotpay', 'message_telegram'),
        null,
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'message_telegram/sitebotpaycosts',
        get_string('sitebotpaycosts', 'message_telegram'),
        get_string('configsitebotpaycosts', 'message_telegram'),
        '800,1600,5000',
        PARAM_TEXT
    ));

    $currencies = [
    'RUB' => get_string('RUB', 'currencies'),
    'USD' => get_string('USD', 'currencies'),
    'EUR' => get_string('EUR', 'currencies'),
];
    $settings->add(new admin_setting_configselect(
        'message_telegram/sitebotpaycurrency',
        get_string('currency'),
        null,
        'RUB',
        $currencies
    ));

    $settings->add(new admin_setting_configtext(
        'message_telegram/sitebotname',
        get_string('sitebotname', 'message_telegram'),
        get_string('configsitebotname', 'message_telegram'),
        null,
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'message_telegram/sitebotusername',
        get_string('sitebotusername', 'message_telegram'),
        get_string('configsitebotusername', 'message_telegram'),
        null,
        PARAM_TEXT
    ));

    $options = ['' => get_string('parse_text', 'message_telegram'), 'HTML' => get_string('parse_html', 'message_telegram')];
    $settings->add(new admin_setting_configselect(
        'message_telegram/parsemode',
        get_string('parsemode', 'message_telegram'),
        get_string('configparsemode', 'message_telegram'),
        '',
        $options
    ));

    $settings->add(new admin_setting_configcheckbox(
        'message_telegram/striptags',
        get_string('striptags', 'message_telegram'),
        get_string('configstriptags', 'message_telegram'),
        true
    ));

    $settings->add(new admin_setting_configcheckbox(
        'message_telegram/fullmessagehtml',
        get_string('fullmessagehtml', 'message_telegram'),
        get_string('configfullmessagehtml', 'message_telegram'),
        false
    ));

    $settings->add(new admin_setting_configcheckbox(
        'message_telegram/telegramlog',
        get_string('telegramlog', 'message_telegram'),
        get_string('configtelegramlog', 'message_telegram'),
        false
    ));

    $settings->add(new admin_setting_configcheckbox(
        'message_telegram/telegramlogdump',
        get_string('telegramlogdump', 'message_telegram'),
        get_string('configtelegramlogdump', 'message_telegram'),
        false
    ));

    $settings->add(new admin_setting_configexecutable(
        'message_telegram/tgext',
        get_string('tgext', 'message_telegram'),
        get_string('configtgext', 'message_telegram'),
        '',
        PARAM_TEXT
    ));


    $plugininfo = \core_plugin_manager::instance()->get_plugin_info('message_telegram');
    $donate = get_string('donate', 'message_telegram', $plugininfo);

    $settings->add(new admin_setting_heading(
        'message_telegram',
        '',
        $donate,
    ));
}
