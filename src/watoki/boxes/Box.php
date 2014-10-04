<?php
namespace watoki\boxes;

use watoki\curir\Container;
use watoki\curir\delivery\WebRequest;
use watoki\deli\Request;
use watoki\factory\Factory;

class Box extends Container {

    /** @var Shelf */
    protected $shelf;

    /**
     * @param Factory $factory <-
     */
    function __construct(Factory $factory) {
        parent::__construct($factory);
        $this->shelf = new Shelf($this->router);
    }

    /**
     * @param Request|WebRequest|BoxedRequest $request
     * @return \watoki\curir\delivery\WebResponse
     */
    public function respond(Request $request) {
        if (!($request instanceof BoxedRequest)) {
            $request = BoxedRequest::fromRequest($request);
        }
        $this->shelf->unbox($request);
        $response = parent::respond($request);
        return $this->shelf->mergeHeaders($response);
    }

}