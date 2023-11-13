<?php

namespace Tonysm\ImportmapLaravel\Exceptions;

use SplFileInfo;

class FailedToFixImportStatementException extends ImportmapException
{
    public string $importStatement;

    public SplFileInfo $file;

    public static function couldNotFixImport(string $importStatement, SplFileInfo $file)
    {
        $exception = new static(sprintf(
            'Failed to fix import statement (%s) in file (%s)',
            $importStatement,
            trim(str_replace(base_path(), '', $file->getPath()), '/')
        ));

        $exception->importStatement = $importStatement;
        $exception->file = $file;

        return $exception;
    }
}
