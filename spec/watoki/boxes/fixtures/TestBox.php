<?php
namespace spec\watoki\boxes\fixtures;

use watoki\boxes\BoxCollection;
use watoki\boxes\BoxContainer;
use watoki\boxes\Wrapper;
use watoki\curir\delivery\WebRequest;

class TestBox extends BoxContainer {

    /** @var array|BoxCollection[] */
    private $collections = array();

    protected function registerBoxes(BoxCollection $boxes) {}

    public function add($name, $args) {
        $this->boxes->set($name, $this->box($name, $args));
    }

    public function addCollection($name, array $boxes) {
        $collection = new BoxCollection($boxes);
        $this->boxes->set($name, $collection);
        $this->collections[$name] = $collection;
    }

    public function addToCollection($collection, $name, $args) {
        $this->collections[$collection]->add($this->box($name, $args));
    }

    public function mapTargetsTo($box) {
        $this->boxes->mapTargetsTo($box);
    }

    public function after($return, WebRequest $request) {
        if (!is_string($return)) {
            return parent::after($return, $request);
        }

        $model = array_merge(
            $request->getArguments()->toArray(),
            $this->boxes->getModel()
        );

        return parent::after($this->render($return, $model), $request);
    }

    private function render($template, $model) {
        foreach ($model as $key => $value) {
            if (substr($key, 0, 1) == Wrapper::$PREFIX) {
                continue;
            } else if (is_array($value)) {
                $$key = implode(' ', $value);
            } else {
                $$key = $value;
            }
        }
        $code = 'return "' . str_replace('"', '\"', $template) . '";';
        return eval($code);
    }
}