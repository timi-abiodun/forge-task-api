<?php

namespace App\Enums;

enum MembershipRole: string
{
    case OWNER = 'owner';   // Full control, billing, deletion
    case ADMIN = 'admin';   // Management rights
    case MEMBER = 'member'; // Standard access
    case VIEWER = 'viewer'; // Read-only access
}