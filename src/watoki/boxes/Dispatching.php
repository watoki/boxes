<?php
namespace watoki\boxes;

use watoki\deli\Router;

interface Dispatching {

    /**
     * @param WrappedRequest $request
     * @param Router $router
     * @return WrappedRequest
     */
    public function dispatch(WrappedRequest $request, Router $router);

    /**
     * @return string|array
     */
    public function getModel();

} 