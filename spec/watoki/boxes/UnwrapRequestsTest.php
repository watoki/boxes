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

        $this->box->givenTheTargetArgumentsOf_Is('inner', 'other');
        $this->box->givenAPathFrom_To('outer', 'other');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello Other');
    }

    function testUnwrapArguments() {
        $this->box->given_Responds('outer', 'Hello $inner');
        $this->box->given_Responds('inner', '$one $two');
        $this->box->given_Contains('outer', 'inner');
        $this->box->given_HasTheArgument_WithValue('inner', 'one', 'My');
        $this->box->given_HasTheArgument_WithValue('inner', 'two', 'World');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello My World');
    }

}