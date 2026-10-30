<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Which role can open which admin page, read from the role / any.role
 * middleware of the routes so it always matches the real permissions.
 */
class RoleAccessMap
{
    /**
     * Roles shown as columns, in display order (visitor grants no page).
     */
    public const ROLES = [
        Role::ADMIN,
        Role::VALIDATOR,
        Role::FAMILY_VALIDATOR,
        Role::ORGANIZER,
        Role::RECEPTION,
    ];

    /**
     * Admin pages shown, in sidebar order: route name => label.
     */
    public const PAGES = [
        'admin.dashboard' => 'Tableau de bord',
        'admin.family-validation' => 'Validation familles',
        'admin.validation' => 'Validation cadeaux',
        'admin.labels' => 'Cartes',
        'admin.reception' => 'Réception',
        'admin.confirmations' => 'Confirmations',
        'admin.delivery' => 'Remise',
        'admin.monitoring' => 'Suivi enfants',
        'admin.families' => 'Familles',
        'admin.seasons' => 'Saisons',
        'admin.family-duplicates' => 'Doublons familles',
        'admin.users' => 'Utilisateurs',
        'admin.settings' => 'Paramètres',
        'admin.gifts-management' => 'Cadeaux',
        'admin.texts-management' => 'Textes',
        'admin.validation-messages' => 'Messages de validation',
        'admin.mail-logs' => 'Journal e-mails',
        'admin.admin-action-logs' => 'Journal actions admin',
        'admin.family-submission-logs' => 'Journal demandes familles',
        'admin.dev-tools' => 'Dev Tools',
        'admin.css-showcase' => 'CSS Showcase',
    ];

    /**
     * @return array<int, array{label: string, roles: array<int, string>}>
     */
    public function pages(): array
    {
        $pages = [];

        foreach (self::PAGES as $routeName => $label) {
            $route = Route::getRoutes()->getByName($routeName);

            if (! $route) {
                continue;
            }

            $pages[] = [
                'label' => $label,
                'roles' => $this->allowedRoles($route->gatherMiddleware()),
            ];
        }

        return $pages;
    }

    /**
     * Roles allowed by every role / any.role middleware of a route.
     *
     * @param  array<int, mixed>  $middleware
     * @return array<int, string>
     */
    protected function allowedRoles(array $middleware): array
    {
        $allowed = self::ROLES;

        foreach ($middleware as $entry) {
            if (! is_string($entry) || ! Str::startsWith($entry, ['role:', 'any.role:'])) {
                continue;
            }

            $roles = explode(',', Str::after($entry, ':'));
            $allowed = array_values(array_intersect($allowed, $roles));
        }

        return $allowed;
    }
}
