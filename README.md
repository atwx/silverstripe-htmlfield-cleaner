# Silverstripe HTML Field Cleaner

Cleans the HTML fields (`HTMLText`, `HTMLVarchar`, …) of DataObjects before they are written.
Unwanted attributes such as inline styles and `data-*` are removed, `<span>` tags get unwrapped,
and TinyMCE's underline spans are turned into `<u>`. All rules are configured in YAML.

Built on [mehr-it/html-cleaner](https://github.com/mehr-it/html-cleaner).

## Installation

```sh
composer require atwx/silverstripe-htmlfield-cleaner
```

The extension is applied automatically to:

- `SilverStripe\CMS\Model\SiteTree` (if `silverstripe/cms` is installed)
- `SilverStripe\SiteConfig\SiteConfig` (if `silverstripe/siteconfig` is installed)
- `DNADesign\Elemental\Models\BaseElement` (if `dnadesign/silverstripe-elemental` is installed)

Add it to other DataObjects yourself:

```yaml
App\Models\Event:
  extensions:
    - Atwx\HtmlFieldCleaner\Extensions\HtmlFieldCleanerExtension
```

## Default behaviour

| Setting               | Default                                                    |
|-----------------------|------------------------------------------------------------|
| `attribute_whitelist` | `href`, `src`, `alt`, `title`, `target`, `rel`, `class`    |
| `unwrap`              | `span`                                                     |
| `preprocessors`       | `underline`: `<span style="text-decoration: underline">` → `<u>` |
| `field_type_prefixes` | `HTML` (all db types starting with `HTML`)                 |

## Global configuration

All list settings are **maps** (`item: true|false`). This lets you switch off a single default
entry, which plain YAML lists can't do, because Silverstripe merges lists by appending.
Plain lists (`- item`) are still accepted for adding entries.

```yaml
---
Name: app-htmlfieldcleaner
After: htmlfieldcleaner
---
Atwx\HtmlFieldCleaner\HtmlFieldCleaner:
  # switch the cleaner off entirely, e.g. in a dev environment
  enabled: true

  # only these attributes are kept (empty = keep all)
  attribute_whitelist:
    class: false          # remove a default entry
    id: true              # add an entry
  # these attributes are always removed ('*' = all)
  attribute_blacklist:
    onclick: true

  # only these tags are kept, others are removed WITH their content (empty = allow all)
  tag_whitelist: {}
  # these tags are removed WITH their content
  tag_blacklist:
    script: true
    style: true

  # these tags are removed, their content is kept ('*' = all)
  unwrap:
    span: true
    font: true

  # rename tags; `~` replaces the tag with its plain text content; `false` disables an entry
  replacements:
    b: strong
    i: em

  # DOM preprocessors that run before the rules above; `~` disables one
  preprocessors:
    underline: ~
```

## Per class configuration

Configured on the DataObject class. It inherits to subclasses, like any other Silverstripe config.

```yaml
# don't clean this class (and its subclasses) at all
App\Elements\ElementEmbed:
  html_cleaner_enabled: false

App\Elements\ElementContent:
  # overrides for all HTML fields of this class, merged into the global settings
  html_cleaner:
    attribute_whitelist:
      style: true
  # per field: `false` skips the field, a map overrides settings for this field only
  html_cleaner_fields:
    EmbedCode: false
    Teaser:
      tag_whitelist: [p, br, strong, em, a]
```

## Remove the default extensions

The extensions are registered with the name `htmlfieldcleaner`, so they can be removed again:

```yaml
---
Name: app-htmlfieldcleaner
After: htmlfieldcleaner-extensions-siteconfig
---
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    htmlfieldcleaner: null
```

## Custom preprocessors

Preprocessors work on the parsed DOM before the cleaning rules are applied. Use them to convert
markup that would otherwise be lost, as the underline preprocessor does.

```php
use Atwx\HtmlFieldCleaner\Preprocessors\Preprocessor;
use DOMDocumentFragment;

class MyPreprocessor implements Preprocessor
{
    public function process(DOMDocumentFragment $fragment): void
    {
        // manipulate $fragment
    }
}
```

```yaml
Atwx\HtmlFieldCleaner\HtmlFieldCleaner:
  preprocessors:
    mine: App\HtmlCleaner\MyPreprocessor
```

## Using the cleaner directly

```php
use Atwx\HtmlFieldCleaner\HtmlFieldCleaner;

$clean = HtmlFieldCleaner::singleton()->clean($html);
$clean = HtmlFieldCleaner::singleton()->clean($html, ['attribute_whitelist' => ['style' => true]]);
```

## Running the tests

```sh
vendor/bin/phpunit vendor/atwx/silverstripe-htmlfield-cleaner/tests/php
```
