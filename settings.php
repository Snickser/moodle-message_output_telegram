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
        '',
        '',
    ));

    $telegrammanager = new message_telegram\manager();

    $sitebottoken = $telegrammanager->config('sitebottoken');
    $sitebotsecret = $telegrammanager->config('sitebotsecret');
    $botname = $telegrammanager->config('sitebotname');
    $botusername = $telegrammanager->config('sitebotusername');
    $mistralapikey = $telegrammanager->config('mistralapikey');

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
        $parts = explode('.', $sitehostname);
        $botusername = $parts[0];

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

    $options = [
        '' => get_string('no'),
    ];
    $fields = profile_get_custom_fields();
    foreach ($fields as $f) {
        $options[$f->shortname] = format_string($f->name);
    }
    $settings->add(new admin_setting_configselect(
        'message_telegram/sitebotusernamefield',
        get_string('usernamefield', 'message_telegram'),
        get_string('customfield', 'message_telegram'),
        '',
        $options
    ));

    $settings->add(new admin_setting_heading(
        'message_telegram_webhook',
        get_string('telegramwebhook', 'message_telegram'),
        null,
    ));

    $url = new moodle_url('/message/output/telegram/telegramconnect.php', ['sesskey' => sesskey(), 'action' => 'setwebhook']);
    $link = html_writer::tag(
        'a',
        get_config('message_telegram', 'webhook') ?
        get_string('unsetwebhook', 'message_telegram') :
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
    'BYR' => get_string('BYR', 'currencies'),
    'KZT' => get_string('KZT', 'currencies'),
    'USD' => get_string('USD', 'currencies'),
    'EUR' => get_string('EUR', 'currencies'),
    'UAH' => get_string('UAH', 'currencies'),
    ];
    $settings->add(new admin_setting_configselect(
        'message_telegram/sitebotpaycurrency',
        get_string('currency'),
        null,
        'RUB',
        $currencies
    ));

    $context = context_user::instance($USER->id);
    $roles = get_default_enrol_roles($context);
    $settings->add(new admin_setting_configmultiselect(
        'message_telegram/sitebotmsgroles',
        get_string('roles'),
        get_string('configsitebotmsgroles', 'message_telegram'),
        [1, 3, 4],
        $roles
    ));

    $options = [
    0 => get_string('no'),
    1 => get_string('yes'),
    ];
    $settings->add(new admin_setting_configselect(
        'message_telegram/sitebotenablereports',
        get_string('reportenabler', 'message_telegram'),
        get_string('reportenabler_desc1', 'message_telegram') . ' ' . get_string('reportenabler_desc2', 'message_telegram'),
        0,
        $options
    ));

    $settings->add(new admin_setting_configcheckbox(
        'message_telegram/sitebotwarnreport',
        get_string('warning'),
        get_string('warnreport_desc', 'message_telegram'),
        true,
        true
    ));

    $options = [
        'email' => get_string('email'),
        'phone1' => get_string('phone1'),
        'phone2' => get_string('phone2'),
        'city' => get_string('city'),
        'country' => get_string('country'),
    ];
    $fields = profile_get_custom_fields();
    foreach ($fields as $f) {
        $options[$f->shortname] = format_string($f->name);
    }
    $settings->add(new admin_setting_configmultiselect(
        'message_telegram/sitebotreportfields',
        get_string('reportfields', 'message_telegram'),
        null,
        [],
        $options
    ));

    $options = [
    'phone1' => get_string('phone1'),
    'phone2' => get_string('phone2'),
    ];
    $fields = profile_get_custom_fields();
    foreach ($fields as $f) {
        $options['profile_field_' . $f->shortname] = format_string($f->name);
    }
    $settings->add(new admin_setting_configselect(
        'message_telegram/sitebotphonefield',
        get_string('phonefield', 'message_telegram'),
        get_string('phonefield_desc', 'message_telegram'),
        'phone2',
        $options
    ));

    $settings->add(new admin_setting_heading(
        'message_telegram_standart',
        get_string('configuration', 'core'),
        null,
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
        get_string('configtelegramlog', 'message_telegram', $CFG->tempdir),
        false
    ));

    $settings->add(new admin_setting_configcheckbox(
        'message_telegram/telegramlogdump',
        get_string('telegramlogdump', 'message_telegram'),
        get_string('configtelegramlogdump', 'message_telegram'),
        false
    ));

    $settings->add(new admin_setting_configcheckbox(
        'message_telegram/telegramwebhookdump',
        get_string('telegramwebhookdump', 'message_telegram'),
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

    // Get API keys for AI provider selection.
    $openrouterapikey = $telegrammanager->config('openrouterapikey');

    $settings->add(new admin_setting_heading(
        'message_telegram_ai',
        get_string('aiprovider', 'message_telegram'),
        null,
    ));

    $options = [
    '' => get_string('no'),
    'remote' => get_string('airemote', 'message_telegram'),
    ];
    if (!empty($openrouterapikey)) {
        $options['openrouter'] = get_string('aiprovider_openrouter', 'message_telegram');
    }
    if (!empty($mistralapikey)) {
        $options['mistral'] = get_string('aiprovider_mistral', 'message_telegram');
    }
    $settings->add(new admin_setting_configselect(
        'message_telegram/aiprovider',
        get_string('aiprovider', 'message_telegram'),
        get_string('aiprovider_desc', 'message_telegram'),
        '',
        $options
    ));

    $settings->add(new admin_setting_heading(
        'message_telegram_airemote',
        get_string('airemote', 'message_telegram'),
        null,
    ));

    $settings->add(new admin_setting_configtext(
        'message_telegram/airemoteheader',
        get_string('airemoteheader', 'message_telegram'),
        get_string('airemoteheader_desc', 'message_telegram'),
        'x-api-moodle-telegram-bot-token',
        PARAM_TEXT,
        40
    ));

    $settings->add(new admin_setting_configtext(
        'message_telegram/airemotekey',
        get_string('airemotekey', 'message_telegram'),
        get_string('airemotekey_desc', 'message_telegram'),
        '',
        PARAM_TEXT,
        40
    ));

    $settings->add(new admin_setting_configtext(
        'message_telegram/airemoteurl',
        get_string('airemoteurl', 'message_telegram'),
        get_string('airemoteurl_desc', 'message_telegram'),
        '',
        PARAM_TEXT,
        40
    ));

    $settings->add(new admin_setting_configtextarea(
        'message_telegram/airemoteprompt',
        get_string('airemoteprompt', 'message_telegram'),
        get_string('airemoteprompt_desc', 'message_telegram'),
        get_string('airemoteprompt_default', 'message_telegram'),
        PARAM_TEXT,
    ));

    $settings->add(new admin_setting_heading(
        'message_telegram_mistral',
        get_string('mistralsettings', 'message_telegram'),
        get_string('mistralsettings_desc', 'message_telegram'),
    ));

    $settings->add(new admin_setting_configtext(
        'message_telegram/mistralapikey',
        get_string('mistralapikey', 'message_telegram'),
        get_string('mistralapikey_desc', 'message_telegram'),
        '',
        PARAM_TEXT,
        40
    ));

    $options = [
    '' => get_string('default'),
    ];
    if ($mistralapikey) {
        $mistral = new \message_telegram\mistral_ai();
        $models = $mistral->get_available_models();
        $sortedmodels = [];
        foreach ($models['data'] as $key => $value) {
            if (!$value['capabilities']['completion_chat']) {
                continue;
            }
            $sortedmodels[$value['id']] = $value['id'] . ' (' . $value['description'] . ')';
        }
        // Sort models alphabetically.
        asort($sortedmodels);
        // Merge with default option first.
        $options = array_merge($options, $sortedmodels);
    }
    $settings->add(new admin_setting_configselect(
        'message_telegram/mistralmodel',
        get_string('mistralmodel', 'message_telegram'),
        get_string('mistralmodel_desc', 'message_telegram'),
        'mistral-medium-latest',
        $options
    ));

    $options = [
    '' => get_string('default'),
    ];
    if ($mistralapikey) {
        $sortedmodels = [];
        foreach ($models['data'] as $key => $value) {
            if (!$value['capabilities']['audio_transcription']) {
                continue;
            }
            $sortedmodels[$value['id']] = $value['id'] . ' (' . $value['description'] . ')';
        }
        // Sort models alphabetically.
        asort($sortedmodels);
        // Merge with default option first.
        $options = array_merge($options, $sortedmodels);
    }
    $settings->add(new admin_setting_configselect(
        'message_telegram/mistraltranscriptionmodel',
        get_string('mistraltranscriptionmodel', 'message_telegram'),
        get_string('mistraltranscriptionmodel_desc', 'message_telegram'),
        'voxtral-mini-latest',
        $options
    ));

    $settings->add(new admin_setting_configtext(
        'message_telegram/mistraltemperature',
        get_string('aitemperature', 'message_telegram'),
        get_string('aitemperature_desc', 'message_telegram'),
        0.2,
        PARAM_FLOAT,
        50
    ));

    $settings->add(new admin_setting_configtext(
        'message_telegram/mistralmaxtokens',
        get_string('aimaxtokens', 'message_telegram'),
        get_string('aimaxtokens_desc', 'message_telegram'),
        2048,
        PARAM_INT,
        10
    ));

    $settings->add(new admin_setting_configtextarea(
        'message_telegram/mistralprompt',
        get_string('mistralprompt', 'message_telegram'),
        get_string('mistralprompt_desc', 'message_telegram'),
        get_string('mistralprompt_default', 'message_telegram'),
        PARAM_TEXT,
    ));

    $settings->add(new admin_setting_heading(
        'message_telegram_openrouter',
        get_string('openroutersettings', 'message_telegram'),
        get_string('openroutersettings_desc', 'message_telegram'),
    ));

    $openrouterapikey = $telegrammanager->config('openrouterapikey');

    $settings->add(new admin_setting_configtext(
        'message_telegram/openrouterapikey',
        get_string('openrouterapikey', 'message_telegram'),
        get_string('openrouterapikey_desc', 'message_telegram'),
        '',
        PARAM_TEXT,
        40
    ));

    $options = [
    '' => get_string('default'),
    ];
    if ($openrouterapikey) {
        $openrouter = new \message_telegram\openrouter_ai();
        $models = $openrouter->get_available_models();
        $sortedmodels = [];
        foreach ($models['data'] as $key => $value) {
            $sortedmodels[$value['id']] = $value['id'];
        }
        // Sort models alphabetically.
        asort($sortedmodels);
        // Merge with default option first.
        $options = array_merge($options, $sortedmodels);
    }
    $settings->add(new admin_setting_configselect(
        'message_telegram/openroutermodel',
        get_string('openroutermodel', 'message_telegram'),
        get_string('openroutermodel_desc', 'message_telegram'),
        'meta-llama/llama-3-8b-instruct:free',
        $options
    ));

    $settings->add(new admin_setting_configtext(
        'message_telegram/openroutertemperature',
        get_string('aitemperature', 'message_telegram'),
        get_string('aitemperature_desc', 'message_telegram'),
        0.2,
        PARAM_FLOAT,
        50
    ));

    $settings->add(new admin_setting_configtext(
        'message_telegram/openroutermaxtokens',
        get_string('aimaxtokens', 'message_telegram'),
        get_string('aimaxtokens_desc', 'message_telegram'),
        2048,
        PARAM_INT,
        10
    ));

    $settings->add(new admin_setting_configtextarea(
        'message_telegram/openrouterprompt',
        get_string('openrouterprompt', 'message_telegram'),
        get_string('openrouterprompt_desc', 'message_telegram'),
        get_string('openrouterprompt_default', 'message_telegram'),
        PARAM_TEXT,
    ));

    $settings->add(new admin_setting_heading(
        'message_telegram_donate',
        ' ',
        null,
    ));

    $plugininfo = \core_plugin_manager::instance()->get_plugin_info('message_telegram');
    $donate = get_string('donate', 'message_telegram', $plugininfo);

    $settings->add(new admin_setting_heading(
        'message_telegram',
        '',
        $donate,
    ));
}
