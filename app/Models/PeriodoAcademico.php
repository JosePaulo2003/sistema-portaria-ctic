<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo mantém períodos usados pelas reservas acadêmicas.
 *
 * Ponto de atencao: Mantenha parâmetros preparados e regras de consulta explícitas. O banco executa SQL, não boas intenções.
 */

namespace App\Models;

use App\Core\Model;

// Períodos usados para organizar o calendário acadêmico.
class PeriodoAcademico extends Model
{
    protected string $table = 'periodos_academicos';
}
