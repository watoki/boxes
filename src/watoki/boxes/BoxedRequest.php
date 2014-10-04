<?php
namespace watoki\boxes;

use watoki\curir\delivery\WebRequest;

class BoxedRequest extends WebRequest {

    /** @var WebRequest */
    private $originalRequest;

    public function __construct(WebRequest $originalRequest) {
        parent::__construct(
            $originalRequest->getContext(),
            $originalRequest->getTarget()->copy(),
            WebRequest::METHOD_GET,
            $originalRequest->getArguments()->copy(),
            $originalRequest->getFormats()->copy(),
            $originalRequest->getHeaders()->copy()
        );
        $this->originalRequest = $originalRequest;
    }

    /**
     * @return \watoki\curir\delivery\WebRequest
     */
    public function getOriginalRequest() {
        return $this->originalRequest;
    }

    public function copy() {
        $copy = new BoxedRequest($this);
        $copy->setMethod($this->getMethod());
        $copy->originalRequest = $this->originalRequest;
        return $copy;
    }

} 