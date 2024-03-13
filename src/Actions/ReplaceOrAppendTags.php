<?php

namespace Tonysm\ImportmapLaravel\Actions;

class ReplaceOrAppendTags
{
    public const VITE_DIRECTIVE_PATTERN = '/(\s*)\@vite\(.*\)/';
    public const CLOSING_HEAD_TAG_PATTERN = '/(\s*)(<\/head>)/';

    public function __invoke(string $contents)
    {
        if (str_contains($contents, '<x-importmap::tasg />')) {
            return $contents;
        }

        if (str_contains($contents, '@vite')) {
            return preg_replace(
                static::VITE_DIRECTIVE_PATTERN,
                "\\1<x-importmap::tags />",
                $contents,
            );
        }

        return preg_replace(
            static::CLOSING_HEAD_TAG_PATTERN,
            PHP_EOL."\\1    <x-importmap::tags />\\1\\2",
            $contents,
        );
    }
}
