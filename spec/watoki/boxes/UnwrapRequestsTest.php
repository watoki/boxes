<?php
namespace spec\watoki\boxes;

use watoki\collections\Map;
use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\protocol\Url;
use watoki\deli\Path;
use watoki\deli\target\RespondingTarget;
use watoki\factory\Factory;
use watoki\scrut\Specification;

class UnwrapRequestsTest extends Specification {

    /** @var WebResponse */
    private $response;

    /** @var array|TestBox[] */
    private $boxes = array();

    /** @var array */
    private $arguments = array();

    function testEmptyRequest() {
        $this->given_Responds('outer', 'Hello $inner');
        $this->given_Responds('inner', 'Inner');
        $this->given_Contains('outer', 'inner');

        $this->whenIGetTheResponseFrom('outer');
        $this->thenTheResponseShouldBe('Hello Inner');
    }

    function testChangeTarget() {
        $this->given_Responds('outer', 'Hello $inner');
        $this->given_Responds('inner', 'Inner');
        $this->given_Contains('outer', 'inner');

        $this->givenTheTargetParameterOf_Is('inner', 'other');

        $this->boxes['outer']->router->set(Path::fromString('other'),
            RespondingTarget::factory($this->factory, new TestBox($this->factory, 'Other')));

        $this->whenIGetTheResponseFrom('outer');
        $this->thenTheResponseShouldBe('Hello Other');
    }

    /**
     * @param $boxName
     * @param $boxResponse
     */
    private function given_Responds($boxName, $boxResponse) {
        $this->boxes[$boxName] = new TestBox(new Factory(), $boxResponse);
    }

    /**
     * @param $outer
     * @param $inner
     */
    private function given_Contains($outer, $inner) {
        $this->boxes[$outer]->add($inner, $this->boxes[$inner]);
    }

    /**
     * @param $path
     */
    private function whenIGetTheResponseFrom($path) {
        $request = new WebRequest(Url::fromString(''), new Path(), 'foo', new Map($this->arguments));
        $this->response = $this->boxes[$path]->respond($request);
    }

    /**
     * @param $body
     */
    private function thenTheResponseShouldBe($body) {
        $this->assertEquals($body, $this->response->getBody());
    }

    private function givenTheTargetParameterOf_Is($path, $target) {
        $this->arguments[$path] = new Map(array(
            '!' => $target
        ));
    }

}