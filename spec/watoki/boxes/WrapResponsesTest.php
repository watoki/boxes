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
        $this->box->thenTheResponseShouldBe('Go <a href="?inner[!]=here&_=inner">Here</a>');
    }

    function testLinkArguments() {
        $this->box->given_Responds('inner', '<a href="?foo=bar">Here</a>');
        $this->box->given_Responds('outer', 'Go $inner');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Go <a href="?inner[foo]=bar&_=inner">Here</a>');
    }

    function testLinkTargetsOtherBox() {
        $this->box->given_Responds('inner', '<a href="there" target="other">Two</a>');
        $this->box->given_Responds('outer', 'Go $inner');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Go <a href="?other[!]=there&_=inner">Two</a>');
    }

    function testRecursiveWrapping() {
        $this->box->given_Responds('one', 'One $two');
        $this->box->given_Responds('two', '<a href="here?foo=bar">Two</a> $three');
        $this->box->given_Responds('three', '<a href="there?me=you">Three</a>');

        $this->box->given_Contains('one', 'two');
        $this->box->given_Contains('two', 'three');

        $this->box->whenIGetTheResponseFrom('one');
        $this->box->thenTheResponseShouldBe(
                'One <a href="?two[!]=here&two[foo]=bar&_=two">Two</a> ' .
                '<a href="?two[three][!]=there&two[three][me]=you&two[_]=three&_=two">Three</a>');
    }

    function testFormWithoutActionAndMethod() {
        $this->box->given_Responds('outer', '$inner');
        $this->box->given_Responds('inner', '<form></form>');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('<form action="?inner[do]=post&_=inner"></form>');
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
            <form action="?inner[!]=there&inner[me]=you&inner[do]=get&_=inner" method="get">
                <input name="inner[foo]" value="bar"/>
                <textarea name="inner[foo][one]"></textarea>
                <select name="inner[foo][two]"></select>
                <button name="inner[do]" value="that">Go</button>
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
                <div><form action="?inner[!]=here&inner[do]=post&_=inner">
                    <a href="?inner[!]=there&inner[one]=two&_=inner">Click</a>
                </form></div>');
    }

    function testKeepState() {
        $this->box->given_Responds('a', '<a href=""></a> $b $c');
        $this->box->given_Responds('b', '<a href=""></a> $f');
        $this->box->given_Responds('c', '<a href=""></a> $d $e');
        $this->box->given_Responds('d', '<a href=""></a>');
        $this->box->given_Responds('e', '<a href=""></a>');
        $this->box->given_Responds('f', '<a href=""></a>');

        $this->box->given_Contains('a', 'b');
        $this->box->given_Contains('a', 'c');
        $this->box->given_Contains('b', 'f');
        $this->box->given_Contains('c', 'd');
        $this->box->given_Contains('c', 'e');

        $this->box->givenTheRequestArgument_Is('foo', 'A');
        $this->box->givenTheRequestArgument_Is('b/foo', 'B');
        $this->box->givenTheRequestArgument_Is('b/f/foo', 'F');
        $this->box->givenTheRequestArgument_Is('c/foo', 'C');
        $this->box->givenTheRequestArgument_Is('c/d/foo', 'D');
        $this->box->givenTheRequestArgument_Is('c/e/foo', 'E');

        $this->box->whenIGetTheResponseFrom('a');
        $this->box->thenTheResponseShouldBe(
                '<a href=""></a> ' .
                '<a href="?foo=A&c[foo]=C&c[d][foo]=D&c[e][foo]=E&_=b"></a> ' .
                '<a href="?foo=A&c[foo]=C&c[d][foo]=D&c[e][foo]=E&b[foo]=B&b[_]=f&_=b"></a> ' .
                '<a href="?foo=A&b[foo]=B&b[f][foo]=F&_=c"></a> ' .
                '<a href="?foo=A&b[foo]=B&b[f][foo]=F&c[foo]=C&c[e][foo]=E&c[_]=d&_=c"></a> ' .
                '<a href="?foo=A&b[foo]=B&b[f][foo]=F&c[foo]=C&c[d][foo]=D&c[_]=e&_=c"></a>');
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
        $this->box->givenTheRequestArgument_Is('inner/!', 'other');

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
                '<a href="?inner[list][0][foo]=bar&inner[list][_]=0&inner[_]=list&_=inner">One</a> ' .
                '<a href="?inner[list][1][foo]=bar&inner[list][_]=1&inner[_]=list&_=inner">Two</a> ' .
                '<a href="?inner[list][2][foo]=bar&inner[list][_]=2&inner[_]=list&_=inner">Three</a>');
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