from pathlib import Path
import unicodedata

from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import cm
from reportlab.platypus import PageBreak, Paragraph, SimpleDocTemplate, Spacer, Table, TableStyle


SAIDA = Path("output/pdf")
SAIDA.mkdir(parents=True, exist_ok=True)


def nome_arquivo(nome):
    texto = unicodedata.normalize("NFKD", nome).encode("ascii", "ignore").decode("ascii")
    return "guia_" + texto.lower().replace(" ", "_").replace("-", "_") + ".pdf"


def estilos():
    base = getSampleStyleSheet()
    base["Title"].fontName = "Helvetica-Bold"
    base["Title"].fontSize = 21
    base["Title"].leading = 27
    base["Heading1"].fontName = "Helvetica-Bold"
    base["Heading1"].fontSize = 15
    base["Heading1"].leading = 20
    base["Heading2"].fontName = "Helvetica-Bold"
    base["Heading2"].fontSize = 12
    base["Heading2"].leading = 16
    base["BodyText"].fontName = "Helvetica"
    base["BodyText"].fontSize = 10.2
    base["BodyText"].leading = 14.5
    base.add(ParagraphStyle(name="Pequeno", parent=base["BodyText"], fontSize=8.8, leading=12, textColor=colors.HexColor("#52615d")))
    base.add(ParagraphStyle(name="Passo", parent=base["BodyText"], leftIndent=12, firstLineIndent=-12, spaceAfter=5))
    return base


def cabecalho_rodape(canvas, doc):
    canvas.saveState()
    canvas.setFillColor(colors.HexColor("#245f49"))
    canvas.rect(0, A4[1] - 1.15 * cm, A4[0], 1.15 * cm, fill=1, stroke=0)
    canvas.setFillColor(colors.white)
    canvas.setFont("Helvetica-Bold", 10)
    canvas.drawString(1.5 * cm, A4[1] - 0.72 * cm, "SGRP - Sistema de Gestão de Recursos Pedagógicos")
    canvas.setFillColor(colors.HexColor("#245f49"))
    canvas.setFont("Helvetica", 8)
    canvas.drawCentredString(A4[0] / 2, 0.8 * cm, f"CTIC-CESIT - Guia de uso - Página {doc.page}")
    canvas.restoreState()


def paragrafo(texto, estilo):
    return Paragraph(texto, estilo)


def lista_simples(itens, st):
    blocos = []
    for item in itens:
        blocos.append(paragrafo(f"- {item}", st["BodyText"]))
        blocos.append(Spacer(1, 3))
    return blocos


def passos(itens, st):
    blocos = []
    for indice, item in enumerate(itens, 1):
        blocos.append(paragrafo(f"{indice}. {item}", st["Passo"]))
    return blocos


