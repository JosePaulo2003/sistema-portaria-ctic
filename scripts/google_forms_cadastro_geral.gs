// Integra o formulario geral de cadastro com o SGRP.

const SGRP_ENDPOINT = 'https://exemption-escargot-spokesman.ngrok-free.dev/integracoes/google-form/usuarios';
const SGRP_TOKEN = '4b3ad82c6997a8e3e2a8369f6864229c84960d7caed6f9b67c19ce0848252410';
const FORM_ID = '1G3YvnhER3_GE4ezkCvxJnHjFA54jKYQvQk86QQuWCk4';

function sincronizarCadastroGeral(e) {
  const respostas = e && e.response
    ? [e.response]
    : FormApp.openById(FORM_ID).getResponses();

  const props = PropertiesService.getScriptProperties();

  for (const resposta of respostas) {
    const dados = respostaParaObjeto(resposta);
    const email = valorObjeto(dados, 'E-mail institucional') || resposta.getRespondentEmail() || '';
    const nome = valorObjeto(dados, 'Nome completo');

    if (!nome || !email) {
      Logger.log('Ignorado: nome ou e-mail vazio. Dados: ' + JSON.stringify(dados));
      continue;
    }

    const chave = 'cadastro_geral_enviado_' + resposta.getId();

    if (props.getProperty(chave)) {
      Logger.log('Ja enviado anteriormente: ' + email);
      continue;
    }

    const perfil = perfilSolicitado(valorObjeto(dados, 'Tipo de usuario'));
    const payload = {
      origem: 'google_forms_cadastro_geral',
      nome: nome,
      email: email,
      perfil_solicitado: perfil,
      setor_funcao: perfil === 'Motorista'
        ? 'Secretaria Administrativa'
        : valorObjeto(dados, 'Setor ou funcao'),
      curso_area: valorObjeto(dados, 'Curso ou area vinculada'),
      professor_responsavel: valorObjeto(dados, 'Professor responsavel'),
      projeto_atividade: valorObjeto(dados, 'Projeto ou atividade'),
      observacao: montarObservacaoGeral(dados)
    };

    const resultado = enviarParaSgrp(payload);
    Logger.log('Resposta SGRP para ' + email + ': ' + JSON.stringify(resultado));

    if (resultado.ok || resultado.codigo === 409) {
      props.setProperty(chave, new Date().toISOString());
      continue;
    }

    throw new Error('Falha ao enviar ' + email + ': ' + resultado.mensagem);
  }
}

// Execute uma vez para acrescentar Motorista a pergunta "Tipo de usuario".
// As alternativas existentes sao preservadas.
function configurarFormularioMotorista() {
  const form = FormApp.openById(FORM_ID);
  const item = form.getItems().find((itemFormulario) =>
    normalizar(itemFormulario.getTitle()) === normalizar('Tipo de usuario')
  );

  if (!item) {
    throw new Error('Pergunta "Tipo de usuario" nao encontrada no formulario.');
  }

  let pergunta;
  if (item.getType() === FormApp.ItemType.MULTIPLE_CHOICE) {
    pergunta = item.asMultipleChoiceItem();
  } else if (item.getType() === FormApp.ItemType.LIST) {
    pergunta = item.asListItem();
  } else {
    throw new Error('A pergunta "Tipo de usuario" precisa ser Multipla escolha ou Lista suspensa.');
  }

  const opcoes = pergunta.getChoices().map((opcao) => opcao.getValue());
  if (!opcoes.some((opcao) => normalizar(opcao) === 'motorista')) {
    pergunta.setChoiceValues(opcoes.concat(['Motorista']));
    Logger.log('Opcao Motorista adicionada ao formulario.');
  } else {
    Logger.log('A opcao Motorista ja existe no formulario.');
  }
}

function testarConexaoSgrpGeral() {
  const resultado = enviarParaSgrp({
    origem: 'teste_apps_script_geral',
    nome: 'Teste Apps Script Motorista',
    email: 'teste.apps.script.motorista+' + Date.now() + '@sgrp.local',
    perfil_solicitado: 'Motorista',
    setor_funcao: 'Secretaria Administrativa',
    curso_area: '',
    professor_responsavel: '',
    projeto_atividade: 'Teste de integracao',
    observacao: 'Teste manual do cadastro de Motorista'
  });

  Logger.log(JSON.stringify(resultado));
}

function respostaParaObjeto(resposta) {
  const obj = {};

  resposta.getItemResponses().forEach((item) => {
    const titulo = item.getItem().getTitle();
    const conteudo = item.getResponse();

    obj[titulo] = Array.isArray(conteudo) ? conteudo.join(', ') : String(conteudo || '').trim();
  });

  return obj;
}

function perfilSolicitado(valor) {
  const perfil = String(valor || '').trim();
  return normalizar(perfil) === 'motorista' ? 'Motorista' : (perfil || 'Visitante');
}

function montarObservacaoGeral(dados) {
  return [
    campo('Setor ou funcao', valorObjeto(dados, 'Setor ou funcao')),
    campo('Curso ou area vinculada', valorObjeto(dados, 'Curso ou area vinculada')),
    campo('Professor responsavel', valorObjeto(dados, 'Professor responsavel')),
    campo('Projeto ou atividade', valorObjeto(dados, 'Projeto ou atividade')),
    campo('Observacoes', valorObjeto(dados, 'Observacoes'))
  ].filter(Boolean).join('\n');
}

function enviarParaSgrp(payload) {
  try {
    const resposta = UrlFetchApp.fetch(SGRP_ENDPOINT, {
      method: 'post',
      contentType: 'application/json',
      headers: {
        'X-Form-Token': SGRP_TOKEN,
        'ngrok-skip-browser-warning': 'true'
      },
      payload: JSON.stringify(payload),
      muteHttpExceptions: true
    });

    const codigo = resposta.getResponseCode();
    const texto = resposta.getContentText();

    return {
      ok: codigo >= 200 && codigo < 300,
      codigo: codigo,
      texto: texto,
      mensagem: codigo + ' - ' + texto
    };
  } catch (erro) {
    return {
      ok: false,
      codigo: 0,
      texto: '',
      mensagem: erro && erro.message ? erro.message : String(erro)
    };
  }
}

function valorObjeto(obj, pergunta) {
  const chaveNormalizada = normalizar(pergunta);
  const chaveEncontrada = Object.keys(obj).find((chave) => normalizar(chave) === chaveNormalizada);

  return chaveEncontrada ? String(obj[chaveEncontrada] || '').trim() : '';
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
