<?php
declare(strict_types=1);

function read_json_file(string $path, ?string &$failureReason = null): array
{
    $failureReason = null;
    if (!is_file($path)) {
        $failureReason = 'The JSON file is missing.';
        return [];
    }
    if (!is_readable($path)) {
        $failureReason = 'The JSON file is not readable.';
        return [];
    }

    $handle = false;
    $lastError = null;
    for ($attempt = 1; $attempt <= 8; $attempt++) {
        error_clear_last();
        $handle = @fopen($path, 'rb');
        if ($handle !== false) {
            break;
        }
        $lastError = error_get_last();
        if ($attempt < 8) {
            usleep(75000);
        }
    }
    if ($handle === false) {
        clearstatcache(true, $path);
        $failureReason = is_readable($path)
            ? 'The JSON file is temporarily unavailable because another process may be using it.'
            : 'The JSON file is not readable.';
        if (is_array($lastError) && isset($lastError['message'])) {
            error_log('Could not open JSON file ' . $path . ': ' . $lastError['message']);
        }
        return [];
    }

    $locked = false;
    try {
        $locked = @flock($handle, LOCK_SH);
        if (!$locked) {
            $failureReason = 'The JSON file could not be locked for reading.';
            return [];
        }
        $json = stream_get_contents($handle);
    } finally {
        if ($locked) {
            @flock($handle, LOCK_UN);
        }
        @fclose($handle);
    }
    if (!is_string($json)) {
        $failureReason = 'The JSON file could not be read.';
        return [];
    }

    try {
        $data = json_decode(
            $json,
            true,
            512,
            JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
        );
    } catch (JsonException) {
        $failureReason = 'The JSON file contains invalid JSON.';
        return [];
    }

    if (!is_array($data)) {
        $failureReason = 'The JSON file root must be an object or array.';
        return [];
    }

    return $data;
}

function encode_json_for_html(array $data): string
{
    return json_encode(
        $data,
        JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
            | JSON_UNESCAPED_SLASHES
            | JSON_INVALID_UTF8_SUBSTITUTE
            | JSON_THROW_ON_ERROR
    );
}
