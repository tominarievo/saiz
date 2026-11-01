<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** bcrypt compares at most 72 bytes, so reject longer input rather than truncate. */
class PasswordRules implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! mb_check_encoding($value, 'UTF-8') || preg_match('/\A[\p{Z}\s]*\z/u', $value) || mb_strlen($value) < 12 || strlen($value) > 72 || str_contains($value, "\0")) {
            $fail('パスワードは12文字以上、UTF-8で72バイト以内で入力してください。記号・空白も使えます。');
        }
    }

    public static function rules(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'confirmed', new self()];
    }
}
