<?php
namespace watoki\boxes;

use watoki\collections\Map;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\protocol\Url;
use watoki\dom\Element;
use watoki\dom\Parser;
use watoki\dom\Printer;

class Wrapper {

    private static $formElements = array(
        'input',
        'textarea',
        'button',
        'select'
    );

    private $name;

    function __construct($name) {
        $this->name = $name;
    }

    public function wrap(WebResponse $response) {
        $parser = new Parser($response->getBody());

        $body = $this->findElement($parser->getRoot(), 'html/body');
        if (!$body) {
            $body = $parser->getRoot();
        }
        $this->wrapChildren($body);

        $printer = new Printer();
        return $printer->printNodes($body->getChildren());
    }

    private function wrapChildren(Element $element) {
        foreach ($element->getChildElements() as $child) {
            switch ($child->getName()) {
                case 'a':
                    $this->wrapLink($child);
                    break;
                case 'form':
                    $this->wrapForm($child);
                    break;
            }
            $this->wrapChildren($child);
        }
    }

    private function findElement(Element $in, $path) {
        foreach (explode('/', $path) as $name) {
            if (!$in) {
                return null;
            }
            $in = $in->findChildElement($name);
        }
        return $in;
    }

    private function wrapLink(Element $element) {
        $target = Url::fromString($element->getAttribute('href')->getValue());
        $wrapped = $this->wrapUrl($element, $target);
        $element->setAttribute('href', $wrapped->toString());
    }

    private function wrapForm(Element $element) {
        $target = '';
        if ($element->getAttribute('action')) {
            $target = $element->getAttribute('action')->getValue();
        }
        $wrapped = $this->wrapUrl($element, $target);
        $wrapped->getParameters()->set(Shelf::TARGET_KEY, $this->name);
        $element->setAttribute('action', $wrapped->toString());

        $this->wrapFormElements($element);
    }

    private function wrapFormElements(Element $in) {
        foreach ($in->getChildElements() as $child) {
            if (in_array($child->getName(), self::$formElements)) {
                $name = $child->getAttribute('name');
                if (!$name || $name->getValue() == WebRequest::$METHOD_KEY) {
                    continue;
                }

                $url = Url::fromString('?' . $name->getValue() . '=0');
                $wrapped = Url::fromString('');
                $wrapped->getParameters()->set($this->name, $url->getParameters());

                $child->setAttribute('name', substr($wrapped->toString(), 1, -2));
            }
            $this->wrapFormElements($child);
        }
    }

    private function wrapUrl(Element $element, $target) {
        $target = Url::fromString($target);
        $box = $this->name;

        if ($element->getAttribute('target')) {
            $box = $element->getAttribute('target')->getValue();
            $element->getAttributes()->removeElement($element->getAttribute('target'));
        }

        $params = new Map();
        if ($target->getPath()->toString()) {
            $params->set(Shelf::TARGET_KEY, $target->getPath()->toString());
        }
        $params->merge($target->getParameters());

        $wrapped = Url::fromString('');
        $wrapped->getParameters()->set($box, $params);
        return $wrapped;
    }
}