<?php

namespace CodingSocks\UploadHandler\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ChecksumMismatchHttpException extends HttpException
{
    /**
     * ChecksumMismatchHttpException constructor.
     *
     * @param null $message
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(460, $message, $previous, [], $code);
    }
}
