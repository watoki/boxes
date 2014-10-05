<?php
namespace watoki\boxes;

use watoki\collections\Map;
use watoki\curir\delivery\WebRequest;
use watoki\curir\protocol\Url;
use watoki\deli\Path;

class WrappedRequest extends WebRequest {

    /** @var null|WebRequest */
    private $originalRequest;

    public function __construct(Path $target, $method = WebRequest::METHOD_GET, Map $arguments = null) {
        parent::__construct(
            Url::fromString(''),
            $target,
            $method,
            $arguments ? : new Map()
        );
    }

    public static function fromString($target, $args = array()) {
        return new WrappedRequest(Path::fromString($target), WebRequest::METHOD_GET, new Map($args));
    }

    public static function fromRequest(WebRequest $request) {
        $boxed = new WrappedRequest(
            $request->getTarget()->copy(),
            WebRequest::METHOD_GET,
            $request->getArguments()->copy()
        );
        $boxed->setOriginalRequest($request);
        return $boxed;
    }

    /**
     * @return \watoki\curir\delivery\WebRequest
     */
    public function getOriginalRequest() {
        return $this->originalRequest;
    }

    /**
     * @return WrappedRequest
     */
    public function copy() {
        $copy = new WrappedRequest(
            $this->getTarget()->copy(),
            $this->getMethod(),
            $this->getArguments()->copy()
        );
        if ($this->originalRequest) {
            $copy->setOriginalRequest($this->originalRequest);
        }
        return $copy;
    }

    public function setOriginalRequest(WebRequest $originalRequest) {
        $this->originalRequest = $originalRequest;
        $this->setContext($originalRequest->getContext());
        $this->getFormats()->insertAll($originalRequest->getFormats(), 0);
        $this->getHeaders()->merge($originalRequest->getHeaders());
    }

} 