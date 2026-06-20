<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Modelo simples para perfis de acesso.
class Perfil extends Model
{
    protected string $table = 'perfis';
}
