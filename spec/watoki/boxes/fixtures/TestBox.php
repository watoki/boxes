<?php
namespace spec\watoki\boxes\fixtures;

use watoki\boxes\Box;
use watoki\boxes\BoxCollection;
use watoki\boxes\BoxContainer;
use watoki\boxes\Wrapper;
use watoki\collections\Map;
use watoki\curir\delivery\WebRequest;
use watoki\deli\Path;
use watoki\deli\router\DynamicRouter;
use watoki\deli\target\ObjectTarget;
use watoki\factory\Factory;

class TestBox extends BoxContainer {

    /** @var string */
    private $response;

    /** @var DynamicRouter */
    public $router;

    /** @var array|BoxCollection[] */
    private $collections = array();

    public $sideEffect = '';

    function __construct(Factory $factory, $response) {
        parent::__construct($factory);
        $this->router = new DynamicRouter();
        $this->response = $response;
        $this->router->set(new Path(), ObjectTarget::factory($this->factory, $this));
    }

    protected function registerBoxes() {}

    public function add($name, BoxContainer $box, $args) {
        $this->setRoute($name, $box);
        $this->boxes->set($name, new Box(Path::fromString($name), new Map($args)));
    }

    public function addCollection($name, array $boxes) {
        $this->collections[$name] = parent::addCollection($name, $boxes);
    }

    public function addToCollection($collection, $name, BoxContainer $box, $args) {
        $this->setRoute($name, $box);
        $this->collections[$collection]->add(new Box(Path::fromString($name), new Map($args)));
    }

    private function setRoute($name, BoxContainer $box) {
        $this->router->set(Path::fromString($name), ObjectTarget::factory($this->factory, $box));
    }

    /**
     * @param WebRequest $request <-
     * @return string
     */
    public function doGet(WebRequest $request) {
        $model = array_merge(
            $request->getArguments()->toArray(),
            $this->boxes->getModel()
        );
        return $this->render($model);
    }

    public function doFoo() {
        return 'foo!';
    }

    private function render($model) {
        foreach ($model as $key => $value) {
            if (substr($key, 0, 1) == Wrapper::$PREFIX) {
                continue;
            } else if (is_array($value)) {
                $$key = implode(' ', $value);
            } else {
                $$key = $value;
            }
        }
        $template = str_replace('"', '\"', $this->response);
        $code = $this->sideEffect . 'return "' . $template . '";';
        return eval($code);
    }
}