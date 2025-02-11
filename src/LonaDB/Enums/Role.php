<?php

namespace LonaDB\Enums;

enum Role: string
{
    case SUPERUSER = "superuser";
    case ADMIN = "administrator";
    case USER = "user";
    case DEFAULT = "default";

    static function find(string $name): ?Role
    {
        return match ($name) {
            'superuser' => Role::SUPERUSER,
            'administrator' => Role::ADMIN,
            'user' => Role::USER,
            default => Role::DEFAULT
        };
    }

    public function isIn(array $roles): bool
    {
        return in_array($this, $roles);
    }


    public function isNotIn(array $roles): bool
    {
        return !$this->isIn($roles);
    }

    public function isNot(Role $role): bool
    {
        return $this->value != $role->value;
    }

    public function all(): Array
    {
        return array(
            "Superuser" => Role::SUPERUSER,
            "Administrator" => Role::ADMIN,
            "User" => Role::USER,
        );
    }

    public function findByName(string $name): ?Role
    {
        return match (strtolower($name)) {
            'superuser' => Role::SUPERUSER,
            'administrator' => Role::ADMIN,
            'user' => Role::USER,
            default => null,
        };
    }
}
