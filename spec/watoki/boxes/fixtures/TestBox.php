<?php
namespace spec\watoki\boxes\fixtures;

use watoki\boxes\Box;
use watoki\boxes\Shelf;
use watoki\deli\Path;
use watoki\deli\Responding;
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

    function __construct(Factory $factory, $response) {
        parent::__construct($factory);
        $this->router = new DynamicRouter();
        $this->shelf = new Shelf($this->router);

        $this->response = $response;

        $this->router->set(new Path(), ObjectTarget::factory($this->factory, $this));
    }

    public function add($name, Responding $box) {
        $this->boxes[] = $name;
        $this->router->set(Path::fromString($name),
            RespondingTarget::factory($this->factory, $box));
        $this->shelf->set($name, Path::fromString($name));
    }

    public function doFoo() {
        $model = array();
        foreach ($this->boxes as $box) {
            $model[$box] = $this->shelf->wrap($box);
        }
        return $this->render($model);
    }

    private function render($model) {
        foreach ($model as $key => $value) {
            $$key = $value;
        }
        $template = str_replace('"', '\"', $this->response);
        return eval('return "' . $template . '";');
    }

}