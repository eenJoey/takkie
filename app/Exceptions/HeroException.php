<?php

namespace App\Exceptions;

use RuntimeException;

/** Iets in "Mijn held" kan niet (al gekookt vandaag, nog geen level 50); de melding is voor de gebruiker. */
class HeroException extends RuntimeException {}
