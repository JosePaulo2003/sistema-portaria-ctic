<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Períodos usados para organizar o calendário acadêmico.
class PeriodoAcademico extends Model
{
    protected string $table = 'periodos_academicos';
}
