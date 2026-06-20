<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Registra eventos técnicos quando for necessário investigar comportamento interno.
class LogSistema extends Model
{
    protected string $table = 'logs_sistema';
}
