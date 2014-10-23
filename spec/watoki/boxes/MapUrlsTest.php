<?php
namespace spec\watoki\boxes;
use watoki\scrut\Specification;

/**
 * The URLs used by the HTTP mode of boxes are all but beautiful. And existing URLs would break by introducing boxes. So URLs can
 * be mapped to make them more human and search engine friendly.
 *
 * @property \spec\watoki\boxes\fixtures\BoxFixture fix <-
*/
class MapUrlsTest extends Specification {

    protected function background() {
        $this->fix->givenTheRequestFormatIs('html');
    }

    function testMapUrlToBoxTarget() {
        $this->fix->givenTheBoxContainer_Responding('o', 'a:$a');
        $this->fix->givenTheBoxContainer_Responding('b', 'B');

        $this->fix->given_Contains('o', 'a');
        $this->fix->given_MapsTargetsTo('o', 'a');

        $this->fix->givenTheRequestTarget_Is('b');

        $this->fix->whenIGetTheResponseFrom('o');
        $this->fix->thenTheResponseShouldBe('a:B');
    }

    function testEmptyTarget() {
        $this->fix->givenTheBoxContainer_Responding('o', 'a:$a');
        $this->fix->givenTheBoxContainer_Responding('a', 'A');

        $this->fix->given_Contains('o', 'a');
        $this->fix->given_MapsTargetsTo('o', 'a');

        $this->fix->whenIGetTheResponseFrom('o');
        $this->fix->thenTheResponseShouldBe('a:A');
    }

    function testMapArguments() {
        $this->fix->givenTheBoxContainer_Responding('o', 'a:$a');
        $this->fix->givenTheBoxContainer_Responding('a', '$foo $me');

        $this->fix->given_Contains('o', 'a');
        $this->fix->given_MapsTargetsTo('o', 'a');

        $this->fix->givenTheRequestArgument_Is('foo', 'bar');
        $this->fix->givenTheRequestArgument_Is('_a/me', 'baz');

        $this->fix->whenIGetTheResponseFrom('o');
        $this->fix->thenTheResponseShouldBe('a:bar baz');
    }

    function testKeepOtherBoxArguments() {
        $this->fix->givenTheBoxContainer_Responding('o', 'a:$a c:$c');
        $this->fix->givenTheBoxContainer_Responding('b', 'B');
        $this->fix->givenTheBoxContainer_Responding('c', '$foo');

        $this->fix->given_Contains('o', 'a');
        $this->fix->given_Contains('o', 'c');
        $this->fix->given_MapsTargetsTo('o', 'a');

        $this->fix->givenTheRequestTarget_Is('b');
        $this->fix->givenTheRequestArgument_Is('_c/foo', 'bar');

        $this->fix->whenIGetTheResponseFrom('o');
        $this->fix->thenTheResponseShouldBe('a:B c:bar');
    }

    function testDisableMappingWithHeader() {
        $this->fix->givenTheBoxContainer_Responding('o', 'a:$a');
        $this->fix->givenTheBoxContainer_Responding('a', 'A');

        $this->fix->given_Contains('o', 'a');
        $this->fix->given_MapsTargetsTo('o', 'a');

        $this->fix->givenTheRequestTarget_Is('a');
        $this->fix->givenTheRequestHasTheHeader('X-NoBoxing');

        $this->fix->whenIGetTheResponseFrom('o');
        $this->fix->thenTheResponseShouldBe('A');
    }

    function testOnlyMapHtmlRequests() {
        $this->fix->givenTheBoxContainer_Responding('o', 'a:$a');
        $this->fix->givenTheBoxContainer_Responding('a', 'A');

        $this->fix->given_Contains('o', 'a');
        $this->fix->given_MapsTargetsTo('o', 'a');

        $this->fix->givenTheRequestTarget_Is('a');
        $this->fix->givenTheRequestFormatIs('notHtml');

        $this->fix->whenIGetTheResponseFrom('o');
        $this->fix->thenTheResponseShouldBe('A');
    }

    function testMapLinks() {
        $this->fix->givenTheBoxContainer_Responding('o', 'a:$a b:$b');
        $this->fix->givenTheBoxContainer_Responding('a', '<a href="foo/bar?me=baz">A</a>');
        $this->fix->givenTheBoxContainer_Responding('b', '<a href="foo" target="a">B</a>');

        $this->fix->given_Contains('o', 'a');
        $this->fix->given_Contains('o', 'b');
        $this->fix->given_MapsTargetsTo('o', 'a');

        $this->fix->givenTheRequestArgument_Is('_b/one', 'two');

        $this->fix->whenIGetTheResponseFrom('o');
        $this->fix->thenTheResponseShouldBe('a:<a href="foo/bar?me=baz&_b[one]=two">A</a> b:<a href="foo?_b[one]=two">B</a>');
    }

    function testWrapChildren() {
        $this->fix->givenTheBoxContainer_Responding('o', '$a');
        $this->fix->givenTheBoxContainer_Responding('a', '$b');
        $this->fix->givenTheBoxContainer_Responding('b', '<a href="?foo=bar">B</a>');

        $this->fix->given_Contains('o', 'a');
        $this->fix->given_Contains('a', 'b');
        $this->fix->given_MapsTargetsTo('o', 'a');

        $this->fix->givenTheRequestArgument_Is('_a/!', 'a');

        $this->fix->whenIGetTheResponseFrom('o');
        $this->fix->thenTheResponseShouldBe('<a href="a?_a[_b][foo]=bar&_a[_]=b">B</a>');
    }

    function testLinksToSelf() {
        $this->fix->givenTheBoxContainer_Responding('o', 'a:$a');
        $this->fix->givenTheBoxContainer_Responding('baz', '<a href="?foo=bar">A</a>');

        $this->fix->given_Contains('o', 'a');
        $this->fix->given_MapsTargetsTo('o', 'a');

        $this->fix->givenTheRequestTarget_Is('baz');

        $this->fix->whenIGetTheResponseFrom('o');
        $this->fix->thenTheResponseShouldBe('a:<a href="baz?foo=bar">A</a>');
    }

    function testAddBaseElement() {
        $this->fix->givenTheBoxContainer_Responding('o', '<html><head></head></html>');
        $this->fix->givenTheBoxContainer('a');

        $this->fix->given_Contains('o', 'a');
        $this->fix->given_MapsTargetsTo('o', 'a');

        $this->fix->givenTheContextIs('my.site/here');

        $this->fix->whenIGetTheResponseFrom('o');
        $this->fix->thenTheResponseShouldBe('<html><head><base href="my.site/here"/></head></html>');
    }

}