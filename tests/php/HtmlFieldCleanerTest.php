<?php

namespace Atwx\HtmlFieldCleaner\Tests;

use Atwx\HtmlFieldCleaner\HtmlFieldCleaner;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

class HtmlFieldCleanerTest extends SapphireTest
{
    private function clean(string $html, array $overrides = []): string
    {
        return HtmlFieldCleaner::singleton()->clean($html, $overrides);
    }

    public function testRemovesAttributesNotOnWhitelist(): void
    {
        $this->assertSame(
            '<p class="lead"><a href="/foo" target="_blank">Link</a></p>',
            $this->clean('<p class="lead" style="color: red" data-x="1"><a href="/foo" target="_blank" onclick="x()">Link</a></p>')
        );
    }

    public function testUnwrapsSpansButKeepsContent(): void
    {
        $this->assertSame(
            '<p>A keep me B</p>',
            $this->clean('<p>A <span class="c" style="color: red">keep me</span> B</p>')
        );
    }

    public function testConvertsUnderlineSpanToU(): void
    {
        $this->assertSame(
            '<p>A <u>under <strong>bold</strong></u> B</p>',
            $this->clean('<p>A <span style="text-decoration: underline;">under <strong style="x">bold</strong></span> B</p>')
        );
    }

    public function testListEntriesCanBeDisabled(): void
    {
        Config::modify()->merge(HtmlFieldCleaner::class, 'unwrap', ['span' => false]);

        $this->assertSame(
            '<p><span class="c">keep</span></p>',
            $this->clean('<p><span class="c" style="x">keep</span></p>')
        );
    }

    public function testOverrides(): void
    {
        $this->assertSame(
            '<p style="color: red"><strong>Bold</strong></p>',
            $this->clean('<p style="color: red"><b>Bold</b></p>', [
                'attribute_whitelist' => ['style' => true],
                'replacements' => ['b' => 'strong'],
            ])
        );
    }

    public function testTagBlacklistRemovesContent(): void
    {
        $this->assertSame(
            '<p>Text</p>',
            $this->clean('<p>Text</p><script>alert(1)</script>', ['tag_blacklist' => ['script']])
        );
    }

    public function testDisabled(): void
    {
        Config::modify()->set(HtmlFieldCleaner::class, 'enabled', false);

        $html = '<p style="color: red">Text</p>';
        $this->assertSame($html, $this->clean($html));
    }

    public function testIsHtmlFieldType(): void
    {
        $cleaner = HtmlFieldCleaner::singleton();

        $this->assertTrue($cleaner->isHtmlFieldType('HTMLText'));
        $this->assertTrue($cleaner->isHtmlFieldType('HTMLVarchar(255)'));
        $this->assertFalse($cleaner->isHtmlFieldType('Varchar(255)'));
        $this->assertFalse($cleaner->isHtmlFieldType('HTMLText', ['field_type_prefixes' => ['HTML' => false]]));
    }
}
