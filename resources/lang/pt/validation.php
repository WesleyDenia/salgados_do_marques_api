<?php

return [
    'required' => 'O campo :attribute é obrigatório.',
    'email' => 'Informe um e-mail válido.',
    'unique' => 'Esse :attribute já está a ser usado.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'min' => [
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'max' => [
        'string' => 'O campo :attribute pode ter no máximo :max caracteres.',
    ],
    'date' => 'Informe uma data válida em :attribute.',
    'accepted' => 'É necessário aceitar o campo :attribute.',
    'array' => 'O campo :attribute deve ser um array.',
    'size' => [
        'string' => 'O campo :attribute deve ter exatamente :size caracteres.',
    ],

    'attributes' => [
        'name' => 'nome',
        'email' => 'email',
        'password' => 'senha',
        'password_confirmation' => 'confirmação da senha',
        'phone' => 'telefone',
        'nif' => 'NIF',
        'birth_date' => 'data de nascimento',
        'lgpd' => 'termo LGPD',
        'lgpd.accepted' => 'aceite do termo LGPD',
        'lgpd.version' => 'versão do termo LGPD',
        'lgpd.hash' => 'hash do termo LGPD',
        'lgpd.channel' => 'canal do termo LGPD',
    ],
];
