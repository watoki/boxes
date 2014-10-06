<?php
namespace watoki\boxes;

use watoki\collections\Map;
use watoki\collections\Set;
use watoki\curir\delivery\WebRequest;
use watoki\curir\protocol\Url;
use watoki\deli\Path;
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

    private static $ignoredHeadElements = array(
        'title'
    );

    /** @var string */
    private $name;

    /** @var \watoki\deli\Path */
    private $path;

    /** @var Set */
    private $headElements;

    /** @var Map */
    private $state;

    function __construct($name, Path $path, Map $state) {
        $this->name = $name;
        $this->path = $path;
        $this->state = $state;
        $this->headElements = new Set();
    }

    /**
     * @param string $response
     * @return string
     */
    public function wrap($response) {
        $parser = new Parser($response);
        $root = $parser->getRoot();

        $body = $this->findElement($root, 'html/body');
        if (!$body) {
            $body = $root;
        }
        $this->wrapChildren($body);

        $head = $this->findElement($root, 'html/head');
        if ($head) {
            foreach ($head->getChildElements() as $headElement) {
                if (!in_array($headElement->getName(), self::$ignoredHeadElements)) {
                    $this->headElements->put($headElement);
                }
            }
        }

        $printer = new Printer();
        return $printer->printNodes($body->getChildren());
    }

    public function getHeadElements() {
        return $this->headElements;
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
                case 'link':
                    $this->wrapAsset($child, 'href');
                    break;
                case 'script':
                case 'img':
                    $this->wrapAsset($child, 'src');
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

        $method = WebRequest::METHOD_POST;
        if ($element->getAttribute('method')) {
            $method = $element->getAttribute('method')->getValue();
        }

        $wrapped = $this->wrapUrl($element, $target);

        $wrapped->getParameters()->one()->set(WebRequest::$METHOD_KEY, $method);
        $element->setAttribute('action', $wrapped->toString());

        $this->wrapFormElements($element);
    }

    private function wrapFormElements(Element $in) {
        foreach ($in->getChildElements() as $child) {
            if (in_array($child->getName(), self::$formElements)) {
                $name = $child->getAttribute('name');
                if (!$name) {
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
            $params->set(Box::$TARGET_KEY, $target->getPath()->toString());
        }
        $params->merge($target->getParameters());

        $wrapped = Url::fromString('');
        foreach ($this->state as $name => $state) {
            if ($name !== $box) {
                $wrapped->getParameters()->set($name, $state);
            }
        }
        $wrapped->getParameters()->set($box, $params);
        $wrapped->getParameters()->set(Box::$PRIMARY_TARGET_KEY, $this->name);
        return $wrapped;
    }

    private function wrapAsset(Element $element, $attributeName) {
        $attribute = $element->getAttribute($attributeName);
        if (!$attribute) {
            return;
        }
        $url = Url::fromString($attribute->getValue());
        if ($url->isAbsolute()) {
            return;
        }
        $path = $url->getPath();
        $path->insertAll($this->path, 0);

        $url->setPath($path);
        $element->setAttribute($attributeName, $url->toString());
    }
}