<?php
namespace watoki\boxes;

use watoki\curir\Container;
use watoki\deli\Request;
use watoki\factory\Factory;

class Box extends Container {

    /** @var Shelf */
    protected $shelf;

    public function respond(Request $request) {
        $this->shelf->unwrap($request);
        return parent::respond($request);
    }

    function __construct(Factory $factory) {
        parent::__construct($factory);
        $this->shelf = new Shelf($this->router);
    }

} 