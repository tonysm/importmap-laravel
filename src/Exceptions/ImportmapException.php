<?php

namespace Tonysm\ImportmapLaravel\Exceptions;

use Exception;

class ImportmapException extends Exception
{
    public static function withResponseError(string $error)
    {
        return new self($error);
    }

    public static function withUnexpectedResponseCode($code)
    {
        return new self(sprintf('Unexpected response code (%s)', $code));
    }
}
