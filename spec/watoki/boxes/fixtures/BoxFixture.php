<?php
namespace spec\watoki\boxes\fixtures;

use watoki\boxes\Shelf;
use watoki\collections\Map;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\protocol\Url;
use watoki\deli\Path;
use watoki\deli\target\RespondingTarget;
use watoki\factory\Factory;
use watoki\scrut\Fixture;

class BoxFixture extends Fixture {

    /** @var WebResponse */
    public $response;

    /** @var array|TestBox[] */
    public $boxes = array();

    /** @var Map|Map[] */
    public $arguments;

    public function setUp() {
        parent::setUp();
        $this->arguments = new Map();
    }

    public function given_Responds($boxName, $boxResponse) {
        $this->boxes[$boxName] = new TestBox(new Factory(), $boxResponse);
    }

    public function given_Contains($outer, $inner) {
        $this->boxes[$outer]->add($inner, $this->boxes[$inner]);
    }

    public function givenAPathFrom_To($start, $target) {
        $this->boxes[$start]->router->set(Path::fromString($target),
            RespondingTarget::factory($this->spec->factory, $this->boxes[$target]));
    }

    public function givenTheTargetArgumentOf_Is($box, $target) {
        $this->givenTheArgument_WithValue($box . '/' .Shelf::TARGET_KEY, $target);
    }

    public function givenTheArgument_WithValue($keyPath, $value) {
        $keys = explode('/', $keyPath);
        $last = array_pop($keys);

        /** @var Map $arguments */
        $arguments = $this->arguments;
        foreach ($keys as $key) {
            if (!$arguments->has($key)) {
                $arguments->set($key, new Map());
            }
            $arguments = $arguments->get($key);
        }
        $arguments->set($last, $value);
    }

    public function whenIGetTheResponseFrom($path) {
        $request = new WebRequest(Url::fromString(''), new Path(), 'foo', $this->arguments);
        $this->response = $this->boxes[$path]->respond($request);
    }

    public function thenTheResponseShouldBe($body) {
        $this->spec->assertEquals(trim($body), trim($this->response->getBody()));
    }

} 