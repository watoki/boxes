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
        $this->box->thenTheResponseShouldBe('Go <a href="?_inner[!]=here&_=inner">Here</a>');
    }

    function testLinkArguments() {
        $this->box->given_Responds('inner', '<a href="?foo=bar">Here</a>');
        $this->box->given_Responds('outer', 'Go $inner');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Go <a href="?_inner[foo]=bar&_=inner">Here</a>');
    }

    function testLinkTargetsOtherBox() {
        $this->box->given_Responds('inner', '<a href="there" target="other">Two</a>');
        $this->box->given_Responds('outer', 'Go $inner');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Go <a href="?_other[!]=there&_=inner">Two</a>');
    }

    function testRecursiveWrapping() {
        $this->box->given_Responds('one', 'One $two');
        $this->box->given_Responds('two', '<a href="here?foo=bar">Two</a> $three');
        $this->box->given_Responds('three', '<a href="there?me=you">Three</a>');

        $this->box->given_Contains('one', 'two');
        $this->box->given_Contains('two', 'three');

        $this->box->whenIGetTheResponseFrom('one');
        $this->box->thenTheResponseShouldBe(
                'One <a href="?_two[!]=here&_two[foo]=bar&_=two">Two</a> ' .
                '<a href="?_two[_three][!]=there&_two[_three][me]=you&_two[_]=three&_=two">Three</a>');
    }

    function testFormWithoutActionAndMethod() {
        $this->box->given_Responds('outer', '$inner');
        $this->box->given_Responds('inner', '<form></form>');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('<form action="?_inner[do]=post&_=inner"></form>');
    }

    function testFormWithAction() {
        $this->box->given_Responds('outer', '$inner');
        $this->box->given_Responds('inner', '
            <form action="there?me=you" method="get">
                <input name="foo" value="bar"/>
                <textarea name="foo[one]"></textarea>
                <select name="foo[two]"></select>
                <button name="do" value="that">Go</button>
            </form>');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('
            <form action="?_inner[!]=there&_inner[me]=you&_inner[do]=get&_=inner" method="get">
                <input name="_inner[foo]" value="bar"/>
                <textarea name="_inner[foo][one]"></textarea>
                <select name="_inner[foo][two]"></select>
                <button name="_inner[do]" value="that">Go</button>
            </form>');
    }

    function testWrapBody() {
        $this->box->given_Responds('outer', '
            <html>
                <head><title>Hello World</title></head>
                <body><p>$inner</p></body>
            </html>');
        $this->box->given_Responds('inner', '
            <html>
                <head><title>Ignored</title></head>
                <body><em>Hello World</em></body>
            </html>');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('
            <html>
                <head><title>Hello World</title></head>
                <body><p><em>Hello World</em></p></body>
            </html>');
    }

    function testAlwaysUnpackBody() {
        $this->box->given_Responds('outer', '$inner');
        $this->box->given_Responds('inner', '<html><body>Hello World</body></html>');

        $this->box->given_Contains('outer', 'inner');
        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello World');
    }

    function testComplexDom() {
        $this->box->given_Responds('outer', '$inner');
        $this->box->given_Responds('inner', '
            <html><body><p>Hello</p>
                <div><form action="here">
                    <a href="there?one=two">Click</a>
                </form></div>
            </body></html>');

        $this->box->given_Contains('outer', 'inner');
        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('<p>Hello</p>
                <div><form action="?_inner[!]=here&_inner[do]=post&_=inner">
                    <a href="?_inner[!]=there&_inner[one]=two&_=inner">Click</a>
                </form></div>');
    }

    function testKeepState() {
        $this->box->given_Responds('a', '<a href="">A</a> $b $c');
        $this->box->given_Responds('b', '<a href="">B</a> $f');
        $this->box->given_Responds('c', '<a href="">C</a> $d $e');
        $this->box->given_Responds('d', '<a href="">D</a>');
        $this->box->given_Responds('e', '<a href="">E</a>');
        $this->box->given_Responds('f', '<a href="">F</a>');

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

    function testAssets() {
        $this->box->given_Responds('inner', 'not me');
        $this->box->given_Responds('outer', '$inner');
        $this->box->given_Responds('other', '
            <link href="some/favico.png" rel="icon" type="image/png"/>
            <link href="../my/styles.css" rel="stylesheet"/>
            <script src="some/script.js"/>
            <script src="/absolute/path.js"/>
            <img src="some/pic.jpg"/>');

        $this->box->given_Contains('outer', 'inner');

        $this->box->givenAPathFrom_To('outer', 'other');
        $this->box->givenTheRequestArgument_Is('_inner/!', 'other');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('
            <link href="other/some/favico.png" rel="icon" type="image/png"/>
            <link href="my/styles.css" rel="stylesheet"/>
            <script src="other/some/script.js"/>
            <script src="/absolute/path.js"/>
            <img src="other/some/pic.jpg"/>');
    }

    function testMergeHead() {
        $this->box->given_Responds('outer', '
            <html>
                <head>
                    <title>I stay</title>
                    <script src="outer/script.js"/>
                    <script src="duplicate.js"/>
                </head>
            </html>
        ');
        $this->box->given_Responds('inner', '
            <html>
                <head>
                    <title>I am ignored</title>
                    <script src="script.js"/>
                    <script src="../duplicate.js"/>
                </head>
            </html>');

        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('
            <html>
                <head>
                    <title>I stay</title>
                    <script src="outer/script.js"/>
                    <script src="duplicate.js"/>
                <script src="inner/script.js"/></head>
            </html>');
    }

    function testBoxesWithDefaultRequestArguments() {
        $this->box->given_Responds('outer', 'Hello $inner');
        $this->box->given_Responds('inner', '$name');
        $this->box->given_Contains_With('outer', 'inner', array('name' => 'World'));

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello World');
    }

    function testBoxList() {
        $this->box->given_Responds('outer', '$inner');
        $this->box->given_Responds('inner', '$list');
        $this->box->given_Responds('item', '<a href="?foo=bar">$name</a>');

        $this->box->given_Contains('outer', 'inner');
        $this->box->given_ContainsACollection('inner', 'list');
        $this->box->given_HasIn_A_With('inner', 'list', 'item', array('name' => 'One'));
        $this->box->given_HasIn_A_With('inner', 'list', 'item', array('name' => 'Two'));
        $this->box->given_HasIn_A_With('inner', 'list', 'item', array('name' => 'Three'));

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe(
                '<a href="?_inner[_list][_0][foo]=bar&_inner[_list][_]=0&_inner[_]=list&_=inner">One</a> ' .
                '<a href="?_inner[_list][_1][foo]=bar&_inner[_list][_]=1&_inner[_]=list&_=inner">Two</a> ' .
                '<a href="?_inner[_list][_2][foo]=bar&_inner[_list][_]=2&_inner[_]=list&_=inner">Three</a>');
    }

    function testListWithHeaders() {
        $this->box->given_Responds('outer', '
            <html>
                <head>
                    <title>Outer</title>
                </head>
            </html>');
        $this->box->given_Responds('inner', '<html><head><script src="$foo"/></head></html>');

        $this->box->given_ContainsACollection('outer', 'list');
        $this->box->given_HasIn_A_With('outer', 'list', 'inner', array('foo' => 'one'));
        $this->box->given_HasIn_A_With('outer', 'list', 'inner', array('foo' => 'bar'));
        $this->box->given_HasIn_A_With('outer', 'list', 'inner', array('foo' => 'bar'));

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('
            <html>
                <head>
                    <title>Outer</title>
                <script src="inner/one"/><script src="inner/bar"/></head>
            </html>');
    }

}