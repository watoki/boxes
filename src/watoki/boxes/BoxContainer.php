<?php
namespace watoki\boxes;

use watoki\collections\Map;
use watoki\curir\Container;
use watoki\curir\delivery\WebRequest;
use watoki\curir\responder\Redirecter;
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

    /**
     * @param string $path
     * @param array $args
     * @return Box
     */
    protected function box($path, $args = array()) {
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
        if (!$request->getArguments()->has(WebRequest::$METHOD_KEY)) {
            $request->setMethod(WebRequest::METHOD_GET);
        }

        try {
            $this->boxes->dispatch($request, $this->router, $request->getArguments());
        } catch (WrappedRedirection $r) {
            $request->getArguments()->set('target', $r->getTarget());
            $request->setMethod('redirect');
        }

        return parent::before($request);
    }

    public function after($return, WebRequest $request) {
        $response = parent::after($return, $request);

        $response->setBody($this->boxes->mergeHeaders($response->getBody()));
        return $response;
    }

    public function doRedirect($target) {
        return Redirecter::fromString($target);
    }

}