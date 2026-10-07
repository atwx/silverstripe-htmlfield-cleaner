<?php

namespace Atwx\HtmlFieldCleaner\Extensions;

use Atwx\HtmlFieldCleaner\HtmlFieldCleaner;
use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;

/**
 * Cleans all HTML db fields of the owner before writing.
 *
 * Per class configuration (on the extended DataObject):
 *
 * ```yaml
 * App\Elements\ElementEmbed:
 *   html_cleaner_enabled: false          # skip this class (and its subclasses)
 *
 * App\Elements\ElementContent:
 *   html_cleaner:                        # overrides for all fields of this class
 *     attribute_whitelist:
 *       style: true
 *   html_cleaner_fields:
 *     EmbedCode: false                   # never clean this field
 *     Teaser:                            # overrides for this field only
 *       tag_whitelist: [p, br, strong, em, a]
 * ```
 *
 * @extends Extension<DataObject>
 */
class HtmlFieldCleanerExtension extends Extension
{
    private static bool $html_cleaner_enabled = true;

    private static array $html_cleaner = [];

    private static array $html_cleaner_fields = [];

    protected function onBeforeWrite(): void
    {
        $owner = $this->getOwner();
        $cleaner = HtmlFieldCleaner::singleton();

        if (!$cleaner->isEnabled() || !$owner->config()->get('html_cleaner_enabled')) {
            return;
        }

        $classOverrides = (array) $owner->config()->get('html_cleaner');
        $fieldOverrides = (array) $owner->config()->get('html_cleaner_fields');

        foreach ((array) $owner->config()->get('db') as $field => $type) {
            $overrides = $classOverrides;
            if (array_key_exists($field, $fieldOverrides)) {
                if (!$fieldOverrides[$field]) {
                    continue;
                }
                if (is_array($fieldOverrides[$field])) {
                    $overrides = $this->mergeOverrides($overrides, $fieldOverrides[$field]);
                }
            }

            if (!is_string($type) || !$cleaner->isHtmlFieldType($type, $overrides)) {
                continue;
            }

            $value = $owner->getField($field);
            if ($value === null || $value === '') {
                continue;
            }

            $owner->setField($field, $cleaner->clean((string) $value, $overrides));
        }
    }

    protected function mergeOverrides(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            $base[$key] = is_array($value) && is_array($base[$key] ?? null)
                ? array_merge($base[$key], $value)
                : $value;
        }

        return $base;
    }
}
