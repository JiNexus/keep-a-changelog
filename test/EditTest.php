<?php
/**
 * @see       https://github.com/phly/keep-a-changelog-tagger for the canonical source repository
 * @copyright Copyright (c) 2018 Matthew Weier O'Phinney
 * @license   https://github.com/phly/keep-a-changelog-tagger/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace PhlyTest\KeepAChangelog;

use Phly\KeepAChangelog\Edit;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Throwable;

class EditTest extends TestCase
{
    private $tempFile;

    public function tearDown()
    {
        if ($this->tempFile && file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function reflectMethod(Edit $command, string $method) : ReflectionMethod
    {
        $r = new ReflectionMethod($command, $method);
        $r->setAccessible(true);
        return $r;
    }

    public function getSampleContents() : string
    {
        return <<< 'EOH'
## 2.0.0 - TBD

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.


EOH;
    }

    public function testGetChangelogEntryReturnsNullIfNoChangelogEntryFound()
    {
        $edit = new Edit();
        $getChangelogEntry = $this->reflectMethod($edit, 'getChangelogEntry');
        $this->assertNull($getChangelogEntry->invoke($edit, __DIR__ . '/_files/invalid-composer/composer.json'));
    }

    public function testGetChangelogEntryReturnsExpectedDataWhenChangelogIsDiscovered()
    {
        $expected = $this->getSampleContents();
        $edit = new Edit();
        $getChangelogEntry = $this->reflectMethod($edit, 'getChangelogEntry');
        $data = $getChangelogEntry->invoke($edit, __DIR__ . '/_files/CHANGELOG.md');

        $this->assertEquals(4, $data->index);
        $this->assertEquals(22, $data->length);
        $this->assertEquals($expected, $data->contents);
    }

    public function testUsesSystemEditorIfPresentInEnv()
    {
        if (! getenv('EDITOR')) {
            putenv('EDITOR=system-editor');
        }

        $edit = new Edit();
        $discoverEditor = $this->reflectMethod($edit, 'discoverEditor');
        $this->assertSame(getenv('EDITOR'), $discoverEditor->invoke($edit));
    }

    public function testUsesDefaultKnownEditorIfNoEditorPresentInEnv()
    {
        if (getenv('EDITOR')) {
            putenv('EDITOR');
        }

        $edit = new Edit();
        $discoverEditor = $this->reflectMethod($edit, 'discoverEditor');
        $editor = $discoverEditor->invoke($edit);
        $this->assertTrue(in_array($editor, ['notepad', 'vi'], true));
    }

    public function testUpdateChangelogEntryReplacesExistingContents()
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'KAC');
        file_put_contents($this->tempFile, file_get_contents(__DIR__ . '/_files/CHANGELOG.md'));

        $expectedContents = <<< 'EOH'
## 2.0.0 - 2018-04-14

### Added

- Everything that needed adding.

### Changed

- Only what we have the wisdom to.

### Deprecated

- Those things we do not need anymore.

### Removed

- The things we previously decided we didn't need.

### Fixed

- The problems.


EOH;
        $tempFile = tempnam(sys_get_temp_dir(), 'CAK');
        file_put_contents($tempFile, $expectedContents);

        $edit = new Edit();
        $updateChangelogEntry = $this->reflectMethod($edit, 'updateChangelogEntry');
        $updateChangelogEntry->invoke($edit, $this->tempFile, $tempFile, 4, 22);

        $this->assertFileEquals(__DIR__ . '/_files/CHANGELOG-EDIT-EXPECTED.md', $this->tempFile);
    }
}
