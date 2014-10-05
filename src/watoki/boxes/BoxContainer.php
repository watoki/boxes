<?php
namespace watoki\boxes;

use watoki\curir\Container;
use watoki\curir\delivery\WebRequest;
use watoki\deli\Request;
use watoki\factory\Factory;

class BoxContainer extends Container {

    /** @var BoxCollection */
    protected $boxes;

    /**
     * @param Factory $factory <-
     */
    function __construct(Factory $factory) {
        parent::__construct($factory);
        $this->boxes = new BoxCollection();
    }

    /**
     * @param Request|WebRequest|WrappedRequest $request
     * @return \watoki\curir\delivery\WebResponse
     */
    public function respond(Request $request) {
        if (!($request instanceof WrappedRequest)) {
            $request = WrappedRequest::fromRequest($request);
        }
        $this->boxes->dispatch($request, $this->router);

        $response = parent::respond($request);
        $response->setBody($this->boxes->mergeHeaders($response->getBody()));

        return $response;
    }

}