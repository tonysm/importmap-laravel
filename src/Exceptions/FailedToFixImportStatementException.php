<?php

namespace Tonysm\ImportmapLaravel\Exceptions;

use SplFileInfo;

class FailedToFixImportStatementException extends ImportmapException
{
    public string $line;

    public SplFileInfo $file;

    public static function couldNotFixImport(string $line, SplFileInfo $file)
    {
        $exception = new static(sprintf(
            'Failed to fix import statement (%s) in file (%s)',
            $line,
            trim(str_replace(base_path(), '', $file->getPath()), '/')
        ));

        $exception->line = $line;
        $exception->file = $file;

        return $exception;
    }
}
