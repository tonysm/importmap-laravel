<?php

namespace Tonysm\ImportmapLaravel\Exceptions;

use Exception;

class ImportmapException extends Exception
{
    public static function withResponseError(string $error)
    {
        return new static($error);
    }

    public static function withUnexpectedResponseCode($code)
    {
        return new static(sprintf('Unexpected response code (%s)', $code));
    }
}
