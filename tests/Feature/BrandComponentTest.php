<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the light and dark logo images, switching by theme', function () {
    $html = (string) $this->blade('<x-brand />');

    expect($html)
        ->toContain('img/scamproof-logo-tight.png')
        ->toContain('img/scamproof-logo-dark-tight.png')
        ->toContain('dark:hidden')
        ->toContain('alt="Scamproof"');
});

it('renders only the dark logo on always-dark surfaces', function () {
    $html = (string) $this->blade('<x-brand dark />');

    expect($html)
        ->toContain('img/scamproof-logo-dark-tight.png')
        ->not->toContain('img/scamproof-logo-tight.png');
});
