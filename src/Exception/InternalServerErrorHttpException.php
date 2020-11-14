<?php

namespace CodingSocks\UploadHandler\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InternalServerErrorHttpException extends HttpException
{
    public function __construct(string $message, \Exception $previous = null, int $code = 0)
    {
        parent::__construct(500, $message, $previous, [], $code);
    }
}
