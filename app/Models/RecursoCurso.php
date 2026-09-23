<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo persiste recursos vinculados a cursos.
 *
 * Ponto de atencao: Mantenha parâmetros preparados e regras de consulta explícitas. O banco executa SQL, não boas intenções.
 */

namespace App\Models;

use App\Core\Model;

// Recursos acadêmicos vinculados a cursos/usuários.
class RecursoCurso extends Model
{
    protected string $table = 'recursos_curso';
}
