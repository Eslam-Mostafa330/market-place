<?php

namespace App\Rules\ProductCategory;

use App\Models\ProductCategory;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SelectableProductCategory implements ValidationRule
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

        $category = ProductCategory::withCount('children')->find($value);

        if (! $category) {
            $fail(__('validation.exists'));
            return;
        }

        if ($category->children_count > 0) {
            $fail(__('validation.custom.select_subcategory'));
        }
    }
}