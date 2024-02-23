<?php

namespace Tonysm\ImportmapLaravel\Actions;

class ReplaceOrAppendTags
{
    const VITE_DIRECTIVE_PATTERN = '/(\s*)\@vite\(.+\)/';
    const CLOSING_HEAD_TAG_PATTERN = '/(\s*)(<\/head>)/';

    public function __invoke(string $contents)
    {
        if (preg_match(self::VITE_DIRECTIVE_PATTERN, $contents)) {
            return preg_replace(
                static::VITE_DIRECTIVE_PATTERN,
                "\\1<x-importmap::tags />",
                $contents,
            );
        }

        return preg_replace(
            static::CLOSING_HEAD_TAG_PATTERN,
            "\n\\1    <x-importmap::tags />\\1\\2",
            $contents,
        );
    }
}
