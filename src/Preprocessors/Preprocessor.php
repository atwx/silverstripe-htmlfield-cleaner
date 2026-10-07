<?php

namespace Atwx\HtmlFieldCleaner\Preprocessors;

use DOMDocumentFragment;

/**
 * Manipulates the parsed HTML before the cleaning rules are applied,
 * e.g. to convert markup that would otherwise be lost by unwrapping.
 */
interface Preprocessor
{
    public function process(DOMDocumentFragment $fragment): void;
}
