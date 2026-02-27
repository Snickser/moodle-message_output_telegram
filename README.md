## Impruved Telegram message output plugin for Moodle.

[![](https://img.shields.io/github/v/release/Snickser/moodle-message_output_telegram.svg)](https://github.com/Snickser/moodle-message_output_telegram/releases)
[![Build Status](https://github.com/Snickser/moodle-message_output_telegram/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/Snickser/moodle-message_output_telegram/actions/workflows/moodle-ci.yml)

This plugin provides Moodle messaging provider for Telegram.

- Delayed sending function (queue) when the rate-limit is reached
- Automatic disabling of the user's subscription when the user blocks the bot.
- If there is an additional custom profile field "telegram_username", it will be filled in automatically.
- Webhook mode (experimental!!)
- BotMode functional (info, courses list, events, donate, etc.).
- Mistral.AI integration (chat and voice transcribing).

---
![изображение](https://github.com/user-attachments/assets/8d2504a5-064f-4b35-8e3a-de8bc93fc8e8)

---
<img width="1130" height="813" alt="изображение" src="https://github.com/user-attachments/assets/b9bed7e8-e688-40bf-aed6-2d9fc875d147" />


---

If you have an earlier version than BETA 3.2.4 uninstall and delete it, and start over with 3.2.4 or higher!


For Admins:
See the setup documentation here - https://docs.moodle.org/33/en/Telegram_message_processor

For Users:
See the preferences setup documentation here - https://docs.moodle.org/33/en/Telegram_message_processor#Configuring_user_preferences
