<?php
namespace watoki\boxes;

use watoki\collections\Map;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\deli\Path;
use watoki\deli\Router;

class Shelf {

    const TARGET_KEY = '!';

    /** @var Router */
    private $router;

    /** @var array|WebResponse[] */
    private $responses = array();

    /** @var array|Path[] default paths indexed by box name */
    private $boxes = array();

    public function __construct(Router $router) {
        $this->router = $router;
    }

    public function set($name, $defaultPath) {
        $this->boxes[$name] = $defaultPath;
    }

    public function unwrap(WebRequest $request) {
        if ($request->getArguments()->has(WebRequest::$METHOD_KEY)) {
            $method = $request->getArguments()->get(WebRequest::$METHOD_KEY);
            $request->setMethod($method);
            $request->getArguments()->remove(WebRequest::$METHOD_KEY);
        } else {
            $request->setMethod(WebRequest::METHOD_GET);
        }

        foreach ($this->boxes as $name => $path) {
            $unwrapped = $request->copy();
            $unwrapped->setTarget($path);
            $unwrapped->getArguments()->clear();

            if ($request->getArguments()->has($name)) {
                /** @var Map $arguments */
                $arguments = $request->getArguments()->get($name);
                if ($arguments->has(self::TARGET_KEY)) {
                    $target = $arguments->get(self::TARGET_KEY);
                    $unwrapped->setTarget(Path::fromString($target));
                    $arguments->remove(self::TARGET_KEY);
                }
                $unwrapped->getArguments()->merge($arguments);
            }

            $this->responses[$name] = $this->router->route($unwrapped)->respond();
        }
    }

    public function wrap($name) {
        if (!$this->responses) {
            throw new \Exception("The Request needs to be unwrapped first.");
        }
        $wrapper = new Wrapper($name);
        return $wrapper->wrap($this->responses[$name]);
    }

}