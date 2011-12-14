<?php
/**
 * @file
 * Namespace-aware XML features.
 *
 * @author Xander Guzman <theshadow@shadowpedia.info>
 * @license http://opensource.org/licenses/lgpl-2.1.php LGPL or MIT-like license.
 */

//@codingStandardsIgnoreStart
/**
 * The QPXML2 extension to QueryPath.
 *
 * This extension provides advanced namespace support to QueryPath.
 */
class QPXML2 implements QueryPathExtension
{
    protected $qp;

    public function __construct(QueryPath $qp)
    {
        $this->qp = $qp;
    }

    public function toXml() {
        return $this->qp->document()->saveXml();
    }

    public function createNilElement($text, $value)
    {
        $value = ($value)? 'true':'false';
        $element = $this->qp->createElement($text);
        $element->attr('xsi:nil', $value);
        return $element;
    }

    public function createElement($text, $nsUri = null)
    {
        if (isset ($text)) {
            foreach ($this->qp->get() as $element) {
                if ($nsUri === null && strpos($text, ':') !== false) {
                    $ns = array_shift(explode(':', $text));
                    $nsUri = $element->ownerDocument->lookupNamespaceURI($ns);

                    if ($nsUri === null) {
                        throw new QueryPathException(
                            "Undefined namespace for: " . $text
                        );
                    }
                }

                $node = null;
                if ($nsUri !== null) {
                    $node = $element->ownerDocument->createElementNS(
                        $nsUri,
                        $text
                    );
                } else {
                    $node = $element->ownerDocument->createElement(
                        $text
                    );
                }
                return qp($node);
            }
        }
        return;
    }

    public function appendElement($text)
    {
        if (isset ($text)) {
            foreach ($this->qp->get() as $element) {
                $node = $this->qp->createElement($text);
                qp($element)->append($node);
            }
        }
        return $this->qp;
    }
}
QueryPathExtensionRegistry::extend('QPXML2');

//@codingStandardsIgnoreEnd
