<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the wordmark halves as separate flex items so no gap can appear', function () {
    $html = (string) $this->blade('<x-brand />');

    expect($html)
        ->toContain('inline-flex')
        ->toContain('>Scam</span>')
        ->toContain('>proof</span>')
        ->toContain('logo-mark.svg');
});
