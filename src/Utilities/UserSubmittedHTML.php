<?php

namespace App\Utilities;

class UserSubmittedHTML
{
    public static function clean(?string $content, array $allowedTags): string
    {
        if (is_null($content)) {
            return '';
        }

        $content = trim($content);
        if ('' === $content || '<p></p>' === $content) {
            return '';
        }

        $allowedTagsFormatted = '';
        foreach ($allowedTags as $allowedTag) {
            $allowedTagsFormatted .= '<' . $allowedTag . '>';
        }

        return strip_tags($content, $allowedTagsFormatted);
    }

    public static function isClean(?string $content, array $allowedTags): bool
    {
        return $content === self::clean($content, $allowedTags);
    }
}