def montar_guia(nome, subtitulo, objetivo, secoes):
    st = estilos()
    caminho = SAIDA / nome_arquivo(nome)
    doc = SimpleDocTemplate(
        str(caminho),
        pagesize=A4,
        rightMargin=1.7 * cm,
        leftMargin=1.7 * cm,
        topMargin=1.8 * cm,
        bottomMargin=1.6 * cm,
        title=f"Guia - {nome}",
    )

    story = [
        paragrafo(f"Guia do usuário: {nome}", st["Title"]),
        paragrafo(subtitulo, st["Pequeno"]),
        Spacer(1, 12),
        paragrafo("Objetivo deste guia", st["Heading1"]),
        paragrafo(objetivo, st["BodyText"]),
        Spacer(1, 10),
        paragrafo("Antes de começar", st["Heading1"]),
    ]
    story.extend(lista_simples([
        "Entre no sistema com seu e-mail e senha.",
        "Confira se o nome do seu perfil aparece no menu superior.",
        "Leia as mensagens verdes ou vermelhas que aparecem depois de salvar uma ação.",
        "Quando terminar, clique em Sair para encerrar a sessão.",
    ], st))
    story.append(Spacer(1, 8))

    for secao in secoes:
        story.append(paragrafo(secao["titulo"], st["Heading1"]))
        if secao.get("quando"):
            story.append(paragrafo("Quando usar", st["Heading2"]))
            story.append(paragrafo(secao["quando"], st["BodyText"]))
            story.append(Spacer(1, 5))
        story.append(paragrafo("Passo a passo", st["Heading2"]))
        story.extend(passos(secao["passos"], st))
        if secao.get("cuidados"):
            story.append(Spacer(1, 3))
            story.append(paragrafo("Cuidados", st["Heading2"]))
            story.extend(lista_simples(secao["cuidados"], st))
        story.append(Spacer(1, 10))

    story.append(PageBreak())
    story.append(paragrafo("Dúvidas rápidas", st["Heading1"]))
    story.extend(lista_simples([
        "Se uma opção não aparecer, seu perfil pode não ter permissão para aquela ação.",
        "Se uma lista estiver vazia, pode não haver registros disponíveis naquele momento.",
        "Se uma ação foi feita por engano, avise a pessoa responsável antes de tentar apagar registros.",
        "Se o sistema mostrar bloqueio ou advertência, procure a portaria ou o desenvolvedor para conferência.",
    ], st))

    story.append(Spacer(1, 14))
    story.append(paragrafo("Boas práticas de segurança", st["Heading1"]))
    story.extend(lista_simples([
        "Não compartilhe sua senha.",
        "Não use a conta de outra pessoa.",
        "Confira nomes, salas, itens e horários antes de salvar.",
        "Não deixe o sistema aberto em computador compartilhado.",
    ], st))

    story.append(Spacer(1, 14))
    story.append(paragrafo("Onde pedir ajuda", st["Heading1"]))
    tabela = Table([
        ["Assunto", "Quem procurar"],
        ["Acesso, senha e erro técnico", "CTIC-CESIT ou desenvolvedor"],
        ["Chaves, itens e devoluções", "Portaria"],
        ["Cursos, disciplinas, aulas e bolsistas", "Secretaria"],
        ["Reservas de sala", "Secretaria ou administrativo"],
    ], colWidths=[6 * cm, 9 * cm])
    tabela.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#dcefe6")),
        ("TEXTCOLOR", (0, 0), (-1, 0), colors.HexColor("#214d3c")),
        ("FONTNAME", (0, 0), (-1, 0), "Helvetica-Bold"),
        ("GRID", (0, 0), (-1, -1), 0.25, colors.HexColor("#cfdcd6")),
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("PADDING", (0, 0), (-1, -1), 7),
    ]))
    story.append(tabela)

    doc.build(story, onFirstPage=cabecalho_rodape, onLaterPages=cabecalho_rodape)
    return caminho


