<?php

namespace Atwx\HtmlFieldCleaner\Preprocessors;

use DOMDocumentFragment;
use DOMElement;
use DOMNode;

/**
 * TinyMCE renders underline as <span style="text-decoration: underline">
 * instead of <u>. Convert it before spans get unwrapped and styles get removed,
 * so the underline formatting survives cleaning.
 */
class UnderlineSpanPreprocessor implements Preprocessor
{
    public function process(DOMDocumentFragment $fragment): void
    {
        foreach ($this->findUnderlineSpans($fragment) as $span) {
            $document = $span->ownerDocument;
            $underline = $span->namespaceURI
                ? $document->createElementNS($span->namespaceURI, 'u')
                : $document->createElement('u');

            while ($span->firstChild) {
                $underline->appendChild($span->firstChild);
            }

            $span->parentNode->replaceChild($underline, $span);
        }
    }

    /**
     * @return DOMElement[]
     */
    protected function findUnderlineSpans(DOMNode $node): array
    {
        $spans = [];
        foreach ($node->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            if (
                strtolower($child->localName) === 'span'
                && preg_match('/text-decoration(-line)?\s*:\s*[^;]*\bunderline\b/i', $child->getAttribute('style'))
            ) {
                $spans[] = $child;
            }

            array_push($spans, ...$this->findUnderlineSpans($child));
        }

        return $spans;
    }
}
