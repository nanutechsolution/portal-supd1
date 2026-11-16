<?php

namespace App\Rules;

use App\Models\OrganizationalUnit;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ParentUnitIsNotDescendant implements ValidationRule
{
    public function __construct(private readonly ?int $unitId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $parentId = (int) $value;

        if ($this->unitId !== null && $parentId === $this->unitId) {
            $fail('Unit tidak boleh menjadi atasan bagi dirinya sendiri.');

            return;
        }

        $parent = OrganizationalUnit::query()->find($parentId);

        if (! $parent) {
            $fail('Unit atasan tidak ditemukan.');

            return;
        }

        /** @var array<int, true> $visited */
        $visited = [];

        while ($parent) {
            if ($this->unitId !== null && $parent->getKey() === $this->unitId) {
                $fail('Unit atasan tidak boleh berasal dari turunan unit ini.');

                return;
            }

            if (isset($visited[$parent->getKey()])) {
                $fail('Hierarki unit mengandung siklus yang tidak valid.');

                return;
            }

            $visited[$parent->getKey()] = true;
            $parent = $parent->parent;
        }
    }
}
