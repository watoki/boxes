<?php
namespace spec\watoki\boxes;

use spec\watoki\boxes\fixtures\BoxFixture;
use watoki\scrut\Specification;

/**
 * @property BoxFixture box <-
 */
class RedirectsTest extends Specification {

    function testAbsolutePath() {
        $this->box->givenTheBoxContainer('o');
        $this->box->givenTheBoxContainer('a');

        $this->box->given_Contains('o', 'a');

        $this->box->given_HasTheSideEffect('a', 'return \watoki\curir\responder\Redirecter::fromString("/some/where/b?foo=baz");');

        $this->box->givenTheContextIs("/some/where");
        $this->box->givenTheRequestArgument_Is('_a/foo', 'bar');

        $this->box->whenIGetTheResponseFrom('o');
        $this->box->thenTheResponseShouldBeARedirectionTo('/some/where?_a[!]=b&_a[foo]=baz&_=a');
    }

    function testWithState() {
        $this->box->givenTheBoxContainer('o');
        $this->box->givenTheBoxContainer('a');
        $this->box->givenTheBoxContainer('b');
        $this->box->givenTheBoxContainer('c');
        $this->box->givenTheBoxContainer('d');

        $this->box->given_Contains('o', 'a');
        $this->box->given_Contains('o', 'b');
        $this->box->given_Contains('b', 'c');

        $this->box->given_HasTheSideEffect('a', 'return \watoki\curir\responder\Redirecter::fromString("../b?foo=baz");');

        $this->box->givenTheRequestArgument_Is('foo', 'O');
        $this->box->givenTheRequestArgument_Is('_a/foo', 'A');
        $this->box->givenTheRequestArgument_Is('_a/me', 'you');
        $this->box->givenTheRequestArgument_Is('_b/foo', 'B');
        $this->box->givenTheRequestArgument_Is('_b/_c/foo', 'C');

        $this->box->whenIGetTheResponseFrom('o');
        $this->box->thenTheResponseShouldBeARedirectionTo('?foo=O&_b[foo]=B&_b[_c][foo]=C&_a[!]=b&_a[foo]=baz&_=a');
    }

    function testEmptyPath() {
        $this->box->givenTheBoxContainer('o');
        $this->box->givenTheBoxContainer('a');

        $this->box->given_Contains('o', 'a');

        $this->box->given_HasTheSideEffect('a', 'return \watoki\curir\responder\Redirecter::fromString("?foo=baz");');

        $this->box->whenIGetTheResponseFrom('o');
        $this->box->thenTheResponseShouldBeARedirectionTo('?_a[!]=a&_a[foo]=baz&_=a');
    }
} 