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

    /** @var array */
    public $arguments = array();

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

    public function givenTheTargetArgumentsOf_Is($path, $target) {
        $this->arguments[$path] = new Map(array(
            Shelf::TARGET_KEY => $target
        ));
    }

    public function whenIGetTheResponseFrom($path) {
        $request = new WebRequest(Url::fromString(''), new Path(), 'foo', new Map($this->arguments));
        $this->response = $this->boxes[$path]->respond($request);
    }

    public function thenTheResponseShouldBe($body) {
        $this->spec->assertEquals($body, $this->response->getBody());
    }

} 