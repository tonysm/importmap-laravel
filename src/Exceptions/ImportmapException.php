<?php

namespace Tonysm\ImportmapLaravel\Exceptions;

use Exception;

class ImportmapException extends Exception
{
    public static function withResponseError(string $error): self
    {
        return new self($error);
    }

    public static function withUnexpectedResponseCode($code): self
    {
        return new self(sprintf('Unexpected response code (%s)', $code));
    }
}
