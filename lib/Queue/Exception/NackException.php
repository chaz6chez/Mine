<?php
namespace Mine\Queue\Exception;

use Mine\Helper\Exception;

class NackException extends Exception {
    /**
     * Exception constructor.
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct($message = 'NACK', $code = 0, \Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}