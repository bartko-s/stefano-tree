<?php

declare(strict_types=1);

namespace StefanoTree\Exception;

class TreeIsBrokenException extends \Exception implements ExceptionInterface
{
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        if (!$message) {
            $message = 'Tree structure is broken. Rebuild your tree.';
        }
        parent::__construct($message, $code, $previous);
    }
}
