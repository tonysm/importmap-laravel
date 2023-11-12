<?php

namespace Tonysm\ImportmapLaravel\Actions;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;
use Tonysm\ImportmapLaravel\Events\FailedToFixImportStatement;
use Tonysm\ImportmapLaravel\Exceptions\FailedToFixImportStatementException;

class FixJsImportPaths
{
    public function __construct(public string $root, public ?string $output = null)
    {
        $this->output ??= $root;
    }

    public function __invoke()
    {
        collect(File::allFiles($this->root))
            ->filter(fn (SplFileInfo $file) => in_array($file->getExtension(), ['js', 'mjs']))
            ->each(fn (SplFileInfo $file) => File::ensureDirectoryExists($this->absoluteOutputPathFor($file)))
            ->each(fn (SplFileInfo $file) => File::put(
                $this->absoluteOutputPathWithFileFor($file),
                $this->updatedJsImports($file),
            ));
    }

    private function absoluteOutputPathFor(SplFileInfo $file)
    {
        return str_replace($this->root, $this->output, dirname($file->getRealPath()));
    }

    private function absoluteOutputPathWithFileFor(SplFileInfo $file)
    {
        return rtrim($this->absoluteOutputPathFor($file), '/').'/'.$file->getFilename();
    }

    private function updatedJsImports(SplFileInfo $file)
    {
        $lines = File::lines($file->getRealPath())->all();

        foreach ($lines as $index => $line) {
            if (! str_starts_with($line, 'import ')) {
                continue;
            }

            try {
                $lines[$index] = preg_replace_callback(
                    '#import (?:.*["\'])(\..*)(?:[\'"];?.*)#',
                    function ($matches) use ($file) {
                        $replaced = $this->replaceDotImports($file, $matches[1], $matches[0]);

                        $relative = trim(str_replace($this->root, '', $replaced), '/');

                        return str_replace($matches[1], $relative, $matches[0]);
                    },
                    $line,
                );
            } catch (FailedToFixImportStatementException $exception) {
                event(new FailedToFixImportStatement($exception->file, $exception->importStatement));
            }
        }

        return implode(PHP_EOL, $lines);
    }

    private function replaceDotImports(SplFileInfo $file, string $imports, string $line)
    {
        $removeExtension = false;
        $removeIndex = false;
        $path = rtrim($file->getPath(), '/').'/'.$imports;

        if (is_dir($path)) {
            $removeIndex = true;
            $path = File::exists(rtrim($path, '/').'/index.mjs')
                ? rtrim($path, '/').'/index.mjs'
                : rtrim($path, '/').'/index.js';
        }

        if (! str_ends_with($path, '.js') && ! str_ends_with($path, '.mjs')) {
            $removeExtension = true;
            $path = File::exists($path.'.mjs')
                ? $path.'.mjs'
                : $path.'.js';
        }

        if (($fixedPath = realpath($path)) === false) {
            throw FailedToFixImportStatementException::couldNotFixImport($line, $file);
        }

        if ($removeIndex) {
            return Str::beforeLast($fixedPath, '/');
        }

        if ($removeExtension) {
            return Str::beforeLast($fixedPath, '.');
        }

        return $fixedPath;
    }
}
