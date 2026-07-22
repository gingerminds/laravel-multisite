<?php

declare(strict_types=1);

namespace Gingerminds\LaravelMultisite\Exceptions\Translation;

use RuntimeException;

/**
 * Raised when the front translations source file cannot be downloaded from
 * Google Drive or cannot be parsed once downloaded (missing "key" column,
 * unreadable temp file, ...).
 *
 * Callers of TranslationService never see this directly: it is caught and
 * logged there, and an empty translation set is returned instead of
 * breaking the API response (see TranslationService::fetchFromDrive()).
 */
class TranslationSourceException extends RuntimeException
{
}
