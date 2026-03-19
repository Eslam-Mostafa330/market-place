<?php

namespace App\Rules\Login;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailOrPhone implements ValidationRule
{
    /**
     * Run the validation rule.
     * If it's not a valid email, check if it looks like a phone number
     * at least 8 characters, contains mostly digits + allowed symbols: +, -, space, (, )
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value) || !is_string($value)) {
            $fail(__('auth.identifier_email_phone'));
            return;
        }

        $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL);

        if (!$isEmail) {
            $isPhone = preg_match('/^[\d\s\+\-\(\)]{8,}$/', trim($value));

            if (!$isPhone) {
                $fail(__('auth.identifier_email_phone'));
            }
        }
    }
}