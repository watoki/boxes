<?php
namespace watoki\boxes;

use watoki\collections\Map;
use watoki\curir\Container;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebRouter;
use watoki\curir\responder\Redirecter;
use watoki\curir\Responder;
use watoki\deli\Path;
use watoki\deli\Request;
use watoki\deli\Router;
use watoki\deli\target\ObjectTarget;
use watoki\factory\Factory;

abstract class BoxContainer extends Container {

    const HEADER_NO_BOXING = 'X-NoBoxing';

    /** @var BoxCollection */
    protected $boxes;

    /** @var \watoki\deli\Router */
    private $router;

    /**
     * @param Factory $factory <-
     */
    function __construct(Factory $factory) {
        parent::__construct($factory);
        $this->router = $this->createBoxRouter();
        $this->boxes = new BoxCollection();
        $this->registerBoxes($this->boxes);
    }

    abstract protected function registerBoxes(BoxCollection $boxes);

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

    /**
     * @param Request|WebRequest $request
     * @return mixed|\watoki\curir\delivery\WebResponse
     */
    public function respond(Request $request) {
        if ($this->isMapping($request)
        ) {
            $request->getHeaders()->set(self::HEADER_NO_BOXING, true);
            $target = new ObjectTarget($request, $this, $this->factory);
            return $target->respond();
        }
        return parent::respond($request);
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

        $response->setBody($this->boxes->mergeHeaders($response->getBody(), $request->getContext()));
        return $response;
    }

    public function doRedirect($target) {
        return Redirecter::fromString($target);
    }

    private function isMapping(WebRequest $request) {
        return $this->boxes->isMapping()
        && !$request->getHeaders()->has(self::HEADER_NO_BOXING)
        && $request->getFormats()->contains('html');
    }

}