<?php
namespace watoki\boxes;

use watoki\curir\delivery\WebRequest;
use watoki\deli\Router;

interface Dispatching {

    /**
     * @param WebRequest $request
     * @param Router $router
     * @return WebRequest
     */
    public function dispatch(WebRequest $request, Router $router);

    /**
     * @return string|array
     */
    public function getModel();

} 