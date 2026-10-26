<?php

// Alleen de regels die Takkie gebruikt; overige vallen terug op het Engels (APP_FALLBACK_LOCALE).
return [
    'email' => 'Vul een geldig e-mailadres in.',
    'max' => [
        'string' => ':Attribute mag maximaal :max tekens zijn.',
    ],
    'min' => [
        'string' => ':Attribute moet minimaal :min tekens zijn.',
    ],
    'required' => ':Attribute is verplicht.',
    'string' => ':Attribute moet tekst zijn.',
    'unique' => ':Attribute is al in gebruik.',

    'custom' => [
        'email' => [
            'unique' => 'Er bestaat al een account met dit e-mailadres.',
        ],
    ],

    'attributes' => [
        'name' => 'naam',
        'email' => 'e-mailadres',
        'password' => 'wachtwoord',
    ],
];
