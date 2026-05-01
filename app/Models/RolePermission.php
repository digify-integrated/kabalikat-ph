<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $table = 'role_permission';

    protected $fillable = [
        'role_id',
        'role_name',
        'navigation_menu_id',
        'navigation_menu_name',
        'read_access',
        'write_access',
        'create_access',
        'delete_access',
        'import_access',
        'export_access',
        'logs_access',
        'last_log_by'
    ];

    protected $casts = [
        'read_access'   => 'boolean',
        'write_access'  => 'boolean',
        'create_access' => 'boolean',
        'delete_access' => 'boolean',
        'import_access' => 'boolean',
        'export_access' => 'boolean',
        'logs_access'   => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function navigationMenu()
    {
        return $this->belongsTo(NavigationMenu::class, 'navigation_menu_id');
    }

    public function hasAccess(string $ability): bool
    {
        return (bool)($this->{$ability . '_access'} ?? false);
    }

    public function toArrayPermissions(): array
    {
        return [
            'read'   => $this->read_access,
            'write'  => $this->write_access,
            'create' => $this->create_access,
            'delete' => $this->delete_access,
            'import' => $this->import_access,
            'export' => $this->export_access,
            'logs'   => $this->logs_access,
        ];
    }

    public function scopeForMenu($query, int $navigationMenuId)
    {
        return $query->where('navigation_menu_id', $navigationMenuId);
    }

    public function scopeForRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }
}
