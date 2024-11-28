<?php

namespace LonaDB\Enums;

enum Event: string
{
    case USER_DELETE = "user_delete";
    case FUNCTION_CREATE = "function_create";
    case PERMISSION_ADD = "permission_add";
    case TABLE_CREATE = "table_create";
    case TABLE_DELETE = "table_delete";
    case USER_CREATE = "user_create";
    case FUNCTION_DELETE = "function_delete";
    case EVAL = "eval";
    case FUNCTION_EXECUTE = "function_execute";
    case PERMISSION_REMOVE = "permission_remove";
    case VALUE_REMOVE = "value_remove";
    case VALUE_SET = "value_set";
}
