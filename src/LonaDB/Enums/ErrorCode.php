<?php

namespace LonaDB\Enums;

enum ErrorCode: string
{
    case NO_PERMISSIONS = "no_permissions";
    case MISSING_USER = "missing_user";
    case MISSING_ARGUMENTS = "missing_arguments";
    case MISSING_PERMISSION = "missing_permission";
    case MISSING_PARAMETERS = "missing_parameters";
    case MISSING_VARIABLE = "missing_variable";
    case VARIABLE_UNDEFINED = "variable_undefined";
    case BAD_TABLE_NAME = "bad_table_name";
    case NOT_ROOT = "not_root";
    case TABLE_EXISTS = "table_exists";
    case USER_EXISTS = "user_exists";
    case TABLE_MISSING = "table_missing";
    case NOT_TABLE_OWNER = "not_table_owner";
    case USER_DOESNT_EXIST = "user_doesnt_exist";
    case NOT_ALLOWED = "not_allowed";
    case LOGIN_ERROR = "login_error";
    case ACTION_NOT_FOUND = "action_not_found";
    case BAD_PROCESS_ID = "bad_process_id";
    case DECRYPTION_FAILED = "decryption_failed";
    case USER_DELETE_FAILED = "user_delete_failed";


    static function find(string $name): ?ErrorCode
    {
        return match ($name) {
            "no_permissions" => self::NO_PERMISSIONS,
            "missing_user" => self::MISSING_USER,
            "missing_arguments" => self::MISSING_ARGUMENTS,
            "missing_permission" => self::MISSING_PERMISSION,
            "missing_parameters" => self::MISSING_PARAMETERS,
            "missing_variable" => self::MISSING_VARIABLE,
            "variable_undefined" => self::VARIABLE_UNDEFINED,
            "bad_table_name" => self::BAD_TABLE_NAME,
            "not_root" => self::NOT_ROOT,
            "table_exists" => self::TABLE_EXISTS,
            "user_exists" => self::USER_EXISTS,
            "table_missing" => self::TABLE_MISSING,
            "not_table_owner" => self::NOT_TABLE_OWNER,
            "user_doesnt_exist" => self::USER_DOESNT_EXIST,
            "not_allowed" => self::NOT_ALLOWED,
            "login_error" => self::LOGIN_ERROR,
            "action_not_found" => self::ACTION_NOT_FOUND,
            "bad_process_id" => self::BAD_PROCESS_ID,
            "decryption_failed" => self::DECRYPTION_FAILED,
            "user_delete_failed" => self::USER_DELETE_FAILED,
            default => null,
        };
    }
}
