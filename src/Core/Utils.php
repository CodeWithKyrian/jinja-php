<?php

declare(strict_types=1);

namespace Codewithkyrian\Jinja;

function array_some(array $array, callable $callback): bool
{
    foreach ($array as $key => $value) {
        if ($callback($value, $key)) {
            return true;
        }
    }
    return false;
}

function array_every(array $array, callable $callback): bool
{
    foreach ($array as $key => $value) {
        if (!$callback($value, $key)) {
            return false;
        }
    }
    return true;
}

function toTitleCase(string $str): string
{
    return ucwords(strtolower($str));
}

/**
 * Function that mimics Python's array slicing.
 * @param array $array The array to slice.
 * @param ?int $start The start index of the slice. Defaults to 0.
 * @param ?int $stop The last index of the slice. Defaults to the length of the array
 * @param int $step The step value of the slice. Defaults to 1.
 * @return array
 */
function slice(array $array, ?int $start = null, ?int $stop = null, ?int $step = null): array
{
    $step ??= 1;
    $length = count($array);
    $direction = $step >= 0 ? 1 : -1;

    if ($direction >= 0) {
        $start = is_null($start) ? 0 : ($start < 0 ? max($length + $start, 0) : min($start, $length));
        $stop = is_null($stop) ? $length : ($stop < 0 ? max($length + $stop, 0) : min($stop, $length));
    } else {
        $start = is_null($start) ? $length - 1 : ($start < 0 ? max($length + $start, -1) : min($start, $length - 1));
        $stop = is_null($stop) ? -1 : ($stop < -1 ? max($length + $stop, -1) : min($stop, $length - 1));
    }

    $result = [];
    for ($i = $start; $direction * $i < $direction * $stop; $i += $step) {
        $result[] = $array[$i];
    }
    return $result;
}
