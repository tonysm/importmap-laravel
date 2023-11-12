<?php

namespace Tonysm\ImportmapLaravel\Actions;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;
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

            $lines[$index] = preg_replace_callback(
                '#import (?:.*["\'])(\..*)(?:[\'"];?.*)#',
                function ($matches) use ($file) {
                    $replaced = $this->replaceDotImports($file->getPath(), $matches[1]);

                    if (! $replaced) {
                        throw FailedToFixImportStatementException::couldNotFixImport($matches[0], $file);
                    }

                    $relative = trim(str_replace($this->root, '', $replaced), '/');

                    return str_replace($matches[1], $relative, $matches[0]);
                },
                $line,
            );
        }

        return implode(PHP_EOL, $lines);
    }

    private function replaceDotImports(string $path, string $imports)
    {
        $removeExtension = false;
        $removeIndex = false;
        $path = rtrim($path, '/').'/'.$imports;

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

        if (! ($fixedPath = realpath($path))) {
            return false;
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
