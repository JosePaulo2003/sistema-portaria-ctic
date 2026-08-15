<?php
$diasSemana = ['Segunda-feira', 'Terca-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sabado'];
?>
<section class="section-header">
    <div>
        <h1>Reservas de Aula</h1>
        <?php if ($curso): ?>
            <p><?= e($curso['nome']) ?></p>
        <?php endif; ?>
    </div>
</section>

<?php if (!$curso): ?>
    <article class="card">
        <h2>Curso nao vinculado</h2>
        <p>Este coordenador precisa estar vinculado a um curso antes de cadastrar reservas de aula.</p>
    </article>
<?php elseif (!$disciplinas): ?>
    <article class="card">
        <h2>Nenhuma materia cadastrada</h2>
        <p>Cadastre primeiro as materias do curso para liberar as reservas de aula.</p>
        <div class="form-actions">
            <a class="button" href="<?= e(baseUrl('/coordenador/materias')) ?>">Ir para materias</a>
        </div>
    </article>
<?php else: ?>
    <form method="post" action="<?= e(baseUrl('/coordenador/reservas-aulas')) ?>" class="card form-grid">
        <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
        <label>Materia
            <select name="disciplina_id" required>
                <option value="">Selecione</option>
                <?php foreach ($disciplinas as $disciplina): ?>
                    <option value="<?= e($disciplina['id']) ?>"><?= e($disciplina['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Professor
            <select name="professor_id" required>
                <option value="">Selecione</option>
                <?php foreach ($professores as $professor): ?>
                    <option value="<?= e($professor['id']) ?>"><?= e($professor['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Sala
            <select name="sala_nome" required>
                <option value="">Selecione</option>
                <?php foreach ($salas as $sala): ?>
                    <option value="<?= e($sala['nome']) ?>"><?= e($sala['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Periodo academico
            <input name="periodo_academico" required placeholder="Ex.: 2026.1">
        </label>
        <label>Turma
            <input name="turma" required placeholder="Ex.: 4o Noturno">
        </label>
        <label>Dia da semana
            <select name="dia_semana" required>
                <option value="">Selecione</option>
                <?php foreach ($diasSemana as $dia): ?>
                    <option value="<?= e($dia) ?>"><?= e($dia) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Inicio
            <input type="time" name="horario_inicio" required>
        </label>
        <label>Fim
            <input type="time" name="horario_fim" required>
        </label>
        <label class="full">Observacao
            <textarea name="observacao"></textarea>
        </label>
        <div class="form-actions">
            <button class="button" type="submit">Criar reserva</button>
        </div>
    </form>

    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Materia</th>
                    <th>Professor</th>
                    <th>Sala</th>
                    <th>Periodo</th>
                    <th>Turma</th>
                    <th>Dia</th>
                    <th>Horario</th>
                    <th>Situacao</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservas as $reserva): ?>
                    <tr>
                        <td colspan="9">
                            <form method="post" action="<?= e(baseUrl('/coordenador/reservas-aulas/atualizar')) ?>" class="inline-form row-edit-form row-edit-form--aula">
                                <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
                                <input type="hidden" name="id" value="<?= e($reserva['id']) ?>">
                                <label>Materia
                                    <select name="disciplina_id" required>
                                        <?php foreach ($disciplinas as $disciplina): ?>
                                            <option value="<?= e($disciplina['id']) ?>" <?= (int) $reserva['disciplina_id'] === (int) $disciplina['id'] ? 'selected' : '' ?>>
                                                <?= e($disciplina['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Professor
                                    <select name="professor_id" required>
                                        <?php foreach ($professores as $professor): ?>
                                            <option value="<?= e($professor['id']) ?>" <?= (int) $reserva['professor_id'] === (int) $professor['id'] ? 'selected' : '' ?>>
                                                <?= e($professor['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Sala
                                    <select name="sala_nome" required>
                                        <?php foreach ($salas as $sala): ?>
                                            <option value="<?= e($sala['nome']) ?>" <?= $reserva['sala_nome'] === $sala['nome'] ? 'selected' : '' ?>>
                                                <?= e($sala['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Periodo
                                    <input name="periodo_academico" required value="<?= e($reserva['periodo_academico']) ?>">
                                </label>
                                <label>Turma
                                    <input name="turma" required value="<?= e($reserva['turma']) ?>">
                                </label>
                                <label>Dia
                                    <select name="dia_semana" required>
                                        <?php foreach ($diasSemana as $dia): ?>
                                            <option value="<?= e($dia) ?>" <?= $reserva['dia_semana'] === $dia ? 'selected' : '' ?>><?= e($dia) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Inicio
                                    <input type="time" name="horario_inicio" required value="<?= e(substr((string) $reserva['horario_inicio'], 0, 5)) ?>">
                                </label>
                                <label>Fim
                                    <input type="time" name="horario_fim" required value="<?= e(substr((string) $reserva['horario_fim'], 0, 5)) ?>">
                                </label>
                                <label>Situacao
                                    <select name="situacao">
                                        <?php foreach (['ativa', 'inativa', 'cancelada'] as $situacao): ?>
                                            <option value="<?= e($situacao) ?>" <?= $reserva['situacao'] === $situacao ? 'selected' : '' ?>><?= e($situacao) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label>Observacao
                                    <input name="observacao" value="<?= e($reserva['observacao'] ?? '') ?>">
                                </label>
                                <button class="button" type="submit">Salvar</button>
                            </form>
                            <form method="post" action="<?= e(baseUrl('/coordenador/reservas-aulas/excluir')) ?>" class="inline-actions">
                                <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>" data-csrf-token>
                                <input type="hidden" name="id" value="<?= e($reserva['id']) ?>">
                                <button class="button button--danger" data-confirm="Excluir esta reserva de aula?" type="submit">Excluir</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$reservas): ?>
                    <tr><td colspan="9">Nenhuma reserva de aula cadastrada para este curso.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
