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
 * recurrent payments
 *
 * @package    message_telegram
 * @copyright  2024 Alex Orlov <snicker@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace message_telegram\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Default tasks.
 *
 * @package    paygw_robokassa
 * @copyright  2024 Alex Orlov <snicker@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class telegram_sender extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'message_telegram');
    }
    /**
     * Execute.
     */
    public function execute() {
        global $DB, $CFG;

        $token = get_config('message_telegram', 'sitebottoken');
        $pmode = get_config('message_telegram', 'parsemode');

        $dir = $CFG->dataroot . '/telegram';

        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if ($file == '..' or $file == '.') {
                    continue;
                }
                $fh = fopen($dir . '/' . $file, "r");
                $chatid = '';
                $chatid = fgets($fh);
                $buff = '';
                $text = '';
                while (($buff = fgets($fh)) !== false) {
                    $text .= $buff;
                }
                $ret = $this->sendmsg($token, $pmode, $chatid, $text, $dir . '/' . $file);
                usleep(50000);
            }
            closedir($dh);
        } else {
            mkdir($dir);
        }
    }

    private function sendmsg($token, $pmode, $chatid, $text, $file) {

            $this->curl = new \curl();

            $location = 'https://api.telegram.org/bot' . $token . '/sendMessage';

            $params = [
             'chat_id' => $chatid,
             'parse_mode' => $pmode,
             'text' => $text,
             'link_preview_options' => '{"is_disabled":true}',
            ];

            $options = [
             'CURLOPT_RETURNTRANSFER' => true,
             'CURLOPT_TIMEOUT' => 30,
             'CURLOPT_HTTP_VERSION' => CURL_HTTP_VERSION_1_1,
             'CURLOPT_SSLVERSION' => CURL_SSLVERSION_TLSv1_2,
            ];

            $response = json_decode($this->curl->post($location, $params, $options));

            if (!empty($this->curl->errno)) {
                mtrace($this->curl->error);
                return;
            }

            if ($response->ok == true) {
                $buff = $response->result->message_id;
                unlink($file);
            } else {
                $buff = $response->error_code . " " . $response->description;
            }
            mtrace($buff);
    }
}
