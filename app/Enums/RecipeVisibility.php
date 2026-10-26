<?php

namespace App\Enums;

enum RecipeVisibility: string
{
    /** Alleen leden van het huishouden */
    case Private = 'private';

    /** Vindbaar voor iedereen via ontdekken en zoeken; alleen leden kunnen bewerken */
    case Public = 'public';
}
