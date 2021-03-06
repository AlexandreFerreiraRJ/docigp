<?php

use App\Data\Repositories\Users;
use App\Data\Repositories\Budgets;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Password;
use App\Services\DataSync\Service as DataSyncService;

Artisan::command('docigp:sync:congressmen', function () {
    $this->info('Synchronizing congressmen...');

    app(DataSyncService::class)->congressmen();
})->describe('Sync congressmen data');

Artisan::command('docigp:sync:parties', function () {
    $this->info('Synchronizing parties...');

    app(DataSyncService::class)->parties();
})->describe('Sync congressmen data');

Artisan::command('docigp:sync:departaments', function () {
    $this->info('Creating departaments...');

    app(DataSyncService::class)->departaments();
})->describe('Create departaments');

Artisan::command('docigp:sync:roles', function () {
    $this->info('Creating roles and abilities...');
    app(DataSyncService::class)->roles();
})->describe('Create roles');

Artisan::command('docigp:budget:generate {baseDate?}', function (
    $baseDate = null
) {
    $this->info('Generating budgets...');

    app(Budgets::class)->generate($baseDate);
})->describe('Sync congressmen data');

Artisan::command('docigp:role:assign {role} {email}', function ($role, $email) {
    if (!($user = app(Users::class)->findByEmail($email))) {
        return $this->info('E-mail não encontrado.');
    }

    $user->assign($role);

    $this->info("{$user->name} is now '{$role}'");
})->describe('Add role to user');

Artisan::command('docigp:role:retract {role} {email}', function (
    $role,
    $email
) {
    if (!($user = app(Users::class)->findByEmail($email))) {
        return $this->info('E-mail não encontrado.');
    }

    $user->retract($role);

    $this->info("{$user->name} is not '{$role}' anymore");
})->describe('Remove role from user');

Artisan::command('docigp:users:create {email} {name}', function (
    $email,
    $name
) {
    $user = app(Users::class)->firstOrCreate(
        ['email' => $email],
        [
            'name' => $name,
            'email' => $email,
            'username' => $email,
        ]
    );

    $this->info("User {$user->email} created");
})->describe('Create user');

Artisan::command('docigp:users:reset-password {email}', function ($email) {
    Password::sendResetLink(['email' => $email]);

    $this->info("Password reset for {$email} was sent");
})->describe('Create user');

Artisan::command('queue:clear {name?}', function ($name = null) {
    Redis::connection()->del($name = $name ?? 'default');

    $this->info("Queue '{$name}' was cleared");
})->describe('Create user');
