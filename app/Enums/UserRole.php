<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case CHEF = 'chef';
    case DELIVERY = 'delivery';
}
