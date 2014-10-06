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

        $this->box->givenTheRequestArgument_Is('_inner/!', 'other');
        $this->box->givenAPathFrom_To('outer', 'other');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello Other');
    }

    function testUnwrapArguments() {
        $this->box->given_Responds('outer', 'Hello $inner');
        $this->box->given_Responds('inner', '$one $two');

        $this->box->given_Contains('outer', 'inner');

        $this->box->givenTheRequestArgument_Is('_inner/one', 'My');
        $this->box->givenTheRequestArgument_Is('_inner/two', 'World');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello My World');
    }

    function testRecursiveUnwrapping() {
        $this->box->given_Responds('one', 'Hello $two');
        $this->box->given_Responds('two', '$dos $three');
        $this->box->given_Responds('three', '$tres');

        $this->box->given_Contains('one', 'two');
        $this->box->given_Contains('two', 'three');

        $this->box->givenTheRequestArgument_Is('_two/_three/tres', 'World');
        $this->box->givenTheRequestArgument_Is('_two/dos', 'There');

        $this->box->whenIGetTheResponseFrom('one');
        $this->box->thenTheResponseShouldBe('Hello There World');
    }

    function testBoxList() {
        $this->box->given_Responds('outer', '$list');
        $this->box->given_Responds('inner', '$foo');

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
        $this->box->given_Responds('root', 'Hello $foo $bar');
        $this->box->given_Responds('foo', 'my');
        $this->box->given_Responds('bar', 'dear $baz');
        $this->box->given_Responds('baz', 'World');

        $this->box->given_Contains('root', 'foo');
        $this->box->given_Contains('root', 'bar');
        $this->box->given_Contains('bar', 'baz');

        $this->box->givenTheMethodIs('bar');
        $this->box->givenTheRequestArgument_Is('_bar/_baz/do', 'foo');

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

    function testListAsPrimaryTarget() {
        $this->box->given_Responds('o', '$inner');
        $this->box->given_Responds('item', '$name');

        $this->box->given_ContainsACollection('o', 'inner');
        $this->box->given_HasIn_A_With('o', 'inner', 'item', array('name' => 'One'));
        $this->box->given_HasIn_A_With('o', 'inner', 'item', array('name' => 'Two'));

        $this->box->givenTheRequestArgument_Is('_', 'inner');
        $this->box->givenTheRequestArgument_Is('_inner/_', '1');

        $this->box->whenIGetTheResponseFrom('o');
        $this->box->thenTheResponseShouldBe('One Two');
    }

    function testRedirects() {
        $this->box->given_Responds('o', '$a -> $b');
        $this->box->given_Responds('a', '$foo');
        $this->box->given_Responds('b', 'B');
        $this->box->given_HasTheSideEffect('b', 'return \watoki\curir\responder\Redirecter::fromString("../c");');
        $this->box->given_Responds('c', 'C');
        $this->box->given_HasTheSideEffect('c', 'return \watoki\curir\responder\Redirecter::fromString("../a?foo=baz");');

        $this->box->given_Contains('o', 'a');
        $this->box->given_Contains('o', 'b');

        $this->box->givenAPathFrom_To('o', 'c');

        $this->box->givenTheRequestArgument_Is('_a/foo', 'bar');

        $this->box->whenIGetTheResponseFrom('o');
        $this->box->thenTheResponseShouldBe('bar -> baz');
    }

}