<?php

namespace App\Enums;

enum UserRole: string
{
    case YouthAdmin = 'youth_admin';
    case SkOfficial = 'sk_official';
    case Youth = 'youth';
}
