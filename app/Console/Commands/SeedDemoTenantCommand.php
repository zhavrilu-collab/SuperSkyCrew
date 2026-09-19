<?php

namespace App\Console\Commands;

use Database\Seeders\DemoTenantSeeder;
use Illuminate\Console\Command;

class SeedDemoTenantCommand extends Command
{
    protected $signature = 'hr:seed-demo';

    protected $description = 'Napravi demo organizaciju s korisnicima za sve HR uloge.';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => DemoTenantSeeder::class,
            '--force' => true,
        ]);

        $this->newLine();
        $this->info('Demo tenant: /'.DemoTenantSeeder::SLUG);
        $this->table(
            ['Uloga', 'E-mail', 'Lozinka'],
            collect(DemoTenantSeeder::accounts())->map(fn (array $account) => [
                $account['role']->label(),
                $account['email'],
                DemoTenantSeeder::PASSWORD,
            ])->all(),
        );

        return self::SUCCESS;
    }
}
