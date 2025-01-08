<?php

namespace LonaDB\Enums;

enum Permission: string
{
    case PERMISSION_ADD = "permission_add";
    case CREATE_FUNCTION = "create_function";
    case PASSWORD_CHECK = "password_check";
    case PERMISSION_CHECK = "permission_check";
    case TABLE_CREATE = "table_create";
    case USER_CREATE = "user_create";
    case DELETE_FUNCTION = "delete_function";
    case TABLE_DELETE = "table_delete";
    case USER_DELETE = "user_delete";
    case GET_TABLES = "get_tables";
    case GET_USERS = "get_users";
    case PERMISSION_REMOVE = "permission_remove";
    case READ = "read";
    case WRITE = "write";


    static function findPermission(string $name): ?Permission
    {
        return match($name) {
            "permission_add" => Permission::PERMISSION_ADD,
            "create_function" => Permission::CREATE_FUNCTION,
            "password_check" => Permission::PASSWORD_CHECK,
            "permission_check" => Permission::PERMISSION_CHECK,
            "table_create" => Permission::TABLE_CREATE,
            "user_create" => Permission::USER_CREATE,
            "delete_function" => Permission::DELETE_FUNCTION,
            "table_delete" => Permission::TABLE_DELETE,
            "user_delete" => Permission::USER_DELETE,
            "get_tables" => Permission::GET_TABLES,
            "get_users" => Permission::GET_USERS,
            "permission_remove" => Permission::PERMISSION_REMOVE,
            "read" => Permission::READ,
            "write" => Permission::WRITE,
            default => null,
        };
    }

    static function all(): Array
    {
        //Return array name => permission
        $permissions = array(
            "Add permissions" => Permission::PERMISSION_ADD,
            "Create functions" => Permission::CREATE_FUNCTION,
            "Check passwords" => Permission::PASSWORD_CHECK,
            "Check permissions" => Permission::PERMISSION_CHECK,
            "Create tables" => Permission::TABLE_CREATE,
            "Create users" => Permission::USER_CREATE,
            "Delete functions" => Permission::DELETE_FUNCTION,
            "Delete tables" => Permission::TABLE_DELETE,
            "Delete users" => Permission::USER_DELETE,
            "See tables" => Permission::GET_TABLES,
            "See users" => Permission::GET_USERS,
            "Remove permissions" => Permission::PERMISSION_REMOVE,
            "Read" => Permission::READ,
            "Write" => Permission::WRITE,
        );

        ksort($permissions);

        return $permissions;
    }
}
