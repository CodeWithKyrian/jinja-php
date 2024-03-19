<?php

use function Codewithkyrian\Jinja\slice;

beforeEach(function () {
    $this->array = range(0, 9);
});

it('slices the whole array with [:]', function () {
    expect(slice($this->array))->toEqual($this->array);
});


it('slices the array with [start:end]', function () {
    expect(slice($this->array, 0, 3))->toEqual([0, 1, 2])
        ->and(slice($this->array, 0, 0))->toEqual([])
        ->and(slice($this->array, 0, 100))->toEqual($this->array)
        ->and(slice($this->array, 100, 100))->toEqual([]);
});

it('slices the array with [start:end:step]', function () {
    expect(slice($this->array, 1, 4, 2))->toEqual([1, 3])
        ->and(slice($this->array, 1, 8, 3))->toEqual([1, 4, 7])
        ->and(slice($this->array, 1, 8, 10))->toEqual([1]);
});

// Add similar tests for other cases

it('slices with negative start index', function () {
    expect(slice($this->array, -3))->toEqual([7, 8, 9])
        ->and(slice($this->array, -10))->toEqual($this->array);
});

it('slices with negative start and end index', function () {
    expect(slice($this->array, -3, -1))->toEqual([7, 8])
        ->and(slice($this->array, -1, -1))->toEqual([])
        ->and(slice($this->array, -3, -5))->toEqual([])
        ->and(slice($this->array, -100, -90))->toEqual([])
        ->and(slice($this->array, -100, -1))->toEqual([0, 1, 2, 3, 4, 5, 6, 7, 8]);
});

it('slices with negative start, end, and step', function () {
    expect(slice($this->array, -3, -1, 2))->toEqual([7]);
});