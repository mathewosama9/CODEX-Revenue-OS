<?php

namespace App\Modules\Platform\Console;

use App\Modules\Platform\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Creates a CODEX staff account. The password is shown once; 2FA setup is
 * forced on first login.
 */
class CreateAdmin extends Command
{
    protected $signature = 'codex:admin {email} {name}';

    protected $description = 'Create a CODEX internal admin (2FA required at first login)';

    public function handle(): int
    {
        $password = Str::password(20);

        Admin::query()->create([
            'email' => (string) $this->argument('email'),
            'name' => (string) $this->argument('name'),
            'password' => $password,
        ]);

        $this->info('Admin created. One-time password (store it in the password manager now):');
        $this->line($password);

        return self::SUCCESS;
    }
}
