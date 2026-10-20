<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super_admin';
    case Admin      = 'admin';
    case Agent      = 'agent';
    case Client     = 'client';
}
