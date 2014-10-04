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

    /** @var array|Path[] paths used for each box */
    private $paths = array();

    /** @var WebRequest */
    private $originalRequest;

    public function __construct(Router $router) {
        $this->router = $router;
    }

    public function set($name, $defaultPath) {
        $this->boxes[$name] = $defaultPath;
    }

    public function unbox(BoxedRequest $request) {
        $this->originalRequest = $request->getOriginalRequest();

        foreach ($this->boxes as $name => $path) {
            $this->paths[$name] = $path;

            $unboxed = $request->copy();
            $unboxed->setTarget($path);
            $unboxed->getArguments()->clear();

            if ($request->getArguments()->has($name)) {
                /** @var Map $arguments */
                $arguments = $request->getArguments()->get($name);
                if ($arguments->has(self::TARGET_KEY)) {
                    $target = Path::fromString($arguments->get(self::TARGET_KEY));
                    $this->paths[$name] = $target;
                    $unboxed->setTarget($target);
                }
                if ($arguments->has(WebRequest::$METHOD_KEY)) {
                    $method = $arguments->get(WebRequest::$METHOD_KEY);
                    $unboxed->setMethod($method);
                    $arguments->remove(WebRequest::$METHOD_KEY);
                }
                $unboxed->getArguments()->merge($arguments);
            }

            $this->responses[$name] = $this->router->route($unboxed)->respond();
        }
    }

    public function box($name) {
        if (!$this->responses) {
            throw new \Exception("The Request needs to be unwrapped first.");
        }
        $wrapper = new Boxer($name, $this->paths[$name], $this->originalRequest->getArguments());
        return $wrapper->wrap($this->responses[$name]);
    }

}