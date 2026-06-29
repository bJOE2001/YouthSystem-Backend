<?php

namespace App\Enums;

enum YouthProfileStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
