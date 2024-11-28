<?php

namespace LonaDB\Enums;

enum Permission: string
{
    case PERMISSION_ADD = "permission_add";
    case CREATE_FUNCTION = "createFunction";
    case PASSWORD_CHECK = "passwordCheck";
    case PERMISSION_CHECK = "permissionCheck";
    case TABLE_CREATE = "tableCreate";
    case USER_CREATE = "userCreate";
    case DELETE_FUNCTION = "deleteFunction";
    case TABLE_DELETE = "table_delete";
    case USER_DELETE = "user_delete";
    case GET_TABLES = "get_tables";
    case GET_USERS = "get_users";
    case PERMISSION_REMOVE = "permission_remove";
    case ADMIN = "admin";
    case READ = "read";
    case WRITE = "write";


    static function findPermission(string $name): ?Permission
    {
        return match($name) {
            "permission_add" => Permission::PERMISSION_ADD,
            "createFunction" => Permission::CREATE_FUNCTION,
            "passwordCheck" => Permission::PASSWORD_CHECK,
            "permissionCheck" => Permission::PERMISSION_CHECK,
            "tableCreate" => Permission::TABLE_CREATE,
            "userCreate" => Permission::USER_CREATE,
            "deleteFunction" => Permission::DELETE_FUNCTION,
            "table_delete" => Permission::TABLE_DELETE,
            "user_delete" => Permission::USER_DELETE,
            "get_tables" => Permission::GET_TABLES,
            "get_users" => Permission::GET_USERS,
            "permission_remove" => Permission::PERMISSION_REMOVE,
            "admin" => Permission::ADMIN,
            "read" => Permission::READ,
            "write" => Permission::WRITE,
            default => null,
        };
    }
}
