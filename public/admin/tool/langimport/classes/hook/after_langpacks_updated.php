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

namespace tool_langimport\hook;

/**
 * Hook dispatched after language packs have been updated, before the string cache is purged.
 *
 * This hook allows plugins to perform actions after language pack updates
 * are completed but before the language string cache is reset. It provides
 * structured data about the update outcome: which packs were requested,
 * which were successfully updated, and any errors that occurred.
 *
 * @package    tool_langimport
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Allows plugins to perform actions after language packs are updated, before the string cache is purged.')]
#[\core\attribute\tags('language', 'langimport')]
class after_langpacks_updated {
    /**
     * Constructor for the hook.
     *
     * @param array $updatedlangs Array of language codes that were successfully updated.
     * @param array $requestedlangs Array of all language codes that were requested for update.
     * @param array $errors Array of error messages for failed updates.
     */
    public function __construct(
        /** @var array Array of language codes that were successfully updated */
        public readonly array $updatedlangs,
        /** @var array Array of all language codes that were requested for update */
        public readonly array $requestedlangs = [],
        /** @var array Array of error messages for failed updates */
        public readonly array $errors = [],
    ) {
    }
}
