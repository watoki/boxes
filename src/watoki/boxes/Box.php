<?php
namespace watoki\boxes;

use watoki\collections\Map;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\deli\Path;
use watoki\deli\Router;

class Box implements Dispatching {

    public static $TARGET_KEY = '!';

    public static $PRIMARY_TARGET_KEY = '_';

    /** @var WebResponse */
    private $response;

    /** @var Path */
    private $target;

    /** @var Map */
    private $arguments;

    function __construct(Path $defaultTarget, Map $defaultArguments = null) {
        $this->target = $defaultTarget;
        $this->arguments = $defaultArguments ? : new Map();
    }

    public function dispatch(WebRequest $request, Router $router) {
        $arguments = $request->getArguments();

        $request->setTarget($this->target);

        if ($arguments->isEmpty()) {
            $arguments->merge($this->arguments);
            $request->setMethod(WebRequest::METHOD_GET);
        } else {
            if ($arguments->has(self::$TARGET_KEY)) {
                $request->setTarget(Path::fromString($arguments->get(self::$TARGET_KEY)));
            }
            if ($arguments->has(WebRequest::$METHOD_KEY)) {
                $request->setMethod($arguments->get(WebRequest::$METHOD_KEY));
            } else {
                $request->setMethod(WebRequest::METHOD_GET);
            }
        }

        $this->response = $router->route($request)->respond();

        if ($this->response->getHeaders()->has(WebResponse::HEADER_LOCATION)) {
            throw new WrappedRedirection($this->response->getHeaders()->get(WebResponse::HEADER_LOCATION));
        }

        return $request;
    }

    public function getModel() {
        return $this->response->getBody();
    }

}