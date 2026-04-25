<?php

dataset('invalid text types', [
    'empty string' => [''],
    'boolean' => [true],
    'integer' => [22],
    'array' => [[]],
]);

dataset('invalid numbers', [
    'empty string' => [''],
    'boolean' => [true],
    'string text' => ['this-is-not-a-number'],
    'array' => [[]],
]);
