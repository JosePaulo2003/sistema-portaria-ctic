<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Cursos cadastrados pela secretaria.
class Curso extends Model
{
    protected string $table = 'cursos';
}
