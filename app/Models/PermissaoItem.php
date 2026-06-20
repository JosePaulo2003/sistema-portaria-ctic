<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

// Permissões específicas para retirada de itens.
class PermissaoItem extends Model
{
    protected string $table = 'permissoes_itens';
}
