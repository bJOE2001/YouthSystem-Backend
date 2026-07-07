<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case SkAdmin = 'sk_admin';
    case Youth = 'youth';
}
