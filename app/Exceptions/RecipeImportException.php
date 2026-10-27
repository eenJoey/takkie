<?php

namespace App\Exceptions;

use RuntimeException;

/** Recept ophalen uit een link lukte niet; de melding is voor de gebruiker bedoeld. */
class RecipeImportException extends RuntimeException {}
