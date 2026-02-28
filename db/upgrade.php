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

/**
 * Upgrade script for message_telegram
 *
 * @param int $oldversion
 * @return boolean
 */
function xmldb_message_telegram_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 3025092005) {
        $table = new xmldb_table('message_telegram');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('chatid', XMLDB_TYPE_INTEGER, '20', null, null, null);
        $table->add_field('lastdata', XMLDB_TYPE_TEXT, null, null, null, null);
        $table->add_field('laststep', XMLDB_TYPE_CHAR, '50', null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 3025092005, 'message', 'telegram');
    }

    if ($oldversion < 3025092106) {
        $table = new xmldb_table('message_telegram');

        $field = new xmldb_field('lastmsgid', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'chatid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 3025092106, 'message', 'telegram');
    }

    if ($oldversion < 3026022616) {
        // Define table message_telegram_mistral to be created.
        $table = new xmldb_table('message_telegram_mistral');

        // Adding fields to table message_telegram_mistral.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('isuser', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table message_telegram_mistral.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Adding indexes to table message_telegram_mistral.
        $table->add_index('userid-timecreated', XMLDB_INDEX_NOTUNIQUE, ['userid', 'timecreated']);

        // Conditionally launch create table for message_telegram_mistral.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Telegram savepoint reached.
        upgrade_plugin_savepoint(true, 3026022616, 'message', 'telegram');
    }

    if ($oldversion < 3026022720) {
        // Define table message_telegram_openrouter to be created.
        $table = new xmldb_table('message_telegram_openrouter');

        // Adding fields to table message_telegram_openrouter.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('isuser', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table message_telegram_openrouter.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Adding indexes to table message_telegram_openrouter.
        $table->add_index('userid-timecreated', XMLDB_INDEX_NOTUNIQUE, ['userid', 'timecreated']);

        // Conditionally launch create table for message_telegram_openrouter.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Telegram savepoint reached.
        upgrade_plugin_savepoint(true, 3026022720, 'message', 'telegram');
    }

    return true;
}
