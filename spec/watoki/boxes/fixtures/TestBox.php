<?php
namespace spec\watoki\boxes\fixtures;

use watoki\boxes\Box;
use watoki\boxes\BoxedRequest;
use watoki\boxes\Shelf;
use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\curir\delivery\WebRequest;
use watoki\deli\Path;
use watoki\deli\router\DynamicRouter;
use watoki\deli\target\ObjectTarget;
use watoki\deli\target\RespondingTarget;
use watoki\factory\Factory;

class TestBox extends Box {

    /** @var string */
    private $response;

    /** @var DynamicRouter */
    public $router;

    /** @var array|string[] */
    private $boxes = array();

    /** @var array|Liste[] */
    private $collections = array();

    function __construct(Factory $factory, $response) {
        parent::__construct($factory);
        $this->router = new DynamicRouter();
        $this->shelf = new Shelf($this->router);

        $this->response = $response;

        $this->router->set(new Path(), ObjectTarget::factory($this->factory, $this));
    }

    public function add($name, Box $box, $args) {
        $this->addBox($name, $box);
        $request = new BoxedRequest(
            Path::fromString($name),
            WebRequest::METHOD_GET,
            new Map($args));
        $this->shelf->set($name, $request);
    }

    public function addToCollection($name, Box $box, $query) {
        $this->addBox($name, $box);

        if (!isset($this->collections[$name])) {
            $this->collections[$name] = new Liste();
            $this->shelf->setList($name, $this->collections[$name]);
        }
        $this->collections[$name]->append(Path::fromString($name . $query));
    }

    private function addBox($name, Box $box) {
        $this->boxes[] = $name;
        $this->router->set(Path::fromString($name),
            RespondingTarget::factory($this->factory, $box));
    }

    /**
     * @param WebRequest $request <-
     * @return string
     */
    public function doGet(WebRequest $request) {
        $model = array();
        foreach ($request->getArguments() as $key => $value) {
            $model[$key] = $value;
        }
        foreach ($this->boxes as $box) {
            $model[$box] = $this->shelf->box($box);
        }
        return $this->render($model);
    }

    public function doFoo() {
        return 'foo!';
    }

    private function render($model) {
        foreach ($model as $key => $value) {
            $$key = $value;
        }
        $template = str_replace('"', '\"', $this->response);
        return eval('return "' . $template . '";');
    }

}