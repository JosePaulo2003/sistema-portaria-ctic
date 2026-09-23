<?php
declare(strict_types=1);

/**
 * GUIA DE MANUTENCAO: Este arquivo remove visitantes cujo prazo terminou.
 *
 * Ponto de atencao: Leia a saída antes de comemorar. Script que termina sem barulho pode ter funcionado ou apenas desistido com elegância.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acesso restrito.');
}

require dirname(__DIR__) . '/config/bootstrap.php';

$total = (new App\Models\User())->purgeExpiredVisitors();
echo "VISITANTES_EXPIRADOS_REMOVIDOS={$total}\n";
