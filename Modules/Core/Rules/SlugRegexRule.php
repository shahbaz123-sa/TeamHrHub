<?php

namespace Modules\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SlugRegexRule implements ValidationRule
{
    protected $regex;

    public function __construct($regex = '/^[A-Za-z0-9_-]+$/')
    {
        $this->regex = $regex;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Case 1: Empty/null/not present → let nullable/required handle it
        if (empty($value) || $value === null) {
            return;
        }

        // Case 2: Check if string matches slug regex
        if (preg_match($this->regex, $value)) {
            return;
        }

        // Otherwise invalid
        $fail("The $attribute may only contain letters, numbers, hyphens, and underscores.");
    }
}
