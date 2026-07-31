<?php

namespace Tonysm\ImportmapLaravel\Exceptions;

use SplFileInfo;

class FailedToFixImportStatementException extends ImportmapException
{
    public string $importStatement;

    public SplFileInfo $sourceFile;

    public static function couldNotFixImport(string $importStatement, SplFileInfo $file): static
    {
        $exception = new static(sprintf(
            'Failed to fix import statement (%s) in file (%s)',
            $importStatement,
            trim(str_replace(base_path(), '', $file->getPath()), '/')
        ));

        $exception->importStatement = $importStatement;
        $exception->sourceFile = $file;

        return $exception;
    }
}
