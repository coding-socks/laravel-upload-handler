<?php

namespace LaraCrafts\ChunkUploader\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class RequestEntityTooLargeHttpException extends HttpException
{
    /**
     * RequestEntityTooLargeHttpException constructor.
     *
     * @param null $message
     * @param int $statusCode
     * @param \Exception|null $previous
     * @param int|null $code
     */
    public function __construct($message = null, \Exception $previous = null, ?int $code = 0)
    {
        parent::__construct(413, $message, $previous, [], $code);
    }
}
