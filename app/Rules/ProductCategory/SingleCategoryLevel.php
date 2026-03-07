<?php

namespace App\Rules\ProductCategory;

use App\Models\ProductCategory;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SingleCategoryLevel implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_null($value)) {
            return;
        }

        $parent = ProductCategory::select('id', 'parent_id')->find($value);

        if ($parent && $parent->parent_id !== null) {
            $fail('Only one level of nesting is allowed. The selected category is already a child category.');
        }
    }
}
