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
 * The langimport langpacks update started event.
 *
 * @package    tool_langimport
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_langimport\event;

/**
 * The tool_langimport langpacks update started event class.
 *
 * Fired before language packs are downloaded and installed, so that
 * observers can act on the set of languages about to be updated.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - array langs: the language codes that will be updated.
 * }
 *
 * @package    tool_langimport
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class langpacks_update_started extends \core\event\base {
    /**
     * Create instance of event.
     *
     * @param array $langs Language codes that are about to be updated.
     * @return langpacks_update_started
     */
    public static function event_with_langs(array $langs): self {
        $data = [
            'context' => \context_system::instance(),
            'other' => [
                'langs' => $langs,
            ],
        ];

        return self::create($data);
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description(): string {
        $langs = implode(', ', $this->other['langs']);
        return "Language pack update started for: {$langs}.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('langpacksupdatestartedevent', 'tool_langimport');
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/admin/tool/langimport/');
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['langs'])) {
            throw new \coding_exception('The \'langs\' value must be set');
        }

        if (!is_array($this->other['langs'])) {
            throw new \coding_exception('The \'langs\' value must be an array');
        }

        foreach ($this->other['langs'] as $langcode) {
            $cleanedlang = clean_param($langcode, PARAM_SAFEDIR);
            if ($cleanedlang !== $langcode) {
                throw new \coding_exception('The \'langs\' array contains an invalid language code');
            }
        }
    }

    /**
     * No mapping required for this event because this event is not backed up.
     *
     * @return false
     */
    public static function get_other_mapping() {
        return false;
    }
}
