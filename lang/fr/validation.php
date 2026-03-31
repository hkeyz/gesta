<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages.
    |
    */

    'accepted' => 'Le champ :attribute doit être accepté.',
    'active_url' => "Le champ :attribute n'est pas une URL valide.",
    'after' => 'Le champ :attribute doit être une date postérieure au :date.',
    'after_or_equal' => 'Le champ :attribute doit être une date postérieure ou égale au :date.',
    'alpha' => 'Le champ :attribute doit contenir uniquement des lettres.',
    'alpha_dash' => 'Le champ :attribute doit contenir uniquement des lettres, des chiffres et des tirets.',
    'alpha_num' => 'Le champ :attribute doit contenir uniquement des chiffres et des lettres.',
    'array' => 'Le champ :attribute doit être un tableau.',
    'before' => 'Le champ :attribute doit être une date antérieure au :date.',
    'before_or_equal' => 'Le champ :attribute doit être une date antérieure ou égale au :date.',
    'between' => [
        'numeric' => 'La valeur de :attribute doit être comprise entre :min et :max.',
        'file' => 'La taille du fichier de :attribute doit être comprise entre :min et :max kilo-octets.',
        'string' => 'Le texte :attribute doit contenir entre :min et :max caractères.',
        'array' => 'Le tableau :attribute doit contenir entre :min et :max éléments.',
    ],
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed' => 'Le champ de confirmation :attribute ne correspond pas.',
    'date' => "Le champ :attribute n'est pas une date valide.",
    'date_format' => 'Le champ :attribute ne correspond pas au format :format.',
    'different' => 'Les champs :attribute et :other doivent être différents.',
    'digits' => 'Le champ :attribute doit contenir :digits chiffres.',
    'digits_between' => 'Le champ :attribute doit contenir entre :min et :max chiffres.',
    'dimensions' => "La taille de l'image :attribute n'est pas conforme.",
    'distinct' => 'Le champ :attribute a une valeur en double.',
    'email' => 'Le champ :attribute doit être une adresse courriel valide.',
    'exists' => 'Le champ :attribute sélectionné est invalide.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'filled' => 'Le champ :attribute doit avoir une valeur.',
    'image' => 'Le champ :attribute doit être une image.',
    'in' => 'Le champ :attribute est invalide.',
    'in_array' => "Le champ :attribute n'existe pas dans :other.",
    'integer' => 'Le champ :attribute doit être un entier.',
    'ip' => 'Le champ :attribute doit être une adresse IP valide.',
    'ipv4' => 'Le champ :attribute doit être une adresse IPv4 valide.',
    'ipv6' => 'Le champ :attribute doit être une adresse IPv6 valide.',
    'json' => 'Le champ :attribute doit être un document JSON valide.',
    'max' => [
        'numeric' => 'La valeur de :attribute ne peut être supérieure à :max.',
        'file' => 'La taille du fichier de :attribute ne peut pas dépasser :max kilo-octets.',
        'string' => 'Le texte de :attribute ne peut contenir plus de :max caractères.',
        'array' => 'Le tableau :attribute ne peut contenir plus de :max éléments.',
    ],
    'mimes' => 'Le champ :attribute doit être un fichier de type : :values.',
    'mimetypes' => 'Le champ :attribute doit être un fichier de type : :values.',
    'min' => [
        'numeric' => 'La valeur de :attribute doit être supérieure ou égale à :min.',
        'file' => 'La taille du fichier de :attribute doit être supérieure à :min kilo-octets.',
        'string' => 'Le texte :attribute doit contenir au moins :min caractères.',
        'array' => 'Le tableau :attribute doit contenir au moins :min éléments.',
    ],
    'not_in' => "Le champ :attribute sélectionné n'est pas valide.",
    'numeric' => 'Le champ :attribute doit contenir un nombre.',
    'present' => 'Le champ :attribute doit être présent.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'required_if' => 'Le champ :attribute est obligatoire quand la valeur de :other est :value.',
    'required_unless' => 'Le champ :attribute est obligatoire sauf si :other est :values.',
    'required_with' => 'Le champ :attribute est obligatoire quand :values est présent.',
    'required_with_all' => 'Le champ :attribute est obligatoire quand :values est présent.',
    'required_without' => "Le champ :attribute est obligatoire quand :values n'est pas présent.",
    'required_without_all' => "Le champ :attribute est requis quand aucun de :values n'est présent.",
    'same' => 'Les champs :attribute et :other doivent être identiques.',
    'size' => [
        'numeric' => 'La valeur de :attribute doit être :size.',
        'file' => 'La taille du fichier de :attribute doit être de :size kilo-octets.',
        'string' => 'Le texte de :attribute doit contenir :size caractères.',
        'array' => 'Le tableau :attribute doit contenir :size éléments.',
    ],
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'timezone' => 'Le champ :attribute doit être un fuseau horaire valide.',
    'unique' => 'La valeur du champ :attribute est déjà utilisée.',
    'uploaded' => "Le fichier du champ :attribute n'a pu être téléversé.",
    'url' => "Le format de l'URL de :attribute n'est pas valide.",
    'indisposable' => "Cet e-mail n'est pas autorisé.",

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'message-personnalise',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [
        'name' => 'nom',
        'username' => "nom d'utilisateur",
        'email' => 'adresse courriel',
        'first_name' => 'prénom',
        'last_name' => 'nom',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'city' => 'ville',
        'country' => 'pays',
        'address' => 'adresse',
        'phone' => 'téléphone',
        'mobile' => 'portable',
        'age' => 'âge',
        'sex' => 'sexe',
        'gender' => 'genre',
        'day' => 'jour',
        'month' => 'mois',
        'year' => 'année',
        'hour' => 'heure',
        'minute' => 'minute',
        'second' => 'seconde',
        'title' => 'titre',
        'content' => 'contenu',
        'description' => 'description',
        'excerpt' => 'extrait',
        'date' => 'date',
        'time' => 'heure',
        'available' => 'disponible',
        'size' => 'taille',
    ],

    'custom-messages' => [
        'quantity_not_available' => 'Seulement :qty :unit disponible',
        'this_field_is_required' => 'Ce champ est requis',
    ],
    'accepted_if' => ':attribute doit etre accepte quand :other vaut :value.',
    'ascii' => ':attribute doit contenir uniquement des caracteres alphanumeriques, des tirets et des underscores sur un octet.',
    'current_password' => 'Le mot de passe est incorrect.',
    'date_equals' => ':attribute doit etre une date egale a :date.',
    'decimal' => ':attribute doit comporter :decimal decimales.',
    'declined' => ':attribute doit etre refuse.',
    'declined_if' => ':attribute doit etre refuse quand :other vaut :value.',
    'doesnt_end_with' => ":attribute ne doit pas se terminer par l'une des valeurs suivantes : :values.",
    'doesnt_start_with' => ":attribute ne doit pas commencer par l'une des valeurs suivantes : :values.",
    'ends_with' => ":attribute doit se terminer par l'une des valeurs suivantes : :values.",
    'enum' => 'La valeur selectionnee pour :attribute est invalide.',
    'gt' => [
        'array' => ':attribute doit contenir plus de :value elements.',
        'file' => ':attribute doit etre plus grand que :value kilo-octets.',
        'numeric' => ':attribute doit etre superieur a :value.',
        'string' => ':attribute doit contenir plus de :value caracteres.',
    ],
    'gte' => [
        'array' => ':attribute doit contenir au moins :value elements.',
        'file' => ':attribute doit etre superieur ou egal a :value kilo-octets.',
        'numeric' => ':attribute doit etre superieur ou egal a :value.',
        'string' => ':attribute doit contenir au moins :value caracteres.',
    ],
    'lowercase' => ':attribute doit etre en minuscules.',
    'lt' => [
        'array' => ':attribute doit contenir moins de :value elements.',
        'file' => ':attribute doit etre plus petit que :value kilo-octets.',
        'numeric' => ':attribute doit etre inferieur a :value.',
        'string' => ':attribute doit contenir moins de :value caracteres.',
    ],
    'lte' => [
        'array' => ':attribute ne doit pas contenir plus de :value elements.',
        'file' => ':attribute doit etre inferieur ou egal a :value kilo-octets.',
        'numeric' => ':attribute doit etre inferieur ou egal a :value.',
        'string' => ':attribute ne doit pas contenir plus de :value caracteres.',
    ],
    'mac_address' => ':attribute doit etre une adresse MAC valide.',
    'max_digits' => ':attribute ne doit pas comporter plus de :max chiffres.',
    'min_digits' => ':attribute doit comporter au moins :min chiffres.',
    'multiple_of' => ':attribute doit etre un multiple de :value.',
    'not_regex' => ':attribute a un format invalide.',
    'password' => [
        'letters' => ':attribute doit contenir au moins une lettre.',
        'mixed' => ':attribute doit contenir au moins une lettre majuscule et une lettre minuscule.',
        'numbers' => ':attribute doit contenir au moins un chiffre.',
        'symbols' => ':attribute doit contenir au moins un symbole.',
        'uncompromised' => ':attribute a deja apparu dans une fuite de donnees. Veuillez en choisir un autre.',
    ],
    'prohibited' => ':attribute est interdit.',
    'prohibited_if' => ':attribute est interdit quand :other vaut :value.',
    'prohibited_unless' => ':attribute est interdit sauf si :other est dans :values.',
    'prohibits' => ':attribute interdit la presence de :other.',
    'required_array_keys' => ':attribute doit contenir des entrees pour :values.',
    'required_if_accepted' => ':attribute est requis quand :other est accepte.',
    'starts_with' => ":attribute doit commencer par l'une des valeurs suivantes : :values.",
    'ulid' => ':attribute doit etre un ULID valide.',
    'uppercase' => ':attribute doit etre en majuscules.',
    'uuid' => ':attribute doit etre un UUID valide.',
];
