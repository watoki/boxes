<?php
namespace spec\watoki\boxes;

use spec\watoki\boxes\fixtures\BoxFixture;
use watoki\scrut\Specification;

/**
 * @property BoxFixture box <-
 */
class KeepStateTest extends Specification {

    function testKeepStateOfSiblings() {
        $this->box->givenTheBoxContainer_Responding('a', '<a href="">A</a> $b $c');
        $this->box->givenTheBoxContainer_Responding('b', '<a href="x" target="c">B</a>');
        $this->box->givenTheBoxContainer_Responding('c', '<a href="">C</a>');

        $this->box->given_Contains('a', 'b');
        $this->box->given_Contains('a', 'c');

        $this->box->givenTheRequestArgument_Is('_d/foo', 'D');

        $this->box->whenIGetTheResponseFrom('a');
        $this->box->thenTheResponseShouldBe(
            '<a href="">A</a> ' .
            '<a href="?_d[foo]=D&_c[!]=x&_=c">B</a> ' .
            '<a href="?_d[foo]=D&_=c">C</a>');
    }

    function testKeepAllState() {
        $this->box->givenTheBoxContainer_Responding('a', '<a href="">A</a> $b $c');
        $this->box->givenTheBoxContainer_Responding('b', '<a href="">B</a> $f');
        $this->box->givenTheBoxContainer_Responding('c', '<a href="">C</a> $d $e');
        $this->box->givenTheBoxContainer_Responding('d', '<a href="">D</a>');
        $this->box->givenTheBoxContainer_Responding('e', '<a href="">E</a>');
        $this->box->givenTheBoxContainer_Responding('f', '<a href="">F</a>');

        $this->box->given_Contains('a', 'b');
        $this->box->given_Contains('a', 'c');
        $this->box->given_Contains('b', 'f');
        $this->box->given_Contains('c', 'd');
        $this->box->given_Contains('c', 'e');

        $this->box->givenTheRequestArgument_Is('foo', 'A');
        $this->box->givenTheRequestArgument_Is('_b/foo', 'B');
        $this->box->givenTheRequestArgument_Is('_b/_f/foo', 'F');
        $this->box->givenTheRequestArgument_Is('_c/foo', 'C');
        $this->box->givenTheRequestArgument_Is('_c/_d/foo', 'D');
        $this->box->givenTheRequestArgument_Is('_c/_e/foo', 'E');

        $this->box->whenIGetTheResponseFrom('a');
        $this->box->thenTheResponseShouldBe(
                '<a href="">A</a> ' .
                '<a href="?foo=A&_c[foo]=C&_c[_d][foo]=D&_c[_e][foo]=E&_=b">B</a> ' .
                '<a href="?foo=A&_c[foo]=C&_c[_d][foo]=D&_c[_e][foo]=E&_b[foo]=B&_b[_]=f&_=b">F</a> ' .
                '<a href="?foo=A&_b[foo]=B&_b[_f][foo]=F&_=c">C</a> ' .
                '<a href="?foo=A&_b[foo]=B&_b[_f][foo]=F&_c[foo]=C&_c[_e][foo]=E&_c[_]=d&_=c">D</a> ' .
                '<a href="?foo=A&_b[foo]=B&_b[_f][foo]=F&_c[foo]=C&_c[_d][foo]=D&_c[_]=e&_=c">E</a>');
    }

    function testDoNotKeepPrimaryTargetInState() {
        $this->box->givenTheBoxContainer_Responding('o', '$a');
        $this->box->givenTheBoxContainer_Responding('a', '<a href="">A</a> $b');
        $this->box->givenTheBoxContainer_Responding('b', '<a href="">B</a>');

        $this->box->given_Contains('o', 'a');
        $this->box->given_Contains('a', 'b');

        $this->box->givenTheRequestArgument_Is('_a/_', 'b');

        $this->box->whenIGetTheResponseFrom('o');
        $this->box->thenTheResponseShouldBe('<a href="?_=a">A</a> <a href="?_a[_]=b&_=a">B</a>');
    }

    /** I don't think this scenario is still required */
    function testDoNotKeepStateIfTargetChanges() {
        $this->box->givenTheBoxContainer_Responding('o', '$a');
        $this->box->givenTheBoxContainer_Responding('a', '<a href="y" target="a">A</a> $b');
        $this->box->givenTheBoxContainer_Responding('b', '<a href="">B</a>');

        $this->box->given_Contains('o', 'a');
        $this->box->given_Contains('a', 'b');

        $this->box->givenTheRequestArgument_Is('_a/_b/foo', 'B');

        $this->box->whenIGetTheResponseFrom('o');
        $this->box->thenTheResponseShouldBe('<a href="?_a[!]=y&_=a">A</a> <a href="?_a[_]=b&_=a">B</a>');
    }

    function testDoNotKeepChildStateIfTargetChanges() {
        $this->box->givenTheBoxContainer_Responding('o', '$a $b');
        $this->box->givenTheBoxContainer_Responding('a', '<a href="x" target="b">A</a>');
        $this->box->givenTheBoxContainer_Responding('b', '$c');
        $this->box->givenTheBoxContainer_Responding('c', 'C');

        $this->box->given_Contains('o', 'a');
        $this->box->given_Contains('o', 'b');
        $this->box->given_Contains('b', 'c');

        $this->box->givenTheRequestArgument_Is('_b/_c/foo', 'A');

        $this->box->whenIGetTheResponseFrom('o');
        $this->box->thenTheResponseShouldBe('<a href="?_b[!]=x&_=b">A</a> C');
    }

}