<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Löst die Abteilungs-GVP (Kürzel „A“) für die Teamwertung Abteilungen auf.
 *
 * - User direkt auf A → diese GVP
 * - User auf direktem Child einer A → Parent-A
 * - GB, Stab, Root ohne Parent, Parent ≠ A → null (nicht in Abteilungs-Wertung)
 */
final class DepartmentGvpResolver
{
    public const DEPARTMENT_KUERZEL = 'A';

    public function resolve(?Model $gvp): ?Model
    {
        if ($gvp === null) {
            return null;
        }

        if ($this->isDepartment($gvp)) {
            return $gvp;
        }

        $parent = $gvp->relationLoaded('parent')
            ? $gvp->getRelation('parent')
            : $gvp->getRelationValue('parent');

        if ($parent instanceof Model && $this->isDepartment($parent)) {
            return $parent;
        }

        return null;
    }

    public function resolveId(?Model $gvp): ?int
    {
        $department = $this->resolve($gvp);

        return $department !== null ? (int) $department->getKey() : null;
    }

    public function isDepartment(Model $gvp): bool
    {
        return trim((string) $gvp->getAttribute('kuerzel')) === self::DEPARTMENT_KUERZEL;
    }
}
