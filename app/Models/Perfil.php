<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo representa perfis e níveis de acesso.
 *
 * Ponto de atencao: Mantenha parâmetros preparados e regras de consulta explícitas. O banco executa SQL, não boas intenções.
 */

namespace App\Models;

use App\Core\Model;

// Modelo simples para perfis de acesso.
class Perfil extends Model
{
    protected string $table = 'perfis';
}
