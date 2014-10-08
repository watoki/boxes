<?php
namespace spec\watoki\boxes;

use spec\watoki\boxes\fixtures\BoxFixture;
use watoki\scrut\Specification;

/**
 * @property BoxFixture box <-
 */
class UnwrapRequestsTest extends Specification {

    function testEmptyRequest() {
        $this->box->givenTheBoxContainer_Responding('outer', 'Hello $inner');
        $this->box->givenTheBoxContainer_Responding('inner', 'Inner');

        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello Inner');
    }

    function testChangeTarget() {
        $this->box->givenTheBoxContainer_Responding('outer', 'Hello $inner');
        $this->box->givenTheBoxContainer_Responding('inner', 'Inner');
        $this->box->givenTheBoxContainer_Responding('other', 'Other');

        $this->box->given_Contains('outer', 'inner');

        $this->box->givenTheRequestArgument_Is('_inner/!', 'other');
        $this->box->givenAPathFrom_To('outer', 'other');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello Other');
    }

    function testUnwrapArguments() {
        $this->box->givenTheBoxContainer_Responding('outer', 'Hello $inner');
        $this->box->givenTheBoxContainer_Responding('inner', '$one $two');

        $this->box->given_Contains('outer', 'inner');

        $this->box->givenTheRequestArgument_Is('_inner/one', 'My');
        $this->box->givenTheRequestArgument_Is('_inner/two', 'World');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello My World');
    }

    function testRecursiveUnwrapping() {
        $this->box->givenTheBoxContainer_Responding('one', 'Hello $two');
        $this->box->givenTheBoxContainer_Responding('two', '$dos $three');
        $this->box->givenTheBoxContainer_Responding('three', '$tres');

        $this->box->given_Contains('one', 'two');
        $this->box->given_Contains('two', 'three');

        $this->box->givenTheRequestArgument_Is('_two/_three/tres', 'World');
        $this->box->givenTheRequestArgument_Is('_two/dos', 'There');

        $this->box->whenIGetTheResponseFrom('one');
        $this->box->thenTheResponseShouldBe('Hello There World');
    }

    function testBoxList() {
        $this->box->givenTheBoxContainer_Responding('outer', '$list');
        $this->box->givenTheBoxContainer_Responding('inner', '$foo');

        $this->box->given_ContainsACollection('outer', 'list');
        $this->box->given_HasIn_A_With('outer', 'list', 'inner', array('foo' => 'baz'));
        $this->box->given_HasIn_A_With('outer', 'list', 'inner', array('foo' => 'baz'));
        $this->box->given_HasIn_A_With('outer', 'list', 'inner', array('foo' => 'bar'));

        $this->box->givenTheRequestArgument_Is('_list/_0/foo', 'One');
        $this->box->givenTheRequestArgument_Is('_list/_1/foo', 'Two');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('One Two bar');
    }

    function testMethodFindsTarget() {
        $this->box->givenTheBoxContainer_Responding('root', 'Hello $foo $bar');
        $this->box->givenTheBoxContainer_Responding('foo', 'my');
        $this->box->givenTheBoxContainer_Responding('bar', 'dear $baz');
        $this->box->givenTheBoxContainer_Responding('baz', 'World');

        $this->box->given_Contains('root', 'foo');
        $this->box->given_Contains('root', 'bar');
        $this->box->given_Contains('bar', 'baz');

        $this->box->givenTheMethodIs('bar');
        $this->box->givenTheRequestArgument_Is('_bar/_baz/do', 'foo');

        $this->box->whenIGetTheResponseFrom('root');
        $this->box->thenTheResponseShouldBe('Hello my dear foo!');
    }

    function testPrimaryTargetIsDispatchedFirst() {
        $this->box->givenTheBoxContainer_Responding('root', '$a $b');
        $this->box->givenTheBoxContainer_Responding('a', '{$GLOBALS[\'foo\']}');
        $this->box->givenTheBoxContainer_Responding('b', 'comes $c');
        $this->box->givenTheBoxContainer_Responding('c', 'c');
        $this->box->given_HasTheSideEffect('c', '$GLOBALS["foo"] = "first";');

        $this->box->given_Contains('root', 'a');
        $this->box->given_Contains('root', 'b');
        $this->box->given_Contains('b', 'c');

        $this->box->givenTheRequestArgument_Is('_', 'b');
        $this->box->givenTheRequestArgument_Is('b/_', 'c');

        $this->box->whenIGetTheResponseFrom('root');
        $this->box->thenTheResponseShouldBe('first comes c');
    }

    function testListAsPrimaryTarget() {
        $this->box->givenTheBoxContainer_Responding('o', '$inner');
        $this->box->givenTheBoxContainer_Responding('item', '$name');

        $this->box->given_ContainsACollection('o', 'inner');
        $this->box->given_HasIn_A_With('o', 'inner', 'item', array('name' => 'One'));
        $this->box->given_HasIn_A_With('o', 'inner', 'item', array('name' => 'Two'));

        $this->box->givenTheRequestArgument_Is('_', 'inner');
        $this->box->givenTheRequestArgument_Is('_inner/_', '1');

        $this->box->whenIGetTheResponseFrom('o');
        $this->box->thenTheResponseShouldBe('One Two');
    }

    function testRedirects() {
        $this->box->givenTheBoxContainer('o');
        $this->box->givenTheBoxContainer('a');

        $this->box->given_Contains('o', 'a');

        $this->box->given_HasTheSideEffect('a', 'return \watoki\curir\responder\Redirecter::fromString("../b?foo=baz");');

        $this->box->givenTheRequestArgument_Is('_a/foo', 'bar');

        $this->box->whenIGetTheResponseFrom('o');
        $this->box->thenTheResponseShouldBeARedirectionTo('?_a[!]=b&_a[foo]=baz&_=a');
    }

    function testRedirectsWithState() {
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
        $this->box->thenTheResponseShouldBeARedirectionTo('?_a[!]=b&_a[foo]=baz&_=a&foo=O&_b[foo]=B&_b[_c][foo]=C');
    }

}