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
 * Strings for component 'message_telegram', language 'ru', version '4.3'.
 *
 * @package     message_telegram
 * @category    string
 * @copyright   2024 Alex Orlov <snickser@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['configsitebotname'] = 'Будет заполнено автоматически, когда Вы сохраните токен бота.';
$string['configsitebottoken'] = 'Введите сюда токен бота сайта, полученный от Botfather.';
$string['configsitebotusername'] = 'Будет заполнено автоматически, когда Вы сохраните токен бота.';
$string['notconfigured'] = 'Сервер Telegram не настроен, поэтому сообщения Telegram не могут быть отправлены';
$string['pluginname'] = 'Telegram';
$string['removetelegram'] = 'Отключиться от Telegram';
$string['setupinstructions'] = 'Создайте новый бот Telegram, используя Botfather. Перейдите по ссылке Botfather ниже и откройте Telegram.
Используйте команду "/newbot" в Telegram для начала создания бота. Вам надо будет задать название бота, например "{$a->name}" и уникальное имя бота, например "{$a->username}". Полное описание <a href="https://docs.moodle.org/33/en/Telegram_message_processor" target="_blank">читаем здесь</a>.';
$string['sitebotname'] = 'Название бота для сайта';
$string['sitebottoken'] = 'Токен бота для сайта';
$string['sitebottokennotsetup'] = 'Токен бота для сайта должен быть указан в настройках плагина.';
$string['sitebotusername'] = 'Ник бота для сайта';
$string['telegrambottoken'] = 'Токен бота Telegram';
$string['telegramchatid'] = 'ID чата Telegram';
$string['connectinstructions'] = 'После того, как вы нажмёте ссылку ниже, вам нужно будет разрешить открытие ссылки в Telegram с вашей учётной записью Telegram. В Telegram нажмите кнопку «Start» в открывшемся чате «{$a}», чтобы подключить свою учётную запись.<br>После завершения вернитесь на эту страницу и нажмите «Save changes (Сохранить изменения)». Полную инструкцию <a href="https://docs.moodle.org/33/en/Telegram_message_processor#Configuring_user_preferences" target="_blank">читаем здесь</a>.';
$string['connectme'] = '<br><p style="color: blue;"><b>> Подключить свой аккаунт к Telegram <</b></p>';
$string['welcome'] = '✅ Ваш аккаунт Moodle успешно подключен!';
