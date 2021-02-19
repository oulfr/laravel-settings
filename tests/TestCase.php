<?php

namespace Spatie\LaravelSettings\Tests;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PHPUnit\Framework\Assert as PHPUnit;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;
use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigrator;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Spatie\LaravelSettings\SettingsCache;
use Spatie\LaravelSettings\SettingsContainer;
use Spatie\LaravelSettings\Support\Crypto;

class TestCase extends BaseTestCase
{
    public function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:yDt5+GiUDRGNCFMLd5L9L7/dIc3wg/7ZmNhNVZEL8SA=');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->artisan('migrate', ['--database' => 'testing']);

        include_once __DIR__ . '/../database/migrations/create_settings_table.php.stub';
        (new \CreateSettingsTable())->up();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelSettingsServiceProvider::class,
        ];
    }

    protected function setRegisteredSettings(array $settings): self
    {
        resolve(SettingsContainer::class)->clearCache();

        config()->set('settings.settings', $settings);

        return $this;
    }

    protected function migrateDummySimpleSettings(
        string $name = 'Louis Armstrong',
        string $description = 'Hello Dolly'
    ): self {
        resolve(SettingsMigrator::class)->inGroup('dummy_simple', function (SettingsBlueprint $blueprint) use ($description, $name): void {
            $blueprint->add('name', $name);
            $blueprint->add('description', $description);
        });

        return $this;
    }

    protected function migrateDummySettings(CarbonImmutable $date): self {
        resolve(SettingsMigrator::class)->inGroup('dummy', function (SettingsBlueprint $blueprint) use ($date): void {
            $blueprint->add('string', 'Ruben');
            $blueprint->add('bool', false);
            $blueprint->add('int', 42);
            $blueprint->add('array', ['John', 'Ringo', 'Paul', 'George']);
            $blueprint->add('nullable_string', null);
            $blueprint->add('default_string', null);
            $blueprint->add('dto', ['name' => 'Freek']);
            $blueprint->add('dto_array', [
                ['name' => 'Seb'],
                ['name' => 'Adriaan'],
            ]);

            $blueprint->add('date_time', $date->toAtomString());
            $blueprint->add('carbon', $date->toAtomString());
            $blueprint->add('nullable_date_time_zone', 'europe/brussels');
        });

        return $this;
    }

    protected function useEnabledCache($app)
    {
        $app['config']->set('settings.cache.enabled', true);
    }

    protected function assertDatabaseHasSetting(string $property, $value): void
    {
        [$group, $name] = explode('.', $property);

        $setting = SettingsProperty::query()
            ->where('group', $group)
            ->where('name', $name)
            ->first();

        PHPUnit::assertNotNull(
            $setting,
            "The setting {$group}.{$name} could not be found in the database"
        );

        PHPUnit::assertEquals($value, json_decode($setting->payload, true));
    }

    protected function assertDatabaseHasEncryptedSetting(string $property, $value): void
    {
        [$group, $name] = explode('.', $property);

        $setting = SettingsProperty::query()
            ->where('group', $group)
            ->where('name', $name)
            ->first();

        PHPUnit::assertNotNull(
            $setting,
            "The setting {$group}.{$name} could not be found in the database"
        );

        PHPUnit::assertNotEquals($value, json_decode($setting->payload, true));
        PHPUnit::assertEquals($value, Crypto::decrypt(json_decode($setting->payload, true)));
    }

    protected function assertDatabaseDoesNotHaveSetting(string $property): void
    {
        [$group, $name] = explode('.', $property);

        $setting = SettingsProperty::query()
            ->where('group', $group)
            ->where('name', $name)
            ->first();

        PHPUnit::assertNull(
            $setting,
            "The setting {$group}.{$name} should not exist in the database"
        );
    }
}
