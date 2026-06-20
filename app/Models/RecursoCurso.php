<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Recursos acadêmicos vinculados a cursos/usuários.
class RecursoCurso extends Model
{
    protected string $table = 'recursos_curso';
}
