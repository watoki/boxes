<?php
namespace spec\watoki\boxes;

use spec\watoki\boxes\fixtures\BoxFixture;
use watoki\deli\Router;
use watoki\scrut\Specification;

/**
 * @property BoxFixture box <-
 */
class WrapResponsesTest extends Specification {

    function testNoWrappingNecessary() {
        $this->box->given_Responds('inner', 'World');
        $this->box->given_Responds('outer', 'Hello $inner');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello World');
    }

    function testLinkTarget() {
        $this->box->given_Responds('inner', '<a href="here">Here</a>');
        $this->box->given_Responds('outer', 'Go $inner');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Go <a href="?inner[!]=here">Here</a>');
    }

    function testLinkArguments() {
        $this->box->given_Responds('inner', '<a href="?foo=bar">Here</a>');
        $this->box->given_Responds('outer', 'Go $inner');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Go <a href="?inner[foo]=bar">Here</a>');
    }

    function testLinkTargetsOtherBox() {
        $this->box->given_Responds('inner', '<a href="there" target="other">Two</a>');
        $this->box->given_Responds('outer', 'Go $inner');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Go <a href="?other[!]=there">Two</a>');
    }

    function testRecursiveWrapping() {
        $this->box->given_Responds('one', 'One $two');
        $this->box->given_Responds('two', 'Two $three');
        $this->box->given_Responds('three', '<a href="there?me=you">Three</a>');

        $this->box->given_Contains('one', 'two');
        $this->box->given_Contains('two', 'three');

        $this->box->whenIGetTheResponseFrom('one');
        $this->box->thenTheResponseShouldBe(
            'One Two <a href="?two[three][!]=there&two[three][me]=you">Three</a>');
    }

}