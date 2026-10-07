<?php

namespace Atwx\HtmlFieldCleaner\Tests;

use Atwx\HtmlFieldCleaner\Tests\HtmlFieldCleanerExtensionTest\TestObject;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

class HtmlFieldCleanerExtensionTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        TestObject::class,
    ];

    protected $usesDatabase = true;

    public function testCleansHtmlFieldsOnWrite(): void
    {
        $object = TestObject::create([
            'Title' => '<span style="x">Plain</span>',
            'Content' => '<p style="x"><span>Text</span></p>',
            'Embed' => '<div style="x"><span>Embed</span></div>',
            'Teaser' => '<p style="color: red"><span>Teaser</span></p>',
        ]);
        $object->write();

        $this->assertSame('<span style="x">Plain</span>', $object->Title, 'Non-HTML fields stay untouched');
        $this->assertSame('<p>Text</p>', $object->Content);
        $this->assertSame('<div style="x"><span>Embed</span></div>', $object->Embed, 'Skipped field stays untouched');
        $this->assertSame('<p style="color: red">Teaser</p>', $object->Teaser, 'Field overrides apply');
    }

    public function testCanBeDisabledPerClass(): void
    {
        Config::modify()->set(TestObject::class, 'html_cleaner_enabled', false);

        $object = TestObject::create(['Content' => '<p style="x">Text</p>']);
        $object->write();

        $this->assertSame('<p style="x">Text</p>', $object->Content);
    }
}
