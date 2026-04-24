<?php

namespace Webteractive\Mailulator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Webteractive\Mailulator\Models\Inbox;

#[AsCommand(name: 'mailulator:install')]
class InstallCommand extends Command
{
    protected $signature = 'mailulator:install {--no-migrate : Skip running database migrations}';

    protected $description = 'Install all of the Mailulator resources';

    public function handle(): int
    {
        $this->components->info('Installing Mailulator resources.');

        collect([
            'Service Provider' => fn () => $this->callSilent('vendor:publish', ['--tag' => 'mailulator-provider', '--force' => false]) == 0,
            'Configuration' => fn () => $this->callSilent('vendor:publish', ['--tag' => 'mailulator-config', '--force' => false]) == 0,
        ])->each(fn ($task, $description) => $this->components->task($description, $task));

        $this->registerMailulatorServiceProvider();

        if (! $this->option('no-migrate')) {
            $this->components->task('Migrations', function () {
                Artisan::call('migrate', ['--database' => 'mailulator', '--force' => true], $this->output);

                return true;
            });
        }

        $this->maybeSeedFirstInbox();

        $this->components->info('Mailulator scaffolding installed successfully.');

        return self::SUCCESS;
    }

    protected function registerMailulatorServiceProvider(): void
    {
        $appNamespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());
        $fqcn = "{$appNamespace}\\Providers\\MailulatorServiceProvider";
        $providerPath = app_path('Providers/MailulatorServiceProvider.php');

        if (file_exists($providerPath)) {
            file_put_contents($providerPath, str_replace(
                'namespace App\\Providers;',
                "namespace {$appNamespace}\\Providers;",
                file_get_contents($providerPath)
            ));
        }

        $bootstrapProviders = $this->laravel->bootstrapPath('providers.php');

        if (file_exists($bootstrapProviders)) {
            ServiceProvider::addProviderToBootstrapFile($fqcn);

            return;
        }

        $configPath = config_path('app.php');

        if (! file_exists($configPath)) {
            return;
        }

        $contents = file_get_contents($configPath);

        if (Str::contains($contents, $fqcn)) {
            return;
        }

        file_put_contents($configPath, str_replace(
            "{$appNamespace}\\Providers\\EventServiceProvider::class,",
            "{$appNamespace}\\Providers\\EventServiceProvider::class,".PHP_EOL."        {$fqcn}::class,",
            $contents
        ));
    }

    protected function maybeSeedFirstInbox(): void
    {
        if (! DB::connection('mailulator')->getSchemaBuilder()->hasTable('inboxes')) {
            return;
        }

        if (Inbox::query()->exists()) {
            return;
        }

        if (! $this->confirm('Create a first inbox now?', true)) {
            return;
        }

        $name = $this->ask('Inbox name', 'Default');
        $plaintext = Str::random(40);

        Inbox::query()->create([
            'name' => $name,
            'api_key' => Inbox::hashToken($plaintext),
        ]);

        $this->newLine();
        $this->components->info('Inbox created. Save this token — it will not be shown again:');
        $this->line("  <fg=yellow>{$plaintext}</>");
        $this->newLine();
    }
}
