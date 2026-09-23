<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo persiste cursos e seus vínculos acadêmicos.
 *
 * Ponto de atencao: Mantenha parâmetros preparados e regras de consulta explícitas. O banco executa SQL, não boas intenções.
 */

namespace App\Models;

use App\Core\Model;

// Cursos cadastrados pela secretaria.
class Curso extends Model
{
    protected string $table = 'cursos';
}
