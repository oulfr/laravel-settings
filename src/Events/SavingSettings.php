<?php

namespace Spatie\LaravelSettings\Events;

use Illuminate\Support\Collection;
use Spatie\LaravelSettings\Settings;
use Spatie\LaravelSettings\Support\SettingsPropertyDataCollection;

class SavingSettings
{
    public Settings $settings;

    public Collection $properties;

    public function __construct(
        Collection $properties,
        Settings $settings
    ) {
        $this->properties = $properties;

        $this->settings = $settings;
    }
}
