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
        $this->box->givenTheArgument_WithValue('inner/one', 'My');
        $this->box->givenTheArgument_WithValue('inner/two', 'World');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello My World');
    }

    function testRecursiveUnwrapping() {
        $this->box->given_Responds('one', 'Hello $two');
        $this->box->given_Responds('two', '$dos $three');
        $this->box->given_Responds('three', '$tres');
        $this->box->given_Contains('one', 'two');
        $this->box->given_Contains('two', 'three');
        $this->box->givenTheArgument_WithValue('two/three/tres', 'World');
        $this->box->givenTheArgument_WithValue('two/dos', 'There');

        $this->box->whenIGetTheResponseFrom('one');
        $this->box->thenTheResponseShouldBe('Hello There World');
    }

}