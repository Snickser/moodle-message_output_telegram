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
 * @copyright   2025 Alex Orlov <snickser@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['alreadyconnected'] = '✅ Ваш Телеграм аккаунт подключен';
$string['askcleared'] = '🗑️ История переписки с ИИ очищена';
$string['asknoquestion'] = '❓ Введите ваш вопрос после команды /ask

/ask Привет, как дела?';
$string['botanswer1'] = '🤔 Ответил бы в привате, но мы пока не знакомы ☺ ️';
$string['botanswer2'] = '👍 Ответил в приват';
$string['botask'] = '/ask - задать вопрос ИИ-ассистенту';
$string['botcertdownload'] = '📥 Скачать';
$string['botcertificates'] = '/certificates - выданные сертификаты';
$string['botcerts'] = '📜 <b>Ваши сертификаты</b>

';
$string['botcertselect'] = '📥 Выберите сертификат';
$string['botcertyour'] = '💾 Ваш сертификат';
$string['botclear'] = '/clear - очистить историю переписки с ИИ';
$string['botenrols'] = '🎓 <b>Участие в курсах</b>';
$string['botentertext'] = '✏  Введите текст сообщения';
$string['botevents'] = '🗓 <b>Предстоящие события</b>

';
$string['botfaq'] = '⁉️ Часто задаваемые вопросы:';
$string['botfaqtext'] = '';
$string['bothelp'] = '👓 Подсказки
/info - информация о платформе
/lang - переключение языка
/faq - часто задаваемые вопросы
/courses - список всех курсов
/enrols - участие в курсах
/events - предстоящие события
/progress - статус элементов курса';
$string['bothelp_anonymous'] = '👓 Подсказки
/info - информация о платформе
/faq  - часто задаваемые вопросы';
$string['botidontknow'] = 'Не знаю что это такое 🤷🏻 /help';
$string['botlang'] = '🈯 Выбрать язык ({$a})';
$string['botmessagehelp'] = '/message - отправить групповое сообщение';
$string['botmsgall'] = '🔺 Всем студентам курса';
$string['botpay'] = '🏦 Выберите сумму {$a}';
$string['botpaydesc'] = 'На поддержание учебной платформы';
$string['botpaytitle'] = '🕉 Пожертвование 🕉';
$string['botuserid'] = '👑 Пользователь 🆔 {$a}';
$string['botuseridhelp'] = '/userid - сменить пользователя';
$string['configsitebotname'] = 'Будет заполнено автоматически, когда Вы сохраните токен бота.';
$string['configsitebottoken'] = 'Введите сюда токен бота сайта, полученный от Botfather.';
$string['configsitebotusername'] = 'Будет заполнено автоматически, когда Вы сохраните токен бота.';
$string['connectinstructions'] = 'После того, как вы нажмёте ссылку ниже, вам нужно будет разрешить открытие ссылки в Telegram с вашей учётной записью Telegram. В Telegram нажмите кнопку «Start» в открывшемся чате «{$a}», чтобы подключить свою учётную запись.<br>После завершения вернитесь на эту страницу и нажмите «Save changes (Сохранить изменения)». Полную инструкцию <a href="https://docs.moodle.org/33/en/Telegram_message_processor#Configuring_user_preferences" target="_blank">читаем здесь</a>.';
$string['connectme'] = '<br><p style="color: blue;"><font size=+1><b>👉 Подключить свой аккаунт к Телеграм 👈</b></font></p>';
$string['connectmemenu'] = '⚠️ Подключить свой аккаунт к Telegram';
$string['enter'] = 'Введите';
$string['enter_phone'] = 'Для лучшего взаимодействия с кураторами, пожалуйста, укажите номер своего мобильного телефона в профиле на портале, или нажмите кнопку внизу.';
$string['enter_time'] = 'Дата и время указываются в мнемоническом или в одном из стандартных форматов, например: YY-MM-DD HH:MM';
$string['firstregister'] = 'Сначала зарегистрируйтесь на сайте, и подключите уведомления через Телеграм. {$a}';
$string['mistralapikey'] = 'API ключ Mistral';
$string['mistralapikey_desc'] = 'Получите API ключ на https://console.mistral.ai/';
$string['mistralconnectionerror'] = 'Ошибка подключения к Mistral AI';
$string['mistralconnectionok'] = 'Подключение к Mistral AI успешно установлено';
$string['mistralerror'] = 'Ошибка при получении ответа от AI';
$string['mistralmodel'] = 'Модель Mistral';
$string['mistralmodel_desc'] = 'Например: mistral-small-latest, mistral-medium-latest, mistral-large-latest';
$string['mistralnotconfigured'] = '❌ ИИ-ассистент не настроен';
$string['mistralprompt'] = 'Системный промпт';
$string['mistralprompt_default'] = 'Вы — полезный ассистент образовательной платформы Moodle. Отвечайте на вопросы пользователей кратко и по делу.';
$string['mistralprompt_desc'] = 'Инструкция для AI-ассистента';
$string['mistralsettings'] = 'Mistral AI (чат-бот)';
$string['mistralsettings_desc'] = 'Настройки интеграции с Mistral AI для ответов на вопросы пользователей через команду /ask';
$string['notconfigured'] = 'Сервер Telegram не настроен, поэтому сообщения Telegram не могут быть отправлены';
$string['pluginname'] = 'Telegram';
$string['provide'] = '☎️ Отправить номер телефона';
$string['provide_help'] = 'Нажмите кнопку';
$string['removetelegram'] = 'Отключиться от Telegram';
$string['reportenabler'] = 'Включить отчёт о персональных данных пользователей';
$string['reportenabler_desc1'] = '<font color=red>Обратите внимание, что персональные данные пользователей передаются на сторонние серверы Telegram, это может нарушать законы вашей страны.</font>';
$string['reportenabler_desc2'] = 'Эта опция позволяет выбранным ролям просматривать персональные данные студентов курса.';
$string['setupinstructions'] = 'Создайте новый бот Telegram, используя Botfather. Перейдите по ссылке Botfather ниже и откройте Telegram.
Используйте команду "/newbot" в Telegram для начала создания бота. Вам надо будет задать название бота, например "{$a->name}" и уникальное имя бота, например "{$a->username}". Полное описание <a href="https://docs.moodle.org/33/en/Telegram_message_processor" target="_blank">читаем здесь</a>.';
$string['sitebotname'] = 'Название бота для сайта';
$string['sitebottoken'] = 'Токен бота для сайта';
$string['sitebottokennotsetup'] = 'Токен бота для сайта должен быть указан в настройках плагина.';
$string['sitebotusername'] = 'Ник бота для сайта';
$string['telegrambottoken'] = 'Токен бота Telegram';
$string['telegramchatid'] = 'ID чата Telegram';
$string['waitai'] = '⏳ Готовлю ответ...';
$string['warnreport_desc'] = 'Выводить предупреждение перед выдачей отчёта.';
$string['welcome'] = '✅ Ваш аккаунт успешно подключен!';
