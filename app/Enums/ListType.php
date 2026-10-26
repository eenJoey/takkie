<?php

namespace App\Enums;

enum ListType: string
{
    /** takkie: gewone checklist, altijd aan/uit te vinken, geen punten */
    case Checklist = 'checklist';

    /** vinkie: de vaste lijst "Huishoudtaken" met punten en herhaling (één per huishouden) */
    case Tasks = 'tasks';
}
