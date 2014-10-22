<?php
namespace watoki\boxes;

use watoki\collections\Map;
use watoki\curir\Container;
use watoki\curir\delivery\WebRequest;
use watoki\curir\responder\Redirecter;
use watoki\curir\Responder;
use watoki\curir\delivery\WebRouter;
use watoki\deli\Path;
use watoki\deli\Router;
use watoki\factory\Factory;

abstract class BoxContainer extends Container {

    /** @var BoxCollection */
    protected $boxes;

    /** @var \watoki\deli\Router */
    private $router;

    /**
     * @param Factory $factory <-
     */
    function __construct(Factory $factory) {
        parent::__construct($factory);
        $this->boxes = new BoxCollection();

        $this->router = $this->createBoxRouter();

        $this->registerBoxes();
    }

    abstract protected function registerBoxes();

    /**
     * @return Router
     */
    protected function createBoxRouter() {
        $class = new \ReflectionClass($this);
        $namespace = $class->getNamespaceName();
        $directory = $this->getDirectory();

        return new WebRouter($this->factory, $directory, $namespace);
    }

    /**
     * @param string $path
     * @param array $args
     * @return Box
     */
    protected function box($path, $args = array()) {
        return new Box(Path::fromString($path), new Map($args));
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