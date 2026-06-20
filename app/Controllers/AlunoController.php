<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Sala;

// Área do aluno: consulta de informações sem permissão para solicitar sala.
class AlunoController extends Controller
{
    public function index(): void { requireProfile('Aluno'); $this->view('aluno/index', ['title' => 'Aluno']); }
    public function consultaSalas(): void { requireProfile('Aluno'); $this->view('aluno/consulta-salas', ['title' => 'Consulta de Salas', 'salas' => (new Sala())->listDisponibilidade($_GET)]); }
}
