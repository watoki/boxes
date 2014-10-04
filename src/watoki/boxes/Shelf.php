<?php
namespace watoki\boxes;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\collections\Set;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\deli\Path;
use watoki\deli\Router;
use watoki\dom\Element;
use watoki\dom\Parser;
use watoki\dom\Printer;

class Shelf {

    const TARGET_KEY = '!';

    /** @var Router */
    private $router;

    /** @var array|WebResponse[] */
    private $responses = array();

    /** @var array|BoxedRequest[]|Liste[] default Requests indexed by box name */
    private $boxes = array();

    /** @var array|Path[] paths used for each box */
    private $targets = array();

    /** @var WebRequest */
    private $originalRequest;

    /** @var Set|Element[] */
    private $headElements;

    public function __construct(Router $router) {
        $this->router = $router;
        $this->headElements = new Set();
    }

    public function set($name, BoxedRequest $default) {
        $this->boxes[$name] = $default;
    }

    /**
     * @param $name
     * @param Liste|Path[] $defaultPaths
     */
    public function setList($name, Liste $defaultPaths) {
        $this->boxes[$name] = $defaultPaths;
    }

    public function unbox(BoxedRequest $request) {
        $this->originalRequest = $request->getOriginalRequest();

        foreach ($this->boxes as $name => $defaultRequest) {
            if ($defaultRequest instanceof Liste) {
                foreach ($defaultRequest as $i => $iDefaultRequest) {
                    $this->unboxBoxCollection($request, $iDefaultRequest, $name, $i);
                }
            } else if ($defaultRequest instanceof BoxedRequest) {
                $this->unboxBox($request, $defaultRequest, $name);
            }
        }
    }

    private function unboxBox(BoxedRequest $request, BoxedRequest $defaultRequest, $name) {
        $this->targets[$name] = $defaultRequest->getTarget();

        $unboxed = $defaultRequest->copy();
        $unboxed->setOriginalRequest($request->getOriginalRequest());

        if ($request->getArguments()->has($name)) {
            $this->parseArguments($unboxed, $name, $request->getArguments()->get($name));
        }

        $this->responses[$name] = $this->router->route($unboxed)->respond();
    }

    private function unboxBoxCollection(BoxedRequest $request, BoxedRequest $defaultRequest, $name, $i) {
        $this->targets[$name][] = $defaultRequest->getTarget();

        $unboxed = $defaultRequest->copy();
        $unboxed->setOriginalRequest($request->getOriginalRequest());

        if ($request->getArguments()->has($name)) {
            /** @var Map $arguments */
            $arguments = $request->getArguments()->get($name);
            if ($arguments->has($i)) {
                $this->parseArguments($unboxed, $name, $arguments->get($i));
            }
        }

        $this->responses[$name][] = $this->router->route($unboxed)->respond();
    }

    private function parseArguments(BoxedRequest $unboxed, $name, Map $arguments) {
        $unboxed->getArguments()->clear();
        $unboxed->getArguments()->merge($arguments);

        if ($arguments->has(self::TARGET_KEY)) {
            $target = Path::fromString($arguments->get(self::TARGET_KEY));
            $this->targets[$name] = $target;
            $unboxed->setTarget($target);
        }
        if ($arguments->has(WebRequest::$METHOD_KEY)) {
            $method = $arguments->get(WebRequest::$METHOD_KEY);
            $unboxed->setMethod($method);
            $arguments->remove(WebRequest::$METHOD_KEY);
        }
    }

    public function box($name) {
        if (!$this->responses) {
            throw new \Exception("The Request needs to be unboxed first.");
        }

        if (is_array($this->responses[$name])) {
            $boxes = array();
            foreach ($this->responses[$name] as $i => $response) {
                $boxes[] = $this->boxResponse($name . "[$i]", $response, $this->targets[$name][$i]);
            }
            return $boxes;
        }

        return $this->boxResponse($name, $this->responses[$name], $this->targets[$name]);
    }

    private function boxResponse($name, $response, Path $target) {
        $boxer = new Boxer($name, $target, $this->originalRequest->getArguments());
        $box = $boxer->box($response);
        $this->headElements->putAll($boxer->getHeadElements());
        return $box;
    }

    public function mergeHeaders(WebResponse $into) {
        $parser = new Parser($into->getBody());
        $html = $parser->findElement('html');
        if ($html && $html->findChildElement('head')) {
            $head = $html->findChildElement('head');

            foreach ($this->headElements as $new) {
                if (!$this->isAlreadyIn($new, $head->getChildElements())) {
                    $head->getChildren()->append($new);
                }
            }

            $printer = new Printer();
            $into->setBody($printer->printNodes($parser->getNodes()));
        }
        return $into;
    }

    /**
     * @param Element $element
     * @param Element[] $in
     * @return bool
     */
    private function isAlreadyIn(Element $element, $in) {
        foreach ($in as $old) {
            if ($old->equals($element)) {
                return true;
            }
        }
        return false;
    }

}