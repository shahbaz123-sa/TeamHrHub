<?php

namespace Modules\HRM\Rules;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Contracts\Validation\ValidationRule;

class FileOrPath implements ValidationRule
{
    protected array $extensions;

    /**
     * @param array $extensions Allowed extensions (e.g. ['pdf', 'doc', 'docx'])
     */
    public function __construct(array $extensions = ['pdf'])
    {
        $this->extensions = array_map('strtolower', $extensions);
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $request = request();

        // Case 1: Empty/null/not present → let nullable/required handle it
        if (empty($value) || $value === null) {
            return;
        }

        // Case 2: Existing string path → assume valid (for updates)
        if (($request->isMethod('PUT') || $request->isMethod('PATCH')) && is_string($value)) {
            return;
        }

        // Case 3: Uploaded file → check extension
        if ($value instanceof UploadedFile) {
            $ext = strtolower($value->getClientOriginalExtension());

            if (! in_array($ext, $this->extensions, true)) {
                $fail("The $attribute must be one of the following types: " . implode(', ', $this->extensions));
            }

            return;
        }

        // Otherwise invalid
        $fail("The $attribute must be a valid file or an existing file path.");
    }
}
