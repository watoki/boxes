<?php
namespace watoki\boxes;

use watoki\collections\Map;
use watoki\curir\Container;
use watoki\curir\delivery\WebRequest;
use watoki\curir\Responder;
use watoki\deli\Path;
use watoki\deli\router\DynamicRouter;
use watoki\deli\router\MultiRouter;
use watoki\factory\Factory;

abstract class BoxContainer extends Container {

    /** @var BoxCollection */
    protected $boxes;

    /** @var DynamicRouter */
    protected $dynamicRouter;

    /**
     * @param Factory $factory <-
     */
    function __construct(Factory $factory) {
        parent::__construct($factory);
        $this->boxes = new BoxCollection();

        $this->dynamicRouter = new DynamicRouter();
        $this->router = new MultiRouter(array($this->dynamicRouter, $this->router));

        $this->registerBoxes();
    }

    abstract protected function registerBoxes();

    /**
     * @param string $path
     * @param array $args
     * @return Box
     */
    protected function box($path, $args) {
        return new Box(Path::fromString($path), new Map($args));
    }

    /**
     * @param string $name
     * @param array $args
     * @param null|string $pathString
     */
    protected function addBox($name, $args = array(), $pathString = null) {
        $pathString = $pathString ? : $name;
        $this->boxes->set($name, $this->box($pathString, $args));
    }

    /**
     * @param string $name
     * @param array $boxes
     * @return BoxCollection
     */
    protected function addCollection($name, array $boxes) {
        $collection = new BoxCollection($boxes);
        $this->boxes->set($name, $collection);
        return $collection;
    }

    protected function getBoxes() {
        return $this->boxes->getModel();
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