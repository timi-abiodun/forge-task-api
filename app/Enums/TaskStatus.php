<?php

namespace App\Enums;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case BLOCKED = 'blocked'; 
    case REVIEW = 'review';
    case COMPLETED = 'completed';
}