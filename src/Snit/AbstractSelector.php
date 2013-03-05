<?php

namespace Snit;

abstract class AbstractSelector
{
    /**
     * Object containing information to be extracted.
     *
     * @var mixed
     */
    protected $data;

    /**
     * Receive the data to have information extracted.
     *
     * @param mixed $mixed StdClass or string (JSON to be decoded)
     */
    public function __construct($mixed=null)
    {
        if (is_string($mixed)) {
            $mixed = json_decode($mixed);
        }

        $this->data = $mixed;
    }
}
