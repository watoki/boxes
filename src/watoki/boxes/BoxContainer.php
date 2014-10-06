<?php
namespace watoki\boxes;

use watoki\collections\Map;
use watoki\curir\Container;
use watoki\curir\delivery\WebRequest;
use watoki\curir\Responder;
use watoki\deli\Path;
use watoki\factory\Factory;

abstract class BoxContainer extends Container {

    /** @var BoxCollection */
    protected $boxes;

    /**
     * @param Factory $factory <-
     */
    function __construct(Factory $factory) {
        parent::__construct($factory);
        $this->boxes = new BoxCollection();

        $this->registerBoxes();
    }

    abstract protected function registerBoxes();

    protected function addBox($name, $args = array(), $pathString = null) {
        $pathString = $pathString ? : $name;
        $this->boxes->set($name, new Box(Path::fromString($pathString), new Map($args)));
        return $this;
    }

    public function before(WebRequest $request) {
        $state = null;
        if (!($request instanceof WrappedRequest)) {
            $request = WrappedRequest::fromRequest($request);
            $state = $request->getArguments();
        }
        $this->boxes->dispatch($request, $this->router, $state);

        return parent::before($request);
    }

    public function after($return, WebRequest $request) {
        $response = parent::after($return, $request);

        $response->setBody($this->boxes->mergeHeaders($response->getBody()));
        return $response;
    }

}