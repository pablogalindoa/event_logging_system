<?php

namespace App\Enums;

enum EventLevel: string
{
    case Debug = 'debug';
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
    case Critical = 'critical';
}
