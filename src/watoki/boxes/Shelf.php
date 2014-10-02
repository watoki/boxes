<?php
namespace watoki\boxes;

use watoki\collections\Map;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\protocol\Url;
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

    /** @var array|Path[] default paths indexed by box name */
    private $boxes = array();

    public function __construct(Router $router) {
        $this->router = $router;
    }

    public function set($name, $defaultPath) {
        $this->boxes[$name] = $defaultPath;
    }

    public function unwrap(WebRequest $request) {
        foreach ($this->boxes as $name => $path) {
            $unwrapped = $request->copy();
            $unwrapped->setTarget($path);
            $unwrapped->getArguments()->clear();

            if ($request->getArguments()->has($name)) {
                /** @var Map $arguments */
                $arguments = $request->getArguments()->get($name);
                if ($arguments->has(self::TARGET_KEY)) {
                    $unwrapped->setTarget(Path::fromString($arguments->get(self::TARGET_KEY)));
                    $arguments->remove(self::TARGET_KEY);
                }
                $unwrapped->getArguments()->merge($arguments);
            }

            $this->responses[$name] = $this->router->route($unwrapped)->respond();
        }
    }

    public function wrap($name) {
        return $this->wrapResponse($this->responses[$name], $name);
    }

    private function wrapResponse(WebResponse $response, $name) {
        $parser = new Parser($response->getBody());

        foreach ($parser->getNodes() as $node) {
            if ($node instanceof Element && $node->getName() == 'a') {
                $target = Url::fromString($node->getAttribute('href')->getValue());
                $box = $name;

                if ($node->getAttribute('target')) {
                    $box = $node->getAttribute('target')->getValue();
                    $node->getAttributes()->removeElement($node->getAttribute('target'));
                }

                $params = new Map();
                if ($target->getPath()->toString()) {
                    $params->set(self::TARGET_KEY, $target->getPath()->toString());
                }
                $params->merge($target->getParameters());

                $wrapped = Url::fromString('');
                $wrapped->getParameters()->set($box, $params);
                $node->setAttribute('href', $wrapped->toString());
            }
        }

        $printer = new Printer();
        return $printer->printNodes($parser->getNodes());
    }

}