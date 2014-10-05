<?php
namespace watoki\boxes;

use watoki\collections\Map;
use watoki\curir\delivery\WebRequest;
use watoki\deli\Path;

class WrappedRequest extends WebRequest {

    /** @var null|WebRequest */
    private $original;

    public function __construct(WebRequest $original, Path $target, $method = WebRequest::METHOD_GET, Map $arguments = null) {
        parent::__construct(
                $original->getContext(),
                $target,
                $method,
                $arguments ? : new Map(),
                $original->getFormats(),
                $original->getHeaders()
        );
        $this->original = $original;
    }

    public static function fromRequest(WebRequest $request) {
        return new WrappedRequest(
                $request,
                $request->getTarget()->copy(),
                WebRequest::METHOD_GET,
                $request->getArguments()->copy()
        );
    }

    /**
     * @return WebRequest
     */
    public function getOriginal() {
        return $this->original;
    }

    /**
     * @return WrappedRequest
     */
    public function copy() {
        return new WrappedRequest(
                $this->original,
                $this->getTarget()->copy(),
                $this->getMethod(),
                $this->getArguments()->copy()
        );
    }

} 