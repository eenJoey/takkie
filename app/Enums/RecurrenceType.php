<?php

namespace App\Enums;

enum RecurrenceType: string
{
    case Once = 'once';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
}
