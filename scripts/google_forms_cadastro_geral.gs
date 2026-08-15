// Integra o formulario de servidores, colaboradores e visitantes com o SGRP.
// Cole este script no Apps Script da planilha/formulario geral e crie um gatilho "Ao enviar formulario".

const SGRP_ENDPOINT = 'http://172.25.60.169/sgrp/integracoes/google-form/usuarios';
const SGRP_TOKEN = 'TROQUE_PELO_FORM_WEBHOOK_TOKEN_DO_ENV';

function enviarSolicitacaoCadastroGeral(e) {
  if (!e || !e.namedValues) {
    throw new Error('Esta funcao precisa ser executada por um gatilho "Ao enviar formulario". Para teste manual, use a funcao testarEnvioCadastroGeral.');
  }

  const respostas = e.namedValues || {};

  const payload = {
    origem: 'google_forms_cadastro_geral',
    nome: valor(respostas, 'Nome completo'),
    email: valor(respostas, 'E-mail institucional'),
    perfil_solicitado: valor(respostas, 'Tipo de usuario') || 'Visitante',
    setor_funcao: valor(respostas, 'Setor ou funcao'),
    curso_area: valor(respostas, 'Curso ou area vinculada'),
    professor_responsavel: valor(respostas, 'Professor responsavel'),
    projeto_atividade: valor(respostas, 'Projeto ou atividade'),
    observacao: montarObservacaoGeral(respostas)
  };

  enviarParaSgrp(payload);
}

function montarObservacaoGeral(respostas) {
  const partes = [
    campo('Setor ou funcao', valor(respostas, 'Setor ou funcao')),
    campo('Curso ou area vinculada', valor(respostas, 'Curso ou area vinculada')),
    campo('Professor responsavel', valor(respostas, 'Professor responsavel')),
    campo('Projeto ou atividade', valor(respostas, 'Projeto ou atividade')),
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

function testarEnvioCadastroGeral() {
  enviarSolicitacaoCadastroGeral({
    namedValues: {
      'Nome completo': ['Usuario Teste Geral'],
      'E-mail institucional': ['usuario.teste.geral@sgrp.local'],
      'Tipo de usuario': ['Visitante'],
      'Setor ou funcao': ['Teste de integracao'],
      'Curso ou area vinculada': ['CTIC/CESIT'],
      'Professor responsavel': [''],
      'Projeto ou atividade': ['Teste do Google Forms'],
      'Observacoes': ['Envio manual de teste pelo Apps Script.']
    }
  });
}
