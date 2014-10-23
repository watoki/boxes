<?php
namespace watoki\boxes;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\collections\Set;
use watoki\curir\delivery\WebRequest;
use watoki\curir\protocol\Url;
use watoki\deli\Path;
use watoki\deli\Router;
use watoki\dom\Attribute;
use watoki\dom\Element;
use watoki\dom\Parser;
use watoki\dom\Printer;

class BoxCollection implements Dispatching {

    /** @var array|Dispatching[] */
    private $children = array();

    private $model = array();

    /** @var Set|Element[] */
    private $heads;

    /** @var null|string */
    private $onLoadHandler;

    /** @var string|null */
    private $mappedBox;

    public function __construct($children = array()) {
        $this->children = $children;
        $this->heads = new Set();
    }

    public function set($name, Dispatching $box) {
        $this->children[$name] = $box;
    }

    public function add(Dispatching $box) {
        $this->children[] = $box;
    }

    public function getModel() {
        return $this->model;
    }

    public function mapTargetsTo($name) {
        $this->mappedBox = $name;
    }

    public function isMapping() {
        return $this->mappedBox !== null;
    }

    public function dispatch(WebRequest $request, Router $router) {
        if ($this->isMapping()) {
            $this->wrapMappedTarget($request);
        }

        if ($request->getArguments()->has(Box::$PRIMARY_TARGET_KEY)) {
            $this->putPrimaryChildFirst($request);
        }

        $this->dispatchToChildren($request, $router);

        ksort($this->model);
        return $request;
    }

    private function putPrimaryChildFirst(WebRequest $request) {
        $primary = $request->getArguments()->get(Box::$PRIMARY_TARGET_KEY);
        uksort($this->children, function ($a, $b) use ($primary) {
            $return = $a == $primary ? -1 : ($b == $primary ? 1 : 0);
            return $return;
        });
    }

    private function dispatchToChildren(WebRequest $request, Router $router) {
        foreach ($this->children as $name => $child) {
            $next = $this->unwrap($request, $name);

            try {
                $dispatched = $child->dispatch($next, $router);
            } catch (WrappedRedirection $r) {
                $target = $r->getTarget();
                throw new WrappedRedirection($this->wrapTarget($target, $name, $next, $request));
            }
            $model = $child->getModel();

            $this->model[$name] = $this->wrapModel($model, $name, $dispatched, $request);

            if ($child instanceof BoxCollection) {
                $this->heads->putAll($child->heads);
                $this->onLoadHandler .= $child->onLoadHandler;
            }
        }
    }

    private function unwrap(WebRequest $request, $name) {
        $next = $request->copy();
        $next->getArguments()->clear();

        if ($request->getArguments()->has(Wrapper::$PREFIX . $name)) {
            $next->getArguments()->merge($request->getArguments()->get(Wrapper::$PREFIX . $name));
        }
        return $next;
    }

    private function wrapModel($model, $name, WebRequest $dispatched, WebRequest $wrapped) {
        if (is_string($model)) {
            return $this->wrap($name, $model, $dispatched, $wrapped);
        } else {
            foreach ($model as $i => $item) {
                $model[$i] = $this->wrap($name, $item, $dispatched, $wrapped);
            }
            return $model;
        }
    }

    private function wrap($name, $model, WebRequest $dispatched, WebRequest $wrapped) {
        $wrapper = new Wrapper($name, $dispatched->getTarget(), $wrapped->getArguments());
        if ($this->isMapping()) {
            $wrapper->except($this->mappedBox);
        }
        $model = $wrapper->wrap($model);
        $this->onLoadHandler .= $wrapper->getOnLoadHandler();
        $this->heads->putAll($wrapper->getHeadElements());
        return $model;
    }

    public function mergeHeaders($into, Url $context) {
        $parser = new Parser($into);

        $html = $parser->findElement('html');
        if (!$html) {
            return $into;
        }

        $head = $html->findChildElement('head');

        if ($head) {
            if ($this->isMapping()) {
                $baseElement = new Element('base', new Liste(array(new Attribute('href', $context->toString(), Attribute::QUOTE_DOUBLE))));
                $head->getChildren()->insert($baseElement, 0);
            }

            foreach ($this->heads as $new) {
                if (!$this->isAlreadyIn($new, $head->getChildElements())) {
                    $head->getChildren()->append($new);
                }
            }
        }

        $body = $html->findChildElement('body');
        if ($body) {
            $handler = $this->onLoadHandler;
            if ($body->getAttribute('onload')) {
                $handler = $body->getAttribute('onload')->getValue() . $handler;
            }
            if ($handler) {
                $body->setAttribute('onload', $handler);
            }
        }

        $printer = new Printer();
        return $printer->printNodes($parser->getNodes());
    }

    /**
     * @param Element $element
     * @param Element[] $in
     * @return bool
     */
    private function isAlreadyIn(Element $element, $in) {
        foreach ($in as $old) {
            if ($old->equals($element)) {
                return true;
            }
        }
        return false;
    }

    private function wrapTarget($target, $name, $next, WebRequest $request) {
        $targetUrl = Url::fromString($target);

        if ($targetUrl->isAbsolute()) {
            $context = $request->getContext()->toString();
            $target = ltrim(substr($target, strlen($context)), '/');
        }

        $model = '<a href="' . $target . '"/>';
        $wrapped = $this->wrap($name, $model, $next, $request);
        $wrappedTarget = Url::fromString(substr($wrapped, 9, -3));

        foreach ($request->getArguments() as $key => $value) {
            if ($key != Wrapper::$PREFIX . $name) {
                $wrappedTarget->getParameters()->set($key, $value);
            }
        }

        return $wrappedTarget->toString();
    }

    private function wrapMappedTarget(WebRequest $request) {
        $prefixedBox = Wrapper::$PREFIX . $this->mappedBox;
        $request->getArguments()->set(Box::$PRIMARY_TARGET_KEY, $this->mappedBox);
        $arguments = new Map();
        if ($request->getArguments()->has($prefixedBox)) {
            $arguments = $request->getArguments()->get($prefixedBox);
        }

        $target = $request->getTarget()->toString();
        $request->setTarget(new Path());
        if ($target) {
            $arguments->set(Box::$TARGET_KEY, $target);
        }
        foreach ($request->getArguments() as $key => $value) {
            if (substr($key, 0, strlen(Wrapper::$PREFIX)) != Wrapper::$PREFIX) {
                $arguments->set($key, $value);
                $request->getArguments()->remove($key);
            }
        }
        $request->getArguments()->set($prefixedBox, $arguments);
    }
}