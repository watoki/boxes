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
        $this->box->given_Responds('a', '<a href="here?argA=X">A</a>');
        $this->box->given_Responds('b', '<a href="there?argB=Y">B</a>');
        $this->box->given_Responds('o', '$a $b');

        $this->box->given_Contains('o', 'a');
        $this->box->given_Contains('o', 'b');

        $this->box->givenTheTargetArgumentOf_Is('a', 'a');
        $this->box->givenTheTargetArgumentOf_Is('b', 'b');
        $this->box->givenTheRequestArgument_Is('a/argA', 'A');
        $this->box->givenTheRequestArgument_Is('a/arg2', '2');
        $this->box->givenTheRequestArgument_Is('a/aa/arg', 'AA');
        $this->box->givenTheRequestArgument_Is('b/argB', 'B');

        $this->box->whenIGetTheResponseFrom('o');
        $this->box->thenTheResponseShouldBe(
                '<a href="?a[!]=here&a[argA]=X&b[!]=b&b[argB]=B&_=a">A</a> ' .
                '<a href="?a[!]=a&a[argA]=A&a[arg2]=2&a[aa][arg]=AA&b[!]=there&b[argB]=Y&_=b">B</a>');
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
        $this->box->givenTheTargetArgumentOf_Is('inner', 'other');

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
        $this->box->given_Responds('outer', '$list');
        $this->box->given_Responds('inner', '<a href="?foo=bar">$name</a>');

        $this->box->given_ContainsACollection('outer', 'list');
        $this->box->given_HasIn_A_With('outer', 'list', 'inner', array('name' => 'One'));
        $this->box->given_HasIn_A_With('outer', 'list', 'inner', array('name' => 'Two'));
        $this->box->given_HasIn_A_With('outer', 'list', 'inner', array('name' => 'Three'));

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe(
                '<a href="?list[0][foo]=bar&list[_]=0&_=list">One</a> ' .
                '<a href="?list[1][foo]=bar&list[_]=1&_=list">Two</a> ' .
                '<a href="?list[2][foo]=bar&list[_]=2&_=list">Three</a>');
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