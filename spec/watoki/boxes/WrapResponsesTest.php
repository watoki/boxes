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
        $this->box->givenTheBoxContainer_Responding('inner', 'World');
        $this->box->givenTheBoxContainer_Responding('outer', 'Hello $inner');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello World');
    }

    function testLinkTarget() {
        $this->box->givenTheBoxContainer_Responding('inner', '<a href="here">Here</a>');
        $this->box->givenTheBoxContainer_Responding('outer', 'Go $inner');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Go <a href="?_inner[!]=here&_=inner">Here</a>');
    }

    function testLinkArguments() {
        $this->box->givenTheBoxContainer_Responding('inner', '<a href="?foo=bar">Here</a>');
        $this->box->givenTheBoxContainer_Responding('outer', 'Go $inner');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Go <a href="?_inner[!]=inner&_inner[foo]=bar&_=inner">Here</a>');
    }

    function testLinkTargetsOtherBox() {
        $this->box->givenTheBoxContainer_Responding('inner', '<a href="there" target="other">Two</a>');
        $this->box->givenTheBoxContainer_Responding('outer', 'Go $inner');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Go <a href="?_other[!]=there&_=other">Two</a>');
    }

    function testLinkTargetsParentBox() {
        $this->box->givenTheBoxContainer_Responding('outer', 'Go $inner');
        $this->box->givenTheBoxContainer_Responding('inner', 'Even $further');
        $this->box->givenTheBoxContainer_Responding('further', '<a href="there?foo=bar" target="_parent">Further</a>');

        $this->box->given_Contains('outer', 'inner');
        $this->box->given_Contains('inner', 'further');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Go Even <a href="?_inner[!]=there&_inner[foo]=bar&_=inner">Further</a>');
    }

    function testLinkTargetsParentsParentBox() {
        $this->box->givenTheBoxContainer_Responding('outer', 'Go $inner');
        $this->box->givenTheBoxContainer_Responding('inner', 'Even $further');
        $this->box->givenTheBoxContainer_Responding('further', '<a href="there?foo=bar" target="_parent_parent">Further</a>');

        $this->box->given_Contains('outer', 'inner');
        $this->box->given_Contains('inner', 'further');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Go Even <a href="there?foo=bar">Further</a>');
    }

    function testDoNotWrapLinksWithOtherTargets() {
        $this->box->givenTheBoxContainer_Responding('inner', '<a href="there" target="_other">Two</a>');
        $this->box->givenTheBoxContainer_Responding('outer', 'Go $inner');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Go <a href="there" target="_other">Two</a>');
    }

    function testRecursiveWrapping() {
        $this->box->givenTheBoxContainer_Responding('one', 'One $two');
        $this->box->givenTheBoxContainer_Responding('two', '<a href="here?foo=bar">Two</a> $three');
        $this->box->givenTheBoxContainer_Responding('three', '<a href="there?me=you">Three</a>');

        $this->box->given_Contains('one', 'two');
        $this->box->given_Contains('two', 'three');

        $this->box->whenIGetTheResponseFrom('one');
        $this->box->thenTheResponseShouldBe(
                'One <a href="?_two[!]=here&_two[foo]=bar&_=two">Two</a> ' .
                '<a href="?_two[!]=two&_two[_three][!]=there&_two[_three][me]=you&_two[_]=three&_=two">Three</a>');
    }

    function testFormWithoutActionAndMethod() {
        $this->box->givenTheBoxContainer_Responding('outer', '$inner');
        $this->box->givenTheBoxContainer_Responding('inner', '<form></form>');
        $this->box->given_Contains('outer', 'inner');

        $this->box->givenTheRequestArgument_Is('_some/key', 'foo');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('<form action="?_some[key]=foo&_inner[!]=inner&_inner[do]=post&_=inner"></form>');
    }

    function testFormWithAction() {
        $this->box->givenTheBoxContainer_Responding('outer', '$inner');
        $this->box->givenTheBoxContainer_Responding('inner', '
            <form action="there?me=you#here" method="get">
                <input name="foo" value="bar"/>
                <textarea name="foo[one]"></textarea>
                <select id="here" name="foo[two]"></select>
                <button name="do" value="that">Go</button>
            </form>');
        $this->box->given_Contains('outer', 'inner');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('
            <form action="?_inner[!]=there&_inner[me]=you&_inner[do]=get&_=inner#here" method="get">
                <input name="_inner[foo]" value="bar"/>
                <textarea name="_inner[foo][one]"></textarea>
                <select id="here" name="_inner[foo][two]"></select>
                <button name="_inner[do]" value="that">Go</button>
            </form>');
    }

    function testWrapBody() {
        $this->box->givenTheBoxContainer_Responding('outer', '
            <html>
                <head><title>Hello World</title></head>
                <body><p>$inner</p></body>
            </html>');
        $this->box->givenTheBoxContainer_Responding('inner', '
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
        $this->box->givenTheBoxContainer_Responding('outer', '$inner');
        $this->box->givenTheBoxContainer_Responding('inner', '<html><body>Hello World</body></html>');

        $this->box->given_Contains('outer', 'inner');
        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello World');
    }

    function testComplexDom() {
        $this->box->givenTheBoxContainer_Responding('outer', '$inner');
        $this->box->givenTheBoxContainer_Responding('inner', '
            <html><body><p>Hello</p>
                <div><form action="here?foo=bar">
                    <a href="there?one=two">Click</a>
                </form></div>
            </body></html>');

        $this->box->given_Contains('outer', 'inner');
        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('<p>Hello</p>
                <div><form action="?_inner[!]=here&_inner[foo]=bar&_inner[do]=post&_=inner">
                    <a href="?_inner[!]=there&_inner[one]=two&_=inner">Click</a>
                </form></div>');
    }

    function testAssets() {
        $this->box->givenTheBoxContainer_Responding('inner', 'not me');
        $this->box->givenTheBoxContainer_Responding('outer', '$inner');
        $this->box->givenTheBoxContainer_Responding('other', '
            <link href="some/favico.png" rel="icon" type="image/png"/>
            <link href="../my/styles.css" rel="stylesheet"/>
            <script src="some/script.js"/>
            <script src="/absolute/path.js"/>
            <img src="some/pic.jpg"/>');

        $this->box->given_Contains('outer', 'inner');

        $this->box->givenAPath_From_To('that/path', 'outer', 'other');
        $this->box->givenTheRequestArgument_Is('_inner/!', 'that/path');

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('
            <link href="that/some/favico.png" rel="icon" type="image/png"/>
            <link href="my/styles.css" rel="stylesheet"/>
            <script src="that/some/script.js"/>
            <script src="/absolute/path.js"/>
            <img src="that/some/pic.jpg"/>');
    }

    function testMergeHead() {
        $this->box->givenTheBoxContainer_Responding('outer', '
            <html>
                <head>
                    <title>I stay</title>
                    <script src="outer/script.js"/>
                    <script src="duplicate.js"/>
                </head>
            </html>
        ');
        $this->box->givenTheBoxContainer_Responding('inner', '
            <html>
                <head>
                    <title>I am ignored</title>
                    <script src="script.js"/>
                    <script src="duplicate.js"/>
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
                <script src="script.js"/></head>
            </html>');
    }

    function testBoxesWithDefaultRequestArguments() {
        $this->box->givenTheBoxContainer_Responding('outer', 'Hello $inner');
        $this->box->givenTheBoxContainer_Responding('inner', '$name');
        $this->box->given_Contains_With('outer', 'inner', array('name' => 'World'));

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('Hello World');
    }

    function testBoxList() {
        $this->box->givenTheBoxContainer_Responding('outer', '$inner');
        $this->box->givenTheBoxContainer_Responding('inner', '$list');
        $this->box->givenTheBoxContainer_Responding('item', '<a href="?foo=bar">$name</a>');

        $this->box->given_Contains('outer', 'inner');
        $this->box->given_ContainsACollection('inner', 'list');
        $this->box->given_HasIn_A_With('inner', 'list', 'item', array('name' => 'One'));
        $this->box->given_HasIn_A_With('inner', 'list', 'item', array('name' => 'Two'));
        $this->box->given_HasIn_A_With('inner', 'list', 'item', array('name' => 'Three'));

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe(
                '<a href="?_inner[!]=inner&_inner[_list][_0][!]=item&_inner[_list][_0][foo]=bar&_inner[_list][_]=0&_inner[_]=list&_=inner">One</a> ' .
                '<a href="?_inner[!]=inner&_inner[_list][_1][!]=item&_inner[_list][_1][foo]=bar&_inner[_list][_]=1&_inner[_]=list&_=inner">Two</a> ' .
                '<a href="?_inner[!]=inner&_inner[_list][_2][!]=item&_inner[_list][_2][foo]=bar&_inner[_list][_]=2&_inner[_]=list&_=inner">Three</a>');
    }

    function testListWithHeaders() {
        $this->box->givenTheBoxContainer_Responding('outer', '
            <html>
                <head>
                    <title>Outer</title>
                </head>
            </html>');
        $this->box->givenTheBoxContainer_Responding('inner', '<html><head><script src="$foo"/></head></html>');

        $this->box->given_ContainsACollection('outer', 'list');
        $this->box->given_HasIn_A_With('outer', 'list', 'inner', array('foo' => 'one'));
        $this->box->given_HasIn_A_With('outer', 'list', 'inner', array('foo' => 'bar'));
        $this->box->given_HasIn_A_With('outer', 'list', 'inner', array('foo' => 'bar'));

        $this->box->whenIGetTheResponseFrom('outer');
        $this->box->thenTheResponseShouldBe('
            <html>
                <head>
                    <title>Outer</title>
                <script src="one"/><script src="bar"/></head>
            </html>');
    }

}