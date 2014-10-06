<?php
namespace watoki\boxes;

use watoki\collections\Set;
use watoki\deli\Router;
use watoki\dom\Element;
use watoki\dom\Parser;
use watoki\dom\Printer;

class BoxCollection implements Dispatching {

    /** @var array|Dispatching[] */
    private $children = array();

    private $model = array();

    /** @var Set|Element[] */
    private $heads;

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

    public function wrapContainer($body) {
        return $this->mergeHeaders($body);
    }

    public function dispatch(WrappedRequest $request, Router $router) {
        if ($request->getArguments()->has(Box::$PRIMARY_TARGET_KEY)) {
            $primary = $request->getArguments()->get(Box::$PRIMARY_TARGET_KEY);
            uksort($this->children, function ($a, $b) use ($primary) {
                $return = $a == $primary ? -1 : ($b == $primary ? 1 : 0);
                return $return;
            });
        }

        foreach ($this->children as $name => $child) {
            $next = $this->unwrap($request, $name);

            $dispatched = $child->dispatch($next, $router);
            $model = $child->getModel();

            $this->model[$name] = $this->wrapModel($model, $name, $dispatched, $request);
        }

        ksort($this->model);
        return $request;
    }

    private function unwrap(WrappedRequest $request, $name) {
        $next = $request->copy();
        $next->getArguments()->clear();

        if ($request->getArguments()->has(Wrapper::$PREFIX . $name)) {
            $next->getArguments()->merge($request->getArguments()->get(Wrapper::$PREFIX . $name));
        }
        return $next;
    }

    private function wrapModel($model, $name, WrappedRequest $dispatched, WrappedRequest $wrapped) {
        if (is_string($model)) {
            return $this->wrap($name, $model, $dispatched, $wrapped);
        } else {
            foreach ($model as $i => $item) {
                $model[$i] = $this->wrap($name, $item, $dispatched, $wrapped);
            }
            return $model;
        }
    }

    private function wrap($name, $model, WrappedRequest $dispatched, WrappedRequest $wrapped) {
        $wrapper = new Wrapper($name, $dispatched->getTarget(), $wrapped->getArguments());
        $model = $wrapper->wrap($model);
        $this->heads->putAll($wrapper->getHeadElements());
        return $model;
    }

    public function mergeHeaders($into) {
        $parser = new Parser($into);

        $html = $parser->findElement('html');
        if (!($html && $html->findChildElement('head'))) {
            return $into;
        }

        $head = $html->findChildElement('head');

        foreach ($this->heads as $new) {
            if (!$this->isAlreadyIn($new, $head->getChildElements())) {
                $head->getChildren()->append($new);
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
}