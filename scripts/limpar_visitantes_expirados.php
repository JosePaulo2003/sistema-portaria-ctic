<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acesso restrito.');
}

require dirname(__DIR__) . '/config/bootstrap.php';

$total = (new App\Models\User())->purgeExpiredVisitors();
echo "VISITANTES_EXPIRADOS_REMOVIDOS={$total}\n";
