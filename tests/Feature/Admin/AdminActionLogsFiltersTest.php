<?php

use App\Livewire\Admin\AdminActionLogs;
use App\Models\AdminActionLog;
use Livewire\Livewire;

test('action type filter limits displayed admin actions', function () {
    AdminActionLog::create([
        'action_type' => AdminActionLog::ACTION_FAMILY_VALIDATED,
        'description' => 'Ligne validation famille',
        'user_label' => 'Alice',
    ]);

    AdminActionLog::create([
        'action_type' => AdminActionLog::ACTION_EMAIL_SENT,
        'description' => 'Ligne envoi e-mail',
        'user_label' => 'Bob',
    ]);

    Livewire::test(AdminActionLogs::class)
        ->set('actionTypeFilter', AdminActionLog::ACTION_EMAIL_SENT)
        ->assertSee('Ligne envoi e-mail')
        ->assertDontSee('Ligne validation famille');
});

test('user filter limits displayed admin actions', function () {
    AdminActionLog::create([
        'action_type' => AdminActionLog::ACTION_EMAIL_SENT,
        'description' => 'Action de Claire',
        'user_label' => 'Claire',
    ]);

    AdminActionLog::create([
        'action_type' => AdminActionLog::ACTION_EMAIL_SENT,
        'description' => 'Action de Daniel',
        'user_label' => 'Daniel',
    ]);

    Livewire::test(AdminActionLogs::class)
        ->set('userSearch', 'Claire')
        ->assertSee('Action de Claire')
        ->assertDontSee('Action de Daniel');
});

test('date range filters displayed admin actions', function () {
    $oldLog = AdminActionLog::create([
        'action_type' => AdminActionLog::ACTION_EMAIL_SENT,
        'description' => 'Ancienne action',
        'user_label' => 'Eva',
    ]);
    $oldLog->forceFill([
        'created_at' => now()->subDays(3),
        'updated_at' => now()->subDays(3),
    ])->saveQuietly();

    $recentLog = AdminActionLog::create([
        'action_type' => AdminActionLog::ACTION_EMAIL_SENT,
        'description' => 'Action récente',
        'user_label' => 'Eva',
    ]);
    $recentLog->forceFill([
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ])->saveQuietly();

    Livewire::test(AdminActionLogs::class)
        ->set('dateFrom', now()->subDays(2)->toDateString())
        ->assertSee('Action récente')
        ->assertDontSee('Ancienne action');
});
