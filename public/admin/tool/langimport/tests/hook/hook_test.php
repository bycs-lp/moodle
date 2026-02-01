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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/componentlib.class.php');

/**
 * Tests for the after_langpacks_updated hook.
 *
 * @package    tool_langimport
 * @category   test
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_test extends \advanced_testcase {
    /**
     * Test the after_langpacks_updated hook: construction, data integrity, and dispatching.
     *
     * @covers \tool_langimport\hook\after_langpacks_updated
     */
    public function test_after_langpacks_updated(): void {
        // Verify construction with all parameters.
        $updated = ['de', 'fr'];
        $requested = ['de', 'fr', 'es'];
        $errors = ['Download error for es'];
        $hook = new after_langpacks_updated($updated, $requested, $errors);
        $this->assertInstanceOf(after_langpacks_updated::class, $hook);
        $this->assertSame($updated, $hook->updatedlangs);
        $this->assertSame($requested, $hook->requestedlangs);
        $this->assertSame($errors, $hook->errors);

        // Verify construction with defaults for optional parameters.
        $simplehook = new after_langpacks_updated(['de']);
        $this->assertSame(['de'], $simplehook->updatedlangs);
        $this->assertSame([], $simplehook->requestedlangs);
        $this->assertSame([], $simplehook->errors);

        // Verify construction with empty array.
        $emptyhook = new after_langpacks_updated([]);
        $this->assertSame([], $emptyhook->updatedlangs);

        // Verify the hook is correctly dispatched and received.
        $count = 0;
        $receivedhook = null;
        $this->redirectHook(
            after_langpacks_updated::class,
            function (after_langpacks_updated $hook) use (&$receivedhook, &$count): void {
                $count++;
                $receivedhook = $hook;
            }
        );

        $dispatched = new after_langpacks_updated(['de', 'fr'], ['de', 'fr', 'es'], ['Error for es']);
        \core\hook\manager::get_instance()->dispatch($dispatched);

        $this->assertSame(1, $count);
        $this->assertSame($dispatched, $receivedhook);
        $this->assertSame(['de', 'fr'], $receivedhook->updatedlangs);
        $this->assertSame(['de', 'fr', 'es'], $receivedhook->requestedlangs);
        $this->assertSame(['Error for es'], $receivedhook->errors);
    }

    /**
     * Test that update_all_installed_languages dispatches the after hook correctly
     * with structured data when the lang_installer returns a partial failure.
     *
     * The lang_installer mock returns RESULT_INSTALLED for 'de' and 'fr',
     * then RESULT_DOWNLOADERROR for 'es'. The after hook should contain
     * the successfully updated languages, all requested languages, and
     * the error messages.
     *
     * @covers \tool_langimport\hook\after_langpacks_updated
     */
    public function test_hook_dispatched_on_partial_failure(): void {
        global $CFG;
        $this->resetAfterTest();

        // Set up hook capture.
        $receivedafter = null;
        $this->redirectHook(
            after_langpacks_updated::class,
            function (after_langpacks_updated $hook) use (&$receivedafter): void {
                $receivedafter = $hook;
            }
        );

        // Mock the lang_installer to control remote language list and install results.
        $installer = $this->createMock(\lang_installer::class);
        $installer->method('get_remote_list_of_languages')
            ->willReturn([
                ['de', 'abc123'],
                ['fr', 'def456'],
                ['es', 'ghi789'],
            ]);
        // Simulate: 'de' and 'fr' install successfully, 'es' fails with download error.
        $installer->method('run')
            ->willReturn([
                'de' => \lang_installer::RESULT_INSTALLED,
                'fr' => \lang_installer::RESULT_INSTALLED,
                'es' => \lang_installer::RESULT_DOWNLOADERROR,
            ]);
        $installer->method('lang_pack_url')
            ->willReturn('https://download.moodle.org/langpack/test/es.zip');

        // Register the mock installer in the DI container so the controller picks it up.
        \core\di::set(\lang_installer::class, $installer);
        $controller = new \tool_langimport\controller();

        // Create fake lang directories so get_list_of_translations recognises them.
        foreach (['de', 'fr', 'es'] as $lang) {
            $dir = $CFG->dataroot . '/lang/' . $lang;
            make_writable_directory($dir);
            file_put_contents($dir . '/langconfig.php', '<?php $string["thislanguage"] = "' . $lang . '";');
        }
        get_string_manager()->reset_caches();

        // Call the REAL update_all_installed_languages which dispatches the hook.
        $controller->update_all_installed_languages();

        // After hook should have structured update data.
        $this->assertNotNull($receivedafter);
        $this->assertSame(['de', 'fr'], $receivedafter->updatedlangs);
        $this->assertSame(['de', 'es', 'fr'], $receivedafter->requestedlangs);
        $this->assertNotEmpty($receivedafter->errors);

        // The controller should have recorded an error from the download failure.
        $this->assertNotEmpty($controller->errors);
    }
}
