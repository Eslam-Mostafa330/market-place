<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert as PHPUnit;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot the application and talk to the database, so they get the
| Laravel TestCase and a migrated schema per test. Unit tests stay free of both
| so they remain fast and independent of any database being available.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

/**
 * Assert a money column equals the given amount, comparing as fixed-point
 * strings so 15 and '15.00' are treated as equal and float drift cannot make a
 * wrong total pass.
 */
expect()->extend('to_be_money', function (string|float|int $expected) {
    expect(number_format((float) $this->value, 2, '.', ''))
        ->toBe(number_format((float) $expected, 2, '.', ''));

    return $this;
});

/*
|--------------------------------------------------------------------------
| Response macros
|--------------------------------------------------------------------------
*/

/**
 * Assert a 422 carrying validation messages for the given fields.
 *
 * The API wraps every response in its own envelope and puts validation messages
 * under `data`, so Laravel's assertJsonValidationErrors — which looks for a
 * top-level `errors` key — never matches. This keeps that knowledge in one place
 * instead of spreading the envelope's shape across every test.
 */
TestResponse::macro('assertApiValidationErrors', function (string|array $fields) {
    $this->assertStatus(422);

    $errors = $this->json('data') ?? [];

    foreach ((array) $fields as $field) {
        PHPUnit::assertArrayHasKey(
            $field,
            $errors,
            "Expected a validation error for '{$field}'. Got: " . json_encode(array_keys($errors)),
        );
    }

    return $this;
});
