<?php
namespace spec\watoki\boxes;
use watoki\scrut\Specification;

/**
 * The URLs used by the HTTP mode of boxes are all but beautiful. And existing URLs would break by introducing boxes. So URLs can
 * be mapped to make them more human and search engine friendly.
 */
class MapUrlsTest extends Specification {

    function testMapUrlToBoxTarget() {
        $this->markTestIncomplete('site/foo/bar  --->  site?_a[!]=foo/bar&_=a');
    }

    function testKeepOtherBoxArguments() {
        $this->markTestIncomplete('site/foo?_b[bar]=baz  --->  site?_a[!]=foo&_=a&_b[bar]=baz');
    }

    function testDisableMappingWithHeader() {
        $this->markTestIncomplete('X-Boxing: off - or something like that');
    }

    function testMapWhenWrapping() {
        $this->markTestIncomplete('site?_a[!]=foo&_=a&_b[bar]=baz  --->  site/foo?_b[bar]=baz');
    }

    function testAdaptLinkUrls() {
        $this->markTestIncomplete('href="?_a[!]=foo/baz" in site?_a[!]=foo/bar  --->  href="../foo/baz" in site/foo/bar');
    }

    function testAdaptAssetUrls() {
        $this->markTestIncomplete('src="foo/bar.png" in site?_a[!]=foo/bar&_=a  --->   src="../foo/bar.png" in site/foo/bar');
    }

}