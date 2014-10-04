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

    /** @var WebRequest */
    private $request;

    public function setUp() {
        parent::setUp();
        $this->request = new WebRequest(Url::fromString(''), new Path(), 'get');
    }

    public function given_Responds($boxName, $boxResponse) {
        $this->boxes[$boxName] = new TestBox(new Factory(), $boxResponse);
    }

    public function given_Contains($outer, $inner) {
        $this->given_Contains_With($outer, $inner, array());
    }

    public function given_Contains_With($outer, $inner, $args) {
        $this->boxes[$outer]->add($inner, $this->boxes[$inner], $args);
    }

    public function given_ContainsA_With($outer, $inner, $args) {
        $this->boxes[$outer]->addToCollection($inner, $this->boxes[$inner], $args);
    }

    public function givenAPathFrom_To($start, $target) {
        $this->boxes[$start]->router->set(Path::fromString($target),
            RespondingTarget::factory($this->spec->factory, $this->boxes[$target]));
    }

    public function givenTheTargetArgumentOf_Is($box, $target) {
        $this->givenTheRequestArgument_Is($box . '/' .Shelf::TARGET_KEY, $target);
    }

    public function givenTheRequestArgument_Is($keyPath, $value) {
        $keys = explode('/', $keyPath);
        $last = array_pop($keys);

        $arguments = $this->request->getArguments();
        foreach ($keys as $key) {
            if (!$arguments->has($key)) {
                $arguments->set($key, new Map());
            }
            $arguments = $arguments->get($key);
        }
        $arguments->set($last, $value);
    }

    public function givenTheMethodIs($method) {
        $this->request->setMethod($method);
    }

    public function whenIGetTheResponseFrom($path) {
        $this->response = $this->boxes[$path]->respond($this->request);
    }

    public function thenTheResponseShouldBe($body) {
        $this->spec->assertEquals(trim($body), trim($this->response->getBody()));
    }

} 