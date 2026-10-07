<?php

namespace Atwx\HtmlFieldCleaner;

use Atwx\HtmlFieldCleaner\Preprocessors\Preprocessor;
use Masterminds\HTML5;
use MehrIt\HtmlCleaner\HtmlCleaner;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use UnexpectedValueException;

/**
 * Cleans HTML fragments according to the rules configured in YAML.
 *
 * All list settings accept either maps (`class: true`, `class: false`) or plain
 * lists (`- class`). Maps are recommended, because entries can be disabled
 * again in a later config block.
 */
class HtmlFieldCleaner
{
    use Configurable;
    use Injectable;

    private static bool $enabled = true;

    private static array $field_type_prefixes = [];

    private static array $attribute_whitelist = [];

    private static array $attribute_blacklist = [];

    private static array $tag_whitelist = [];

    private static array $tag_blacklist = [];

    private static array $unwrap = [];

    private static array $replacements = [];

    private static array $preprocessors = [];

    /**
     * Settings that are merged per key instead of being replaced by overrides.
     */
    private const array LIST_SETTINGS = [
        'field_type_prefixes',
        'attribute_whitelist',
        'attribute_blacklist',
        'tag_whitelist',
        'tag_blacklist',
        'unwrap',
        'replacements',
        'preprocessors',
    ];

    public function isEnabled(): bool
    {
        return (bool) static::config()->get('enabled');
    }

    /**
     * Whether the given db field spec (e.g. "HTMLText", "HTMLVarchar(255)") should be cleaned.
     */
    public function isHtmlFieldType(string $type, array $overrides = []): bool
    {
        $prefixes = $this->normaliseList($this->getSettings($overrides)['field_type_prefixes']);
        foreach ($prefixes as $prefix) {
            if (str_starts_with($type, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the global settings merged with the given overrides.
     * List settings are merged per entry, all other settings are replaced.
     */
    public function getSettings(array $overrides = []): array
    {
        $settings = [];
        foreach (array_merge(['enabled'], HtmlFieldCleaner::LIST_SETTINGS) as $key) {
            $settings[$key] = static::config()->get($key) ?? [];
        }

        foreach ($overrides as $key => $value) {
            if (in_array($key, HtmlFieldCleaner::LIST_SETTINGS, true) && is_array($value)) {
                $settings[$key] = array_merge((array) ($settings[$key] ?? []), $value);
            } else {
                $settings[$key] = $value;
            }
        }

        return $settings;
    }

    public function clean(string $html, array $overrides = []): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $settings = $this->getSettings($overrides);
        if (!$settings['enabled']) {
            return $html;
        }

        $html5 = new HTML5();
        $fragment = $html5->parseFragment($html);

        foreach ($this->getPreprocessors($settings) as $preprocessor) {
            $preprocessor->process($fragment);
        }

        $this->buildCleaner($settings)->cleanDOM($fragment);

        return $html5->saveHTML($fragment);
    }

    protected function buildCleaner(array $settings): HtmlCleaner
    {
        $cleaner = new HtmlCleaner();

        if ($whitelist = $this->normaliseList($settings['attribute_whitelist'])) {
            $cleaner->setAttributeWhitelist($whitelist);
        }
        if ($blacklist = $this->normaliseList($settings['attribute_blacklist'])) {
            $cleaner->setAttributeBlacklist($blacklist);
        }
        if ($whitelist = $this->normaliseList($settings['tag_whitelist'])) {
            $cleaner->setTagWhitelist($whitelist);
        }
        if ($blacklist = $this->normaliseList($settings['tag_blacklist'])) {
            $cleaner->setTagBlacklist($blacklist);
        }

        // false disables a replacement inherited from a previous config block
        $replacements = array_filter(
            (array) $settings['replacements'],
            fn ($replacement) => $replacement !== false
        );

        return $cleaner
            ->setReplacements($replacements)
            ->setUnwraps($this->normaliseList($settings['unwrap']));
    }

    /**
     * @return Preprocessor[]
     */
    protected function getPreprocessors(array $settings): array
    {
        $preprocessors = [];
        foreach ((array) $settings['preprocessors'] as $class) {
            if (!$class) {
                continue;
            }

            $preprocessor = Injector::inst()->get($class);
            if (!$preprocessor instanceof Preprocessor) {
                throw new UnexpectedValueException(sprintf(
                    '%s must implement %s',
                    $class,
                    Preprocessor::class
                ));
            }

            $preprocessors[] = $preprocessor;
        }

        return $preprocessors;
    }

    /**
     * Turns `[a, b]` and `{a: true, b: false}` into `[a]` resp. `[a, b]`.
     *
     * @return string[]
     */
    protected function normaliseList(array $list): array
    {
        $result = [];
        foreach ($list as $key => $value) {
            if (is_int($key)) {
                if (is_string($value) && $value !== '') {
                    $result[$value] = true;
                }
            } else {
                $result[$key] = (bool) $value;
            }
        }

        return array_keys(array_filter($result));
    }
}
