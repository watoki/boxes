<?php
namespace spec\watoki\boxes;

use spec\watoki\boxes\fixtures\BoxFixture;
use watoki\scrut\Specification;

/**
 * @property BoxFixture box <-
 */
class UnwrapRequestsTest extends Specification {

    function testEmptyRequest() {
        $this->box->given_Responds('outer', 'Hello $inner');
        $this->box->given_Responds('inner', 'Inner');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello Inner');
    }

    function testChangeTarget() {
        $this->box->given_Responds('outer', 'Hello $inner');
        $this->box->given_Responds('inner', 'Inner');
        $this->box->given_Responds('other', 'Other');
        $this->box->given_Contains('outer', 'inner');

        $this->box->givenTheTargetArgumentOf_Is('inner', 'other');
        $this->box->givenAPathFrom_To('outer', 'other');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello Other');
    }

    function testUnwrapArguments() {
        $this->box->given_Responds('outer', 'Hello $inner');
        $this->box->given_Responds('inner', '$one $two');
        $this->box->given_Contains('outer', 'inner');
        $this->box->givenTheRequestArgument_Is('inner/one', 'My');
        $this->box->givenTheRequestArgument_Is('inner/two', 'World');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello My World');
    }

    function testRecursiveUnwrapping() {
        $this->box->given_Responds('one', 'Hello $two');
        $this->box->given_Responds('two', '$dos $three');
        $this->box->given_Responds('three', '$tres');
        $this->box->given_Contains('one', 'two');
        $this->box->given_Contains('two', 'three');
        $this->box->givenTheRequestArgument_Is('two/three/tres', 'World');
        $this->box->givenTheRequestArgument_Is('two/dos', 'There');

        $this->box->whenIGetTheResponseFrom('one');
        $this->box->thenTheResponseShouldBe('Hello There World');
    }

    function testBoxList() {
        $this->box->given_Responds('outer', '$list');
        $this->box->given_Responds('inner', '$foo');

        $this->box->given_ContainsACollection('outer', 'list');
        $this->box->given_HasIn_A_With('outer', 'list', 'inner', array());
        $this->box->given_HasIn_A_With('outer', 'list', 'inner', array());
        $this->box->given_HasIn_A_With('outer', 'list', 'inner', array('foo' => 'bar'));

        $this->box->givenTheRequestArgument_Is('list/0/foo', 'One');
        $this->box->givenTheRequestArgument_Is('list/1/foo', 'Two');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('One Two bar');
    }

    function testMethodFindsTarget() {
        $this->box->given_Responds('root', 'Hello $foo $bar');
        $this->box->given_Responds('foo', 'my');
        $this->box->given_Responds('bar', 'dear $baz');
        $this->box->given_Responds('baz', 'World');

        $this->box->given_Contains('root', 'foo');
        $this->box->given_Contains('root', 'bar');
        $this->box->given_Contains('bar', 'baz');

        $this->box->givenTheMethodIs('bar');
        $this->box->givenTheRequestArgument_Is('bar/baz/do', 'foo');

        $this->box->whenIGetTheResponseFrom('root');
        $this->box->thenTheResponseShouldBe('Hello my dear foo!');
    }

    function testPrimaryTargetIsDispatchedFirst() {
        $this->box->given_Responds('root', '$a $b');
        $this->box->given_Responds('a', '{$GLOBALS[\'foo\']}');
        $this->box->given_Responds('b', 'comes $c');
        $this->box->given_Responds('c', 'c');
        $this->box->given_HasTheSideEffect('c', '$GLOBALS["foo"] = "first";');

        $this->box->given_Contains('root', 'a');
        $this->box->given_Contains('root', 'b');
        $this->box->given_Contains('b', 'c');

        $this->box->givenTheRequestArgument_Is('_', 'b');
        $this->box->givenTheRequestArgument_Is('b/_', 'c');

        $this->box->whenIGetTheResponseFrom('root');
        $this->box->thenTheResponseShouldBe('first comes c');
    }

}