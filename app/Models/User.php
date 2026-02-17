<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'last_log_by'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function menuPermissions(int $navigationMenuId): array
    {
        $row = DB::table('role_permission as rp')
            ->join('role_user_account as rua', 'rua.role_id', '=', 'rp.role_id')
            ->where('rua.user_account_id', $this->id)
            ->where('rp.navigation_menu_id', $navigationMenuId)
            ->selectRaw('
                MAX(rp.read_access)   as read_access,
                MAX(rp.write_access)  as write_access,
                MAX(rp.create_access) as create_access,
                MAX(rp.delete_access) as delete_access,
                MAX(rp.import_access) as import_access,
                MAX(rp.export_access) as export_access,
                MAX(rp.logs_access)   as logs_access
            ')
            ->first();

        return [
            'read'   => (bool)($row->read_access   ?? false),
            'write'  => (bool)($row->write_access  ?? false),
            'create' => (bool)($row->create_access ?? false),
            'delete' => (bool)($row->delete_access ?? false),
            'import' => (bool)($row->import_access ?? false),
            'export' => (bool)($row->export_access ?? false),
            'logs'   => (bool)($row->logs_access   ?? false),
        ];
    }

    public function hasMenuAccess(int $navigationMenuId, string $ability): bool
    {
        $perms = $this->menuPermissions($navigationMenuId);

        return (bool)($perms[$ability] ?? false);
    }

}
