<?php

namespace StefanoTree\Exception;

use Exception;

class TreeIsBrokenException extends BaseException
{
    public function __construct($message = '', $code = 0, Exception $previous = null)
    {
        if (!$message) {
            $message = 'Tree structure is broken. Rebuild your tree.';
        }
        parent::__construct($message, $code, $previous);
    }
}
