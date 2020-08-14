<?php

namespace CodingSocks\UploadHandler\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class RequestEntityTooLargeHttpException extends HttpException
{
    /**
     * RequestEntityTooLargeHttpException constructor.
     *
     * @param string|null $message
     * @param \Exception|null $previous
     * @param integer $code
     */
    public function __construct($message = null, \Exception $previous = null, int $code = 0)
    {
        parent::__construct(413, $message, $previous, [], $code);
    }
}
