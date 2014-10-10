<?php
namespace watoki\boxes;

use watoki\collections\Map;
use watoki\curir\protocol\Url;
use watoki\deli\Path;
use watoki\dom\Element;

class ContainerWrapper extends Wrapper {

    public function __construct(Map $state) {
        parent::__construct(null, new Path(), $state);
    }

    protected function findBody($root) {
        return $root;
    }

    protected function wrapFormElements(Element $in) {
        return;
    }

    protected function wrapUrl(Element $element, $target) {
        $target = Url::fromString($target);
//        $params = $target->getParameters();
//
//        foreach ($this->state as $name => $state) {
//            if ($this->isKeepWorthyState($params, $name)) {
//                $params->set($name, $state);
//            }
//        }
//
        return $target;
    }

} 