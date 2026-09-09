<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case SubAdmin = 'sub_admin';
    case SkAdmin = 'sk_admin';
    case Youth = 'youth';
}