GUIAS = {
    "Desenvolvedor": {
        "subtitulo": "Perfil técnico com acesso máximo ao sistema.",
        "objetivo": "Ensinar como manter usuários, acompanhar logs, controlar advertências e resolver bloqueios sem perder rastreabilidade.",
        "secoes": [
            {
                "titulo": "Criar um usuário",
                "quando": "Use quando uma pessoa nova precisar acessar o sistema.",
                "passos": ["Abra Técnico > Usuários.", "Clique em Novo usuário ou Cadastro.", "Preencha nome, e-mail, senha inicial, perfil e situação.", "Clique em Salvar.", "Oriente a pessoa a trocar a senha no primeiro acesso, se necessário."],
                "cuidados": ["Escolha o perfil correto. O perfil define o que a pessoa pode fazer.", "Use e-mail único para cada usuário."],
            },
            {
                "titulo": "Editar ou apagar um usuário",
                "quando": "Use quando dados estiverem errados ou quando uma conta não deve mais acessar.",
                "passos": ["Abra Técnico > Usuários.", "Localize o usuário na lista.", "Altere nome, e-mail, perfil ou situação.", "Clique em Salvar.", "Para remover, clique em Excluir e confirme."],
                "cuidados": ["Se houver histórico, o sistema pode anonimizar em vez de apagar de verdade.", "Não apague contas sem autorização."],
            },
            {
                "titulo": "Controlar bloqueios por advertência",
                "quando": "Use quando alguém ficar bloqueado após devoluções irregulares.",
                "passos": ["Abra Técnico > Advertências.", "Confira o usuário na lista de bloqueios.", "Para mudar o prazo, ajuste a data no campo Fim e clique em Alterar.", "Para liberar imediatamente, clique em Zerar.", "Para limpar a lista, clique em Apagar no bloqueio desejado."],
                "cuidados": ["Apagar remove o bloqueio da lista.", "Limpar histórico remove advertências registradas; use com cuidado."],
            },
            {
                "titulo": "Consultar logs",
                "quando": "Use para verificar quem fez uma ação importante no sistema.",
                "passos": ["Abra Técnico > Logs.", "Leia usuário, módulo, ação, data e descrição.", "Use essas informações para investigar alterações.", "Se for necessário limpar logs antigos, clique em Limpar logs."],
                "cuidados": ["Logs ajudam a auditar o sistema. Não limpe sem necessidade."],
            },
        ],
    },
    "Administrativo": {
        "subtitulo": "Perfil de acompanhamento geral.",
        "objetivo": "Ensinar como consultar reservas, retiradas, histórico e disponibilidade de salas.",
        "secoes": [
            {
                "titulo": "Consultar reservas de sala",
                "quando": "Use para acompanhar solicitações e uso planejado dos ambientes.",
                "passos": ["Abra Administrativo > Reservas.", "Veja sala, usuário, data, horário e situação.", "Confira se há reservas pendentes, confirmadas ou encerradas."],
                "cuidados": ["Não confunda reserva pendente com reserva confirmada."],
            },
            {
                "titulo": "Consultar retiradas e histórico",
                "quando": "Use para acompanhar chaves e itens retirados ou devolvidos.",
                "passos": ["Abra Administrativo > Retiradas para ver movimentações em aberto.", "Abra Administrativo > Histórico para ver movimentações finalizadas.", "Confira usuário, sala ou item, tipo de movimentação e status."],
                "cuidados": ["A devolução é registrada pela portaria."],
            },
            {
                "titulo": "Ver disponibilidade de salas",
                "quando": "Use para saber se um ambiente está livre, reservado, aberto ou bloqueado.",
                "passos": ["Abra Administrativo > Disponibilidade.", "Informe nome, status, tipo, data ou horário se quiser filtrar.", "Clique em Filtrar.", "Clique em Ver sala para ver reservas, aulas e movimentações."],
                "cuidados": ["A consulta depende da data e horário selecionados."],
            },
        ],
    },
    "Secretaria": {
        "subtitulo": "Perfil responsável por cadastros acadêmicos, salas, permissões e bolsistas.",
        "objetivo": "Ensinar como cadastrar cursos, disciplinas, aulas, salas, itens, bolsistas e permissões de chave.",
        "secoes": [
            {
                "titulo": "Criar ou editar curso",
                "quando": "Use quando um curso novo precisar aparecer nas disciplinas.",
                "passos": ["Abra Secretaria > Matérias.", "Na área Novo curso, preencha nome, código e situação.", "Clique em Criar curso.", "Para editar, altere os campos na lista de cursos e clique em Salvar.", "Para remover, clique em Excluir."],
                "cuidados": ["Se o curso tiver disciplinas vinculadas, ele pode ser inativado em vez de apagado."],
            },
            {
                "titulo": "Criar ou editar disciplina",
                "quando": "Use para cadastrar matérias ofertadas em um curso.",
                "passos": ["Abra Secretaria > Matérias.", "Na área Nova disciplina, preencha disciplina, curso, período e professor.", "Adicione observação se necessário.", "Clique em Criar disciplina.", "Para editar, altere os campos na lista e clique em Salvar."],
                "cuidados": ["Vincule o professor correto para evitar erro nas aulas."],
            },
            {
                "titulo": "Cadastrar sala",
                "quando": "Use quando um novo ambiente precisar ser controlado pelo sistema.",
                "passos": ["Abra Secretaria > Salas.", "Preencha nome, código, bloco, capacidade, tipo e situação.", "Escreva uma descrição se ajudar na identificação.", "Clique em Cadastrar sala.", "Para editar, ajuste a sala na lista e clique em Salvar."],
                "cuidados": ["Use situação Bloqueada ou Manutenção quando a sala não puder ser usada."],
            },
            {
                "titulo": "Liberar chave autorizada",
                "quando": "Use quando um usuário puder retirar uma chave.",
                "passos": ["Abra Secretaria > Chaves.", "Escolha o usuário.", "Escolha a sala ou marque Acesso total.", "Informe início e expiração, se houver.", "Marque os dias permitidos.", "Escreva uma observação se necessário.", "Clique em Liberar chave."],
                "cuidados": ["Use Acesso total apenas quando a pessoa puder retirar qualquer chave.", "Use Nunca expirar somente para permissões permanentes."],
            },
            {
                "titulo": "Retirar chave ou item pela secretaria",
                "quando": "Use quando a secretaria precisar registrar uma retirada disponível.",
                "passos": ["Abra Secretaria > Retirada.", "Veja a lista de chaves disponíveis.", "Digite uma observação se necessário.", "Clique em Retirar chave.", "Para itens, vá até Itens disponíveis e clique em Retirar item."],
                "cuidados": ["A devolução não é feita nessa tela. A portaria registra a devolução."],
            },
            {
                "titulo": "Cadastrar bolsista",
                "quando": "Use para adicionar ou ajustar aluno bolsista.",
                "passos": ["Abra Secretaria > Bolsistas.", "Preencha nome, e-mail, senha, professor, situação e projeto.", "Clique em Salvar.", "Para editar, altere a linha do bolsista e clique em Salvar.", "Para apagar, clique em Apagar e confirme."],
                "cuidados": ["Se houver histórico, o sistema pode remover acesso e anonimizar os dados."],
            },
            {
                "titulo": "Cadastrar aula do semestre",
                "quando": "Use para registrar ocupações acadêmicas fixas.",
                "passos": ["Abra Secretaria > Aulas.", "Escolha professor e disciplina.", "Preencha período, sala, turma, dia, início e fim.", "Clique em Cadastrar aula.", "Para corrigir, edite a linha da aula e clique em Salvar."],
                "cuidados": ["Confira horário de início e fim antes de salvar."],
            },
        ],
    },
    "Portaria": {
        "subtitulo": "Perfil responsável por devoluções, visitantes e conferência de movimentações.",
        "objetivo": "Ensinar como acompanhar a fila de devoluções, registrar devolução de chave ou item e cadastrar visitantes.",
        "secoes": [
            {
                "titulo": "Usar o painel da portaria",
                "quando": "Use no início do turno para ver a situação geral.",
                "passos": ["Abra Portaria.", "Veja Salas monitoradas, Chaves pendentes e Itens pendentes.", "Confira Ambientes disponíveis para chave.", "Abra Fila de devoluções quando quiser ver o que está em aberto.", "Abra Movimentações recentes para conferir registros finalizados."],
                "cuidados": ["A fila só mostra retiradas que ainda não foram devolvidas."],
            },
            {
                "titulo": "Registrar devolução",
                "quando": "Use quando alguém devolver uma chave ou item.",
                "passos": ["Abra Portaria > Retiradas.", "Localize a retirada na lista.", "No campo Devolver, escolha Mesma pessoa, Pessoa não cadastrada ou outro usuário.", "Escreva observação se necessário.", "Clique em Registrar."],
                "cuidados": ["Se a chave for devolvida por pessoa diferente, o sistema registra advertência.", "Se a pessoa não estiver cadastrada, escolha Pessoa não cadastrada."],
            },
            {
                "titulo": "Cadastrar visitante",
                "quando": "Use quando uma pessoa externa precisar de acesso temporário.",
                "passos": ["Abra Portaria > Visitantes.", "Preencha nome, e-mail, senha inicial e situação.", "Clique em Salvar.", "Para editar, altere os dados na lista e clique em Salvar.", "Para remover, clique em Excluir."],
                "cuidados": ["Use senha inicial simples apenas para primeiro acesso e oriente a troca depois."],
            },
            {
                "titulo": "Consultar histórico",
                "quando": "Use para conferir movimentações antigas.",
                "passos": ["Abra Portaria > Histórico.", "Confira usuário, foto, sala ou item, tipo, data de retirada e status.", "Use a informação para tirar dúvidas sobre devoluções."],
                "cuidados": ["Histórico serve para conferência. Não substitui registro de devolução."],
            },
        ],
    },
    "Professor": {
        "subtitulo": "Perfil para consultar salas, solicitar reservas, orientar bolsistas e retirar recursos permitidos.",
        "objetivo": "Ensinar como consultar disponibilidade, solicitar sala e registrar retiradas autorizadas.",
        "secoes": [
            {
                "titulo": "Solicitar uma sala",
                "quando": "Use quando precisar reservar uma sala para uma data e horário.",
                "passos": ["Abra Professor > Disponibilidade.", "Escolha data e horário no filtro.", "Clique em Filtrar.", "Procure a sala desejada.", "Clique em Solicitar sala.", "Acompanhe a solicitação em Professor > Reservas."],
                "cuidados": ["O botão aparece apenas quando a sala está disponível para solicitação.", "A solicitação fica pendente até avaliação."],
            },
            {
                "titulo": "Ver detalhes de uma sala",
                "quando": "Use para saber se há reserva, aula ou movimentação relacionada à sala.",
                "passos": ["Abra Professor > Disponibilidade.", "Clique em Ver sala no cartão da sala.", "Leia os dados de reservas, aulas e movimentações.", "Volte para a disponibilidade se quiser escolher outra sala."],
                "cuidados": ["Confira sempre data e horário da consulta."],
            },
            {
                "titulo": "Retirar chave ou item",
                "quando": "Use quando receber uma chave ou item disponível e permitido.",
                "passos": ["Abra Professor > Retiradas.", "Confira a lista de chaves e itens disponíveis.", "Digite uma observação se necessário.", "Clique em Retirar chave ou Retirar item.", "Ao devolver, procure a portaria para finalizar."],
                "cuidados": ["Após advertências, o sistema pode bloquear novas retiradas.", "A devolução é registrada pela portaria."],
            },
            {
                "titulo": "Indicar bolsista",
                "quando": "Use para cadastrar orientando bolsista.",
                "passos": ["Abra Professor > Bolsistas.", "Preencha nome, e-mail, senha inicial e projeto.", "Clique em Salvar.", "Para editar, ajuste os dados na lista e clique em Salvar."],
                "cuidados": ["Confira o e-mail antes de salvar."],
            },
        ],
    },
    "Bolsista": {
        "subtitulo": "Perfil de aluno bolsista com retiradas autorizadas.",
        "objetivo": "Ensinar como consultar recursos disponíveis e registrar retirada quando permitido.",
        "secoes": [
            {
                "titulo": "Retirar chave ou item autorizado",
                "quando": "Use quando uma chave ou item estiver liberado para você.",
                "passos": ["Abra Bolsista > Retiradas.", "Veja as chaves e itens disponíveis.", "Digite observação se necessário.", "Clique em Retirar chave ou Retirar item.", "Faça a devolução na portaria."],
                "cuidados": ["Não retire recurso em nome de outra pessoa.", "Se a lista estiver vazia, não há recurso disponível para seu perfil."],
            },
            {
                "titulo": "Consultar sala de pesquisa",
                "quando": "Use para acompanhar informações do seu vínculo de pesquisa.",
                "passos": ["Abra Bolsista > Sala de Pesquisa.", "Leia as informações disponíveis.", "Se algo estiver incorreto, procure seu professor ou a secretaria."],
                "cuidados": ["Essa tela pode depender de cadastros feitos pela secretaria."],
            },
        ],
    },
    "Aluno": {
        "subtitulo": "Perfil de consulta de salas, sem solicitação de reserva.",
        "objetivo": "Ensinar como consultar a disponibilidade de salas sem alterar registros.",
        "secoes": [
            {
                "titulo": "Consultar salas",
                "quando": "Use para saber se uma sala está aberta, fechada, reservada, em manutenção ou bloqueada.",
                "passos": ["Abra Aluno > Consulta de Salas.", "Use Busca para procurar por nome, código ou bloco.", "Escolha status, tipo, data ou horário se quiser filtrar.", "Clique em Filtrar.", "Clique em Ver sala para mais detalhes."],
                "cuidados": ["Aluno não solicita sala pelo sistema.", "A consulta muda conforme data e horário."],
            },
        ],
    },
    "Visitante": {
        "subtitulo": "Perfil temporário para retirada de chave autorizada.",
        "objetivo": "Ensinar como usar uma conta temporária de visitante de forma correta.",
        "secoes": [
            {
                "titulo": "Retirar chave autorizada",
                "quando": "Use somente quando a portaria ou secretaria liberar seu acesso.",
                "passos": ["Entre com e-mail e senha fornecidos.", "Abra Visitante > Chave.", "Veja as chaves disponíveis para você.", "Digite observação se necessário.", "Clique em Retirar chave.", "Na devolução, procure a portaria."],
                "cuidados": ["A conta de visitante é limitada.", "Não entregue a chave para outra pessoa."],
            },
        ],
    },
    "Serviços Gerais": {
        "subtitulo": "Perfil operacional com acesso total às chaves disponíveis.",
        "objetivo": "Ensinar como registrar retiradas de chaves e itens no trabalho diário.",
        "secoes": [
            {
                "titulo": "Retirar chave",
                "quando": "Use quando precisar acessar qualquer ambiente disponível para serviço.",
                "passos": ["Abra Serviços Gerais > Retiradas.", "Veja a lista de chaves disponíveis.", "Digite observação se necessário.", "Clique em Retirar chave.", "Ao terminar, devolva a chave na portaria."],
                "cuidados": ["Serviços Gerais tem permissão total, mas somente para chaves disponíveis.", "Confira se escolheu a sala correta."],
            },
            {
                "titulo": "Retirar item",
                "quando": "Use quando precisar de equipamento disponível na portaria.",
                "passos": ["Abra Serviços Gerais > Retiradas.", "Vá até Itens disponíveis.", "Confira nome, categoria e quantidade.", "Digite observação se necessário.", "Clique em Retirar item.", "Devolva na portaria."],
                "cuidados": ["A retirada reduz a quantidade disponível até a devolução."],
            },
        ],
    },
}


for perfil, dados in GUIAS.items():
    montar_guia(perfil, dados["subtitulo"], dados["objetivo"], dados["secoes"])

print("Guias acessíveis gerados em output/pdf")
