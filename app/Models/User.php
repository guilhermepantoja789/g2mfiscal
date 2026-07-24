<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\EmpresaPerfil;
use App\Services\Acl\EmpresaAcl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     * is_platform_admin is intentionally excluded — only Artisan may set it.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
        ];
    }

    public function isPlatformAdmin(): bool
    {
        return (bool) $this->is_platform_admin;
    }

    public function empresas()
    {
        return $this->belongsToMany(Empresa::class)->withPivot('perfil')->withTimestamps();
    }

    public function perfilNaEmpresa(int $empresaId): ?EmpresaPerfil
    {
        return app(EmpresaAcl::class)->perfilNaEmpresa($this, $empresaId);
    }

    public function isContadorNaEmpresa(int $empresaId): bool
    {
        return $this->perfilNaEmpresa($empresaId) === EmpresaPerfil::Contador;
    }

    public function podeAdministrarEmpresa(int $empresaId): bool
    {
        return app(EmpresaAcl::class)->podeAdministrar($this, $empresaId);
    }
}
