// Integra o formulario de alunos com o SGRP.
// Cole este script no Apps Script da planilha/formulario de alunos e crie um gatilho "Ao enviar formulario".

const SGRP_ENDPOINT = 'http://172.25.60.169/sgrp/integracoes/google-form/usuarios';
const SGRP_TOKEN = 'TROQUE_PELO_FORM_WEBHOOK_TOKEN_DO_ENV';

function enviarSolicitacaoCadastroAluno(e) {
  if (!e || !e.namedValues) {
    throw new Error('Esta funcao precisa ser executada por um gatilho "Ao enviar formulario". Para teste manual, use a funcao testarEnvioCadastroAluno.');
  }

  const respostas = e.namedValues || {};

  const payload = {
    origem: 'google_forms_cadastro_alunos',
    nome: valor(respostas, 'Nome completo'),
    email: valor(respostas, 'E-mail institucional'),
    perfil_solicitado: 'Aluno',
    matricula: valor(respostas, 'Numero de matricula'),
    curso: valor(respostas, 'Curso'),
    periodo: valor(respostas, 'Periodo'),
    turma: valor(respostas, 'Turma'),
    turno: valor(respostas, 'Turno'),
    observacao: montarObservacaoAluno(respostas)
  };

  enviarParaSgrp(payload);
}

function montarObservacaoAluno(respostas) {
  const partes = [
    campo('Matricula', valor(respostas, 'Numero de matricula')),
    campo('Curso', valor(respostas, 'Curso')),
    campo('Periodo', valor(respostas, 'Periodo')),
    campo('Turma', valor(respostas, 'Turma')),
    campo('Turno', valor(respostas, 'Turno')),
    campo('Observacoes', valor(respostas, 'Observacoes'))
  ].filter(Boolean);

  return partes.join('\n');
}

function enviarParaSgrp(payload) {
  const resposta = UrlFetchApp.fetch(SGRP_ENDPOINT, {
    method: 'post',
    contentType: 'application/json',
    headers: {
      'X-Form-Token': SGRP_TOKEN
    },
    payload: JSON.stringify(payload),
    muteHttpExceptions: true
  });

  const codigo = resposta.getResponseCode();
  if (codigo < 200 || codigo >= 300) {
    throw new Error('SGRP recusou a solicitacao: ' + codigo + ' - ' + resposta.getContentText());
  }
}

function valor(respostas, pergunta) {
  const chaveNormalizada = normalizar(pergunta);
  const chaveEncontrada = Object.keys(respostas).find((chave) => normalizar(chave) === chaveNormalizada);
  const item = chaveEncontrada ? respostas[chaveEncontrada] : '';
  return Array.isArray(item) ? String(item[0] || '').trim() : String(item || '').trim();
}

function normalizar(texto) {
  return String(texto || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();
}

function campo(rotulo, conteudo) {
  return conteudo ? rotulo + ': ' + conteudo : '';
}

function testarEnvioCadastroAluno() {
  enviarSolicitacaoCadastroAluno({
    namedValues: {
      'Nome completo': ['Aluno Teste'],
      'E-mail institucional': ['aluno.teste@sgrp.local'],
      'Numero de matricula': ['202600001'],
      'Curso': ['Teste'],
      'Periodo': ['1'],
      'Turma': ['A'],
      'Turno': ['Matutino'],
      'Observacoes': ['Envio manual de teste pelo Apps Script.']
    }
  });
}
