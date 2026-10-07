<?php

namespace Atwx\HtmlFieldCleaner\Tests\HtmlFieldCleanerExtensionTest;

use Atwx\HtmlFieldCleaner\Extensions\HtmlFieldCleanerExtension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class TestObject extends DataObject implements TestOnly
{
    private static string $table_name = 'HtmlFieldCleanerTest_TestObject';

    private static array $db = [
        'Title' => 'Varchar(255)',
        'Content' => 'HTMLText',
        'Embed' => 'HTMLText',
        'Teaser' => 'HTMLVarchar(255)',
    ];

    private static array $extensions = [
        HtmlFieldCleanerExtension::class,
    ];

    private static array $html_cleaner_fields = [
        'Embed' => false,
        'Teaser' => [
            'attribute_whitelist' => ['style' => true],
        ],
    ];
}
