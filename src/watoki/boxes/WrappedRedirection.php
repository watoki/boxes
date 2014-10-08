<?php
namespace watoki\boxes;

class WrappedRedirection extends \Exception {

    /** @var string */
    private $target;

    public function __construct($target) {
        $this->target = $target;
    }

    /**
     * @return string
     */
    public function getTarget() {
        return $this->target;
    }

} 