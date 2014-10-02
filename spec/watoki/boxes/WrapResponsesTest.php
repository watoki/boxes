<?php
namespace spec\watoki\boxes;

use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\protocol\Url;
use watoki\deli\Path;
use watoki\deli\Router;
use watoki\scrut\Specification;

class WrapResponsesTest extends Specification {

    function testNoWrappingNecessary() {
        $this->givenResource_Responds('inner', 'World');
        $this->givenResource_Responds('outer', 'Hello $inner');
        $this->given_Contains('outer', 'inner');

        $this->whenIGetTheResponseFrom('outer');
        $this->thenTheResponseShouldBe('Hello World');
    }

    function testLinkTarget() {
        $this->givenResource_Responds('inner', '<a href="here">Here</a>');
        $this->givenResource_Responds('outer', 'Go $inner');
        $this->given_Contains('outer', 'inner');

        $this->whenIGetTheResponseFrom('outer');
        $this->thenTheResponseShouldBe('Go <a href="?inner[!]=here">Here</a>');
    }

    function testLinkArguments() {
        $this->givenResource_Responds('inner', '<a href="?foo=bar">Here</a>');
        $this->givenResource_Responds('outer', 'Go $inner');
        $this->given_Contains('outer', 'inner');

        $this->whenIGetTheResponseFrom('outer');
        $this->thenTheResponseShouldBe('Go <a href="?inner[foo]=bar">Here</a>');
    }

    #########################################################################################

    /** @var null|WebResponse */
    private $response;

    /** @var array|TestBox[] */
    private $resources = array();

    private function givenResource_Responds($path, $response) {
        $this->resources[$path] = new TestBox($this->factory, $response);
    }

    private function given_Contains($outer, $inner) {
        $this->resources[$outer]->add($inner, $this->resources[$inner]);
    }

    private function whenIGetTheResponseFrom($path) {
        $request = new WebRequest(Url::fromString(''), new Path(), 'foo');
        $this->response = $this->resources[$path]->respond($request);
    }

    private function thenTheResponseShouldBe($string) {
        $this->assertEquals($string, $this->response->getBody());
    }

}