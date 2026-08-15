document.addEventListener('DOMContentLoaded', () => {
  const layer = document.querySelector('[data-guide-layer]');
  const configElement = document.querySelector('[data-guide-config]');
  const startButtons = document.querySelectorAll('[data-guide-start]');

  if (!layer || !configElement || startButtons.length === 0) {
    return;
  }

  let config;
  try {
    config = JSON.parse(configElement.textContent || '{}');
  } catch (error) {
    return;
  }

  const overviewSteps = Array.isArray(config.steps) ? config.steps : [];
  const topics = Array.isArray(config.topics) ? config.topics : [];
  if (overviewSteps.length === 0 || topics.length === 0) {
    return;
  }

  const catalog = layer.querySelector('[data-guide-catalog]');
  const topicList = layer.querySelector('[data-guide-topic-list]');
  const topicSearch = layer.querySelector('[data-guide-search]');
  const topicCount = layer.querySelector('[data-guide-topic-count]');
  const emptyState = layer.querySelector('[data-guide-empty]');
  const closeButton = layer.querySelector('[data-guide-close]');
  const ring = layer.querySelector('[data-guide-ring]');
  const popover = layer.querySelector('[data-guide-popover]');
  const title = layer.querySelector('[data-guide-title]');
  const description = layer.querySelector('[data-guide-description]');
  const counter = layer.querySelector('[data-guide-counter]');
  const progress = layer.querySelector('[data-guide-progress]');
  const previousButton = layer.querySelector('[data-guide-previous]');
  const nextButton = layer.querySelector('[data-guide-next]');
  const skipButton = layer.querySelector('[data-guide-skip]');
  const nav = document.querySelector('[data-admin-nav]');
  const menuToggle = document.querySelector('[data-menu-toggle]');
  const pendingKey = `sgrp-guide-pending:${config.userId || 'usuario'}`;
  let tourSteps = [];
  let currentIndex = 0;
  let active = false;
  let touring = false;
  let currentTarget = null;
  let menuOpenedByGuide = false;

  const setMenuOpen = (open) => {
    if (!nav || !menuToggle) {
      return;
    }
    nav.classList.toggle('is-open', open);
    menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    menuToggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
  };

  const clearGuideMenus = () => {
    document.querySelectorAll('.nav-group.guide-group-open').forEach((group) => {
      group.classList.remove('is-open', 'guide-group-open');
    });
  };

  const clearRuntimeTargets = () => {
    document.querySelectorAll('[data-guide-runtime-target]').forEach((element) => {
      element.removeAttribute('data-guide-runtime-target');
    });
  };

  const findTarget = (selector) => {
    if (!selector) {
      return null;
    }
    try {
      return document.querySelector(selector);
    } catch (error) {
      return null;
    }
  };

  const prepareTarget = (target) => {
    clearGuideMenus();
    if (!target) {
      return;
    }

    const targetNav = target.closest('[data-admin-nav]');
    if (targetNav && window.getComputedStyle(targetNav).display === 'none') {
      setMenuOpen(true);
      menuOpenedByGuide = true;
    }

    const group = target.closest('.nav-group');
    if (group) {
      window.requestAnimationFrame(() => {
        if (!active || !touring) return;
        clearGuideMenus();
        group.classList.add('is-open', 'guide-group-open');
        target.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
      });
      return;
    }

    target.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'smooth' });
  };

  const centerRing = () => {
    if (!ring) return;
    currentTarget = null;
    ring.classList.add('guide-focus-ring--center');
    ring.style.left = '50%';
    ring.style.top = '50%';
    ring.style.width = '0';
    ring.style.height = '0';
  };

  const positionPopover = (target) => {
    if (!ring || !popover || popover.hidden) {
      return;
    }

    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    const margin = 12;

    if (!target || target.getClientRects().length === 0) {
      centerRing();
      popover.classList.add('guide-popover--center');
      popover.style.left = '50%';
      popover.style.top = '50%';
      return;
    }

    currentTarget = target;
    ring.classList.remove('guide-focus-ring--center');
    popover.classList.remove('guide-popover--center');

    const rect = target.getBoundingClientRect();
    const padding = 7;
    const left = Math.max(6, rect.left - padding);
    const top = Math.max(6, rect.top - padding);
    const width = Math.min(viewportWidth - left - 6, rect.width + padding * 2);
    const height = Math.min(viewportHeight - top - 6, rect.height + padding * 2);

    ring.style.left = `${left}px`;
    ring.style.top = `${top}px`;
    ring.style.width = `${Math.max(width, 20)}px`;
    ring.style.height = `${Math.max(height, 20)}px`;

    const popoverRect = popover.getBoundingClientRect();
    const popoverWidth = Math.min(popoverRect.width || 390, viewportWidth - margin * 2);
    let popoverLeft = rect.left + (rect.width / 2) - (popoverWidth / 2);
    popoverLeft = Math.max(margin, Math.min(popoverLeft, viewportWidth - popoverWidth - margin));

    const below = rect.bottom + 16;
    const above = rect.top - popoverRect.height - 16;
    const popoverTop = below + popoverRect.height <= viewportHeight - margin
      ? below
      : Math.max(margin, above);

    popover.style.left = `${popoverLeft}px`;
    popover.style.top = `${popoverTop}px`;
  };

  const renderTour = () => {
    const step = tourSteps[currentIndex];
    if (!step) {
      return;
    }

    if (title) title.textContent = step.title || 'Tutorial';
    if (description) description.textContent = step.description || '';
    if (counter) counter.textContent = `Passo ${currentIndex + 1} de ${tourSteps.length}`;
    if (progress) progress.style.width = `${((currentIndex + 1) / tourSteps.length) * 100}%`;
    if (previousButton) previousButton.hidden = currentIndex === 0;
    if (nextButton) nextButton.textContent = currentIndex === tourSteps.length - 1 ? 'Concluir' : 'Próximo';

    const target = findTarget(step.target);
    prepareTarget(target);
    window.requestAnimationFrame(() => {
      window.requestAnimationFrame(() => positionPopover(target));
    });
  };

  const showLayer = () => {
    active = true;
    layer.hidden = false;
    layer.setAttribute('aria-hidden', 'false');
    document.body.classList.add('guide-is-active');
  };

  const closeHelp = () => {
    if (!active) return;
    active = false;
    touring = false;
    currentTarget = null;
    tourSteps = [];
    layer.hidden = true;
    layer.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('guide-is-active');
    clearGuideMenus();
    clearRuntimeTargets();
    if (menuOpenedByGuide) {
      setMenuOpen(false);
      menuOpenedByGuide = false;
    }
  };

  const startTour = (steps) => {
    if (!Array.isArray(steps) || steps.length === 0) return;
    showLayer();
    touring = true;
    tourSteps = steps;
    currentIndex = 0;
    menuOpenedByGuide = false;
    if (catalog) catalog.hidden = true;
    if (popover) popover.hidden = false;
    renderTour();
    window.requestAnimationFrame(() => popover?.focus());
  };

  const savePendingTopic = (topic) => {
    try {
      window.sessionStorage.setItem(pendingKey, JSON.stringify(topic));
    } catch (error) {
      // Se o armazenamento estiver bloqueado, o tutorial ainda funciona na página atual.
    }
  };

  const readPendingTopic = () => {
    try {
      const value = window.sessionStorage.getItem(pendingKey);
      return value ? JSON.parse(value) : null;
    } catch (error) {
      return null;
    }
  };

  const removePendingTopic = () => {
    try {
      window.sessionStorage.removeItem(pendingKey);
    } catch (error) {
      // Sem ação necessária.
    }
  };

  const pagePath = (url) => {
    try {
      return new URL(url, window.location.origin).pathname.replace(/\/+$/, '') || '/';
    } catch (error) {
      return '';
    }
  };

  const runtimeSelector = (element, index) => {
    const id = `alvo-${index}`;
    element.setAttribute('data-guide-runtime-target', id);
    return `[data-guide-runtime-target="${id}"]`;
  };

  const buildPageTutorial = (topic) => {
    clearRuntimeTargets();
    const main = document.querySelector('main.page-shell');
    if (!main) return [];

    const steps = [];
    const used = new Set();
    const usedForms = new Set();
    const usedTables = new Set();
    const usedActions = new Set();
    const cleanText = (value) => String(value || '').replace(/\s+/g, ' ').trim();
    const isVisible = (element) => Boolean(
      element
      && element.getClientRects().length > 0
      && window.getComputedStyle(element).visibility !== 'hidden'
    );
    const unique = (values) => [...new Set(values.map(cleanText).filter(Boolean))];
    const humanize = (value) => cleanText(value)
      .replace(/[\-_]+/g, ' ')
      .replace(/\b\w/g, (letter) => letter.toUpperCase());
    const directLabelText = (control) => {
      const label = control.labels?.[0] || control.closest('label');
      if (!label) return '';
      return cleanText([...label.childNodes]
        .filter((node) => node.nodeType === Node.TEXT_NODE)
        .map((node) => node.textContent)
        .join(' '));
    };
    const fieldNames = (form) => unique([...form.querySelectorAll('input:not([type="hidden"]), select, textarea')]
      .filter(isVisible)
      .map((control) => directLabelText(control)
        || control.getAttribute('aria-label')
        || control.getAttribute('placeholder')
        || humanize(control.getAttribute('name'))));
    const buttonText = (element) => cleanText(
      element.getAttribute('aria-label')
      || element.getAttribute('title')
      || element.textContent
      || element.value
    );
    const formActions = (form) => unique([...form.querySelectorAll('button, input[type="submit"], a.button')]
      .filter(isVisible)
      .map(buttonText));
    const nearbyHeading = (element) => {
      const section = element.closest('.resource-section, section, article');
      const sectionHeading = section?.querySelector('h2, h3');
      if (sectionHeading && !element.contains(sectionHeading)) return cleanText(sectionHeading.textContent);
      let sibling = element.previousElementSibling;
      while (sibling) {
        if (sibling.matches('h1, h2, h3, .section-header')) {
          return cleanText(sibling.querySelector?.('h1, h2, h3')?.textContent || sibling.textContent);
        }
        sibling = sibling.previousElementSibling;
      }
      return '';
    };
    const actionDescription = (label) => {
      const normalized = cleanText(label).toLocaleLowerCase('pt-BR');
      if (/excluir|apagar|revogar|limpar|recusar/.test(normalized)) {
        return 'Esta ação remove, revoga ou recusa o registro. Confira os dados e leia a confirmação antes de continuar.';
      }
      if (/salvar|atualizar|alterar|confirmar|registrar/.test(normalized)) {
        return 'Use esta ação depois de revisar os campos para gravar a alteração no sistema.';
      }
      if (/retirar/.test(normalized)) {
        return 'Use esta ação para registrar a retirada. Quando solicitado, informe a observação e confirme sua senha.';
      }
      if (/devolver/.test(normalized)) {
        return 'Use esta ação para confirmar que a chave ou o item foi devolvido e encerrar a movimentação.';
      }
      if (/aprovar/.test(normalized)) {
        return 'Use esta ação após conferir os dados para aprovar a solicitação apresentada.';
      }
      if (/filtrar|buscar|pesquisar/.test(normalized)) {
        return 'Use esta ação para aplicar os critérios informados e atualizar os resultados exibidos.';
      }
      if (/editar/.test(normalized)) {
        return 'Abra esta opção para alterar os dados do registro selecionado.';
      }
      if (/ver|detalhe|consultar/.test(normalized)) {
        return 'Abra esta opção para consultar as informações completas do registro selecionado.';
      }
      if (/solicitar/.test(normalized)) {
        return 'Use esta ação para iniciar ou enviar uma nova solicitação.';
      }
      if (/gerar/.test(normalized)) {
        return 'Use esta ação para o sistema gerar automaticamente a informação necessária.';
      }
      if (/copiar/.test(normalized)) {
        return 'Use esta ação para copiar a informação e poder compartilhá-la com segurança.';
      }
      return `Use “${label}” para executar esta função na página.`;
    };
    const addStep = (element, stepTitle, stepDescription) => {
      if (!element || !isVisible(element) || used.has(element)) return;
      used.add(element);
      steps.push({
        target: runtimeSelector(element, steps.length + 1),
        title: stepTitle,
        description: stepDescription,
      });
    };

    const functions = Array.isArray(topic.functions) ? topic.functions.filter(Boolean) : [];
    const introDescription = functions.length > 0
      ? `${topic.description} Nesta página você pode: ${functions.join('; ')}.`
      : topic.description;
    const intro = main.querySelector('.section-header') || main.querySelector('h1') || main;
    addStep(intro, topic.title, introDescription);

    const candidates = main.querySelectorAll([
      '.section-header',
      'h1',
      '.resource-section',
      '.dashboard-grid > a',
      '.dashboard-grid > article',
      '.room-grid',
      'form',
      '.table-wrap',
      'table',
      'article.card',
      'button',
      'a.button',
    ].join(', '));

    candidates.forEach((element) => {
      if (!isVisible(element) || element === intro) return;

      if (element.matches('h1') && element.closest('.section-header')) return;

      if (element.matches('.section-header, h1')) {
        addStep(element, cleanText(element.querySelector?.('h1')?.textContent || element.textContent), 'Esta é a área principal da página e resume o objetivo da tarefa escolhida.');
        return;
      }

      if (element.matches('.resource-section')) {
        const heading = cleanText(element.querySelector('h2, h3')?.textContent);
        if (heading) {
          addStep(element, heading, `Nesta seção você consulta ou executa as funções relacionadas a ${heading.toLocaleLowerCase('pt-BR')}.`);
        }
        return;
      }

      if (element.matches('.dashboard-grid > a, .dashboard-grid > article')) {
        const heading = cleanText(element.querySelector('h2, h3, strong')?.textContent) || 'Atalho da página';
        const description = cleanText(element.querySelector('p')?.textContent) || 'Use este cartão para consultar as informações ou abrir a função indicada.';
        addStep(element, heading, description);
        return;
      }

      if (element.matches('.room-grid')) {
        addStep(element, 'Resultados das salas', 'Cada cartão mostra a sala, o tipo, a capacidade e a situação no horário pesquisado. Use as ações do cartão para ver detalhes ou solicitar a sala, quando permitido.');
        return;
      }

      if (element.matches('form')) {
        const fields = fieldNames(element);
        const actions = formActions(element);
        const method = (element.getAttribute('method') || 'get').toLocaleLowerCase('pt-BR');
        const signature = `${method}|${element.getAttribute('action') || ''}|${fields.join('|')}|${actions.join('|')}`;
        if (usedForms.has(signature)) return;
        usedForms.add(signature);

        if (fields.length > 0) {
          const isFilter = method === 'get' || actions.some((label) => /filtrar|buscar|pesquisar/i.test(label));
          const heading = nearbyHeading(element);
          const stepTitle = isFilter ? 'Pesquisar e filtrar' : (heading || actions[0] || 'Preencher informações');
          const fieldsText = fields.slice(0, 8).join(', ');
          const remaining = fields.length > 8 ? ` e mais ${fields.length - 8} campo(s)` : '';
          const actionText = actions.length > 0 ? ` Depois, use ${actions.map((label) => `“${label}”`).join(' ou ')}.` : '';
          addStep(element, stepTitle, `Preencha ou confira: ${fieldsText}${remaining}.${actionText}`);
        }
        return;
      }

      if (element.matches('.table-wrap, table')) {
        if (element.matches('table') && element.closest('.table-wrap')) return;
        const table = element.matches('table') ? element : element.querySelector('table');
        if (!table) return;
        const columns = unique([...table.querySelectorAll('thead th')].map((cell) => cell.textContent));
        const signature = columns.join('|') || nearbyHeading(element) || 'registros';
        if (usedTables.has(signature)) return;
        usedTables.add(signature);
        const heading = nearbyHeading(element) || 'Registros e resultados';
        const columnsText = columns.length > 0 ? ` As colunas mostram: ${columns.join(', ')}.` : '';
        addStep(element, heading, `Aqui você acompanha os registros disponíveis, seus dados e sua situação.${columnsText}`);
        return;
      }

      if (element.matches('article.card') && !element.closest('.dashboard-grid')) {
        const heading = cleanText(element.querySelector('h2, h3, strong')?.textContent);
        if (heading) {
          addStep(element, heading, cleanText(element.querySelector('p')?.textContent) || 'Este cartão reúne informações importantes para a tarefa atual.');
        }
        return;
      }

      if (element.matches('button, a.button')) {
        if (element.closest('[data-guide-layer]')) return;
        const label = buttonText(element);
        if (!label) return;
        const ownerForm = element.closest('form');
        const destination = ownerForm?.getAttribute('action') || element.getAttribute('href') || '';
        const signature = `${label.toLocaleLowerCase('pt-BR')}|${pagePath(destination)}`;
        if (usedActions.has(signature)) return;
        usedActions.add(signature);
        addStep(element, label, actionDescription(label));
      }
    });

    if (steps.length === 1) {
      addStep(
        main.querySelector('.dashboard-grid, .room-grid, .card, .table-wrap') || main,
        'Informações disponíveis',
        'Use esta área para consultar os dados e atalhos relacionados à tarefa escolhida.'
      );
    }

    steps.push({
      target: null,
      title: 'Tutorial concluído',
      description: `Agora você já pode usar a opção “${topic.title}”. O botão Ajuda continua disponível para iniciar outro tutorial.`,
    });
    return steps;
  };

  const startTopic = (topic) => {
    if (topic.type === 'overview') {
      startTour(overviewSteps);
      return;
    }

    if (!topic.url) return;
    const destinationPath = pagePath(topic.url);
    const currentPath = pagePath(window.location.href);
    if (destinationPath === currentPath) {
      startTour(buildPageTutorial(topic));
      return;
    }

    savePendingTopic(topic);
    window.location.assign(topic.url);
  };

  const normalizeSearch = (value) => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase('pt-BR')
    .trim();

  const renderTopics = (query = '') => {
    if (!topicList) return;
    topicList.replaceChildren();
    const normalizedQuery = normalizeSearch(query);
    const visibleTopics = topics
      .filter((topic) => {
        const searchable = [
          topic.title,
          topic.description,
          topic.category,
          ...(Array.isArray(topic.functions) ? topic.functions : []),
        ].join(' ');
        return !normalizedQuery || normalizeSearch(searchable).includes(normalizedQuery);
      })
      .sort((left, right) => Number(Boolean(right.current)) - Number(Boolean(left.current)));

    if (topicCount) {
      topicCount.textContent = normalizedQuery
        ? `${visibleTopics.length} de ${topics.length} tutoriais`
        : `${topics.length} tutoriais disponíveis`;
    }
    if (emptyState) emptyState.hidden = visibleTopics.length > 0;

    const groups = new Map();
    visibleTopics.forEach((topic) => {
      const category = topic.current ? 'Nesta página' : (topic.category || 'Outras funções');
      if (!groups.has(category)) groups.set(category, []);
      groups.get(category).push(topic);
    });

    groups.forEach((groupTopics, category) => {
      const group = document.createElement('section');
      const groupHeader = document.createElement('div');
      const groupTitle = document.createElement('h3');
      const groupCount = document.createElement('span');
      const groupGrid = document.createElement('div');

      group.className = 'guide-topic-group';
      groupHeader.className = 'guide-topic-group__header';
      groupTitle.textContent = category;
      groupCount.textContent = `${groupTopics.length} ${groupTopics.length === 1 ? 'tutorial' : 'tutoriais'}`;
      groupGrid.className = 'guide-topic-group__grid';
      groupHeader.append(groupTitle, groupCount);

      groupTopics.forEach((topic) => {
        const button = document.createElement('button');
        const heading = document.createElement('span');
        const topicTitle = document.createElement('strong');
        const topicDescription = document.createElement('span');
        const functionList = document.createElement('ul');
        const action = document.createElement('span');
        const actionText = document.createElement('span');
        const actionArrow = document.createElement('span');

        button.type = 'button';
        button.className = 'guide-topic';
        if (topic.current) button.classList.add('guide-topic--current');
        heading.className = 'guide-topic__heading';
        topicTitle.textContent = topic.title || 'Tutorial';
        topicDescription.textContent = topic.description || '';
        topicDescription.className = 'guide-topic__description';
        functionList.className = 'guide-topic__functions';
        const functions = Array.isArray(topic.functions) ? topic.functions : [];
        functions.forEach((functionName) => {
          const item = document.createElement('li');
          item.textContent = functionName;
          functionList.appendChild(item);
        });
        action.className = 'guide-topic__action';
        actionText.textContent = topic.current ? 'Aprender esta página' : 'Iniciar tutorial';
        actionArrow.textContent = '→';
        actionArrow.setAttribute('aria-hidden', 'true');
        action.append(actionText, actionArrow);
        heading.appendChild(topicTitle);
        if (topic.current) {
          const badge = document.createElement('span');
          badge.className = 'guide-topic__badge';
          badge.textContent = 'Você está aqui';
          heading.appendChild(badge);
        }
        button.append(heading, topicDescription);
        if (functions.length > 0) button.appendChild(functionList);
        button.appendChild(action);
        button.addEventListener('click', () => startTopic(topic));
        groupGrid.appendChild(button);
      });

      group.append(groupHeader, groupGrid);
      topicList.appendChild(group);
    });
  };

  const openCatalog = () => {
    showLayer();
    touring = false;
    clearGuideMenus();
    clearRuntimeTargets();
    centerRing();
    if (topicSearch) topicSearch.value = '';
    renderTopics('');
    if (popover) popover.hidden = true;
    if (catalog) catalog.hidden = false;
    window.requestAnimationFrame(() => {
      if (topicSearch) topicSearch.focus();
      else catalog?.focus();
    });
  };

  startButtons.forEach((button) => button.addEventListener('click', openCatalog));
  topicSearch?.addEventListener('input', () => renderTopics(topicSearch.value));
  closeButton?.addEventListener('click', closeHelp);
  skipButton?.addEventListener('click', closeHelp);
  previousButton?.addEventListener('click', () => {
    if (touring && currentIndex > 0) {
      currentIndex -= 1;
      renderTour();
    }
  });
  nextButton?.addEventListener('click', () => {
    if (!touring) return;
    if (currentIndex >= tourSteps.length - 1) {
      closeHelp();
      return;
    }
    currentIndex += 1;
    renderTour();
  });

  document.addEventListener('keydown', (event) => {
    if (!active) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      closeHelp();
    }
    if (touring && event.key === 'ArrowRight') nextButton?.click();
    if (touring && event.key === 'ArrowLeft' && currentIndex > 0) previousButton?.click();
  });

  window.addEventListener('resize', () => {
    if (active && touring) positionPopover(currentTarget);
  });

  const pendingTopic = readPendingTopic();
  if (pendingTopic && pendingTopic.type === 'page') {
    const expectedPath = pagePath(pendingTopic.url || '');
    if (expectedPath === pagePath(window.location.href)) {
      removePendingTopic();
      window.setTimeout(() => startTour(buildPageTutorial(pendingTopic)), 450);
    }
  }
});
