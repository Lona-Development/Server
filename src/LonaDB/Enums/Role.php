<?php

namespace LonaDB\Enums;

enum Role: string
{
    case SUPERUSER = "superuser";
    case ADMIN = "administrator";
    case USER = "user";

    static function find(string $name): ?Role
    {
        return match ($name) {
            'superuser' => Role::SUPERUSER,
            'administrator' => Role::ADMIN,
            'user' => Role::USER,
            default => null,
        };
    }

    public function isIn(array $roles): bool
    {
        return in_array($this->value, $roles);
    }


    public function isNotIn(array $roles): bool
    {
        return !$this->isIn($roles);
    }

    public function isNot(Role $role): bool
    {
        return $this->value !== $role->value;
    }
}
