/**
 * GUIA DE MANUTENCAO: Este arquivo coordena os comportamentos da interface usando atributos data-* como contrato com o HTML.
 * Ponto de atencao: preserve os seletores usados pelas views. Renomear um data-* sem procurar consumidores e como trocar a fechadura e culpar a chave.
 */
document.addEventListener('DOMContentLoaded', () => {
  const browserAlerts = [];
  const queueBrowserAlert = (message) => {
    const text = String(message || '').trim();
    if (text !== '') {
      browserAlerts.push(text);
    }
  };

  document.querySelectorAll('[data-browser-alert]').forEach((item) => {
    queueBrowserAlert(item.getAttribute('data-browser-alert-message') || item.getAttribute('data-browser-alert'));
    item.remove();
  });

  document.querySelectorAll('[data-app-notification]').forEach((item) => {
    const title = item.getAttribute('data-app-notification-title') || '';
    const message = item.getAttribute('data-app-notification-message') || '';
    queueBrowserAlert([title, message].filter(Boolean).join(': '));
    item.remove();
  });

  document.querySelectorAll('.flash, .app-notification').forEach((item) => {
    queueBrowserAlert(item.textContent);
    item.remove();
  });

  browserAlerts.forEach((message) => window.alert(message));

  const withdrawalCenter = document.querySelector('[data-withdrawal-alert-center]');
  if (withdrawalCenter) {
    const listUrl = withdrawalCenter.getAttribute('data-list-url');
    const itemsContainer = withdrawalCenter.querySelector('[data-withdrawal-alert-items]');
    const countNode = withdrawalCenter.querySelector('[data-withdrawal-alert-count]');
    const centerToggle = withdrawalCenter.querySelector('[data-withdrawal-center-toggle]');
    const groupFilter = withdrawalCenter.querySelector('[data-withdrawal-group-filter]');
    let lastSignature = '';
    let latestPayload = null;

    const storageGet = (key, fallback = '') => {
      try { return window.sessionStorage.getItem(key) ?? fallback; } catch (_) { return fallback; }
    };
    const storageSet = (key, value) => {
      try { window.sessionStorage.setItem(key, value); } catch (_) { /* armazenamento indisponível */ }
    };
    const minimizedIds = () => {
      try {
        const stored = JSON.parse(storageGet('sgrp-retiradas-minimizadas', '[]'));
        return new Set(Array.isArray(stored) ? stored.map(String) : []);
      } catch (_) {
        return new Set();
      }
    };
    const saveMinimizedIds = (ids) => storageSet('sgrp-retiradas-minimizadas', JSON.stringify(Array.from(ids)));
    const formatDateTime = (value) => {
      const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/);
      return match ? `${match[3]}/${match[2]}/${match[1]} às ${match[4]}:${match[5]}` : 'horário não informado';
    };
    const appendText = (parent, tag, className, value) => {
      const node = document.createElement(tag);
      if (className) node.className = className;
      node.textContent = value;
      parent.appendChild(node);
      return node;
    };
    const hiddenInput = (name, value) => {
      const input = document.createElement('input');
      input.type = 'hidden'; input.name = name; input.value = value;
      if (name === '_csrf') input.dataset.csrfToken = '';
      return input;
    };

    const renderWithdrawalAlerts = (payload) => {
      const requests = Array.isArray(payload.solicitacoes) ? payload.solicitacoes : [];
      latestPayload = payload;
      const selectedGroup = groupFilter?.value || '';
      if (groupFilter) {
        const existingGroups = new Set(Array.from(groupFilter.options).slice(1).map((option) => option.value));
        const availableGroups = Array.from(new Set(requests.map((item) => item.contexto?.perfil_nome || 'Perfil não informado'))).sort((a, b) => a.localeCompare(b, 'pt-BR'));
        if (availableGroups.some((group) => !existingGroups.has(group)) || existingGroups.size !== availableGroups.length) {
          groupFilter.replaceChildren(new Option('Todos os grupos', ''));
          availableGroups.forEach((group) => groupFilter.appendChild(new Option(group, group)));
          groupFilter.value = availableGroups.includes(selectedGroup) ? selectedGroup : '';
        }
      }
      const activeGroup = groupFilter?.value || '';
      const filteredRequests = activeGroup
        ? requests.filter((item) => (item.contexto?.perfil_nome || 'Perfil não informado') === activeGroup)
        : requests;
      // Evita reconstruir o DOM quando o polling devolve o mesmo estado. Inclua
      // aqui qualquer campo novo que altere o card; esquecer isso cria o classico
      // "o backend mudou, mas a tela decidiu viver no passado".
      const signature = JSON.stringify([activeGroup, requests.map((item) => [item.id, item.contexto?.situacao, item.contexto?.tentativas, item.contexto?.expira_em, item.contexto?.grupo_usuario, item.contexto?.codigo_exibicao])]);
      if (signature === lastSignature) return;
      lastSignature = signature;
      itemsContainer.replaceChildren();
      countNode.textContent = activeGroup ? `${filteredRequests.length} de ${requests.length}` : String(requests.length);
      withdrawalCenter.hidden = requests.length === 0;
      const minimized = minimizedIds();

      if (filteredRequests.length === 0 && requests.length > 0) {
        appendText(itemsContainer, 'p', 'withdrawal-alert-center__empty', 'Nenhuma solicitação pendente neste grupo.');
      }

      filteredRequests.forEach((request) => {
        const context = request.contexto || {};
        const card = document.createElement('article');
        card.className = 'withdrawal-alert';
        card.dataset.withdrawalAlertId = String(request.id);
        if (minimized.has(String(request.id))) card.classList.add('is-minimized');

        const header = document.createElement('header');
        header.className = 'withdrawal-alert__header';
        const identity = document.createElement('div');
        appendText(identity, 'span', 'withdrawal-alert__eyebrow', 'Solicitação #' + request.id);
        appendText(identity, 'strong', '', context.usuario_nome || 'Usuário não identificado');
        appendText(identity, 'span', 'withdrawal-alert__profile', `${context.grupo_usuario || 'Outros'} · ${context.perfil_nome || 'Perfil não informado'}`);
        header.appendChild(identity);
        const minimize = appendText(header, 'button', 'withdrawal-alert__minimize', card.classList.contains('is-minimized') ? 'Expandir' : 'Minimizar');
        minimize.type = 'button';
        minimize.addEventListener('click', () => {
          card.classList.toggle('is-minimized');
          const ids = minimizedIds();
          if (card.classList.contains('is-minimized')) ids.add(String(request.id)); else ids.delete(String(request.id));
          saveMinimizedIds(ids);
          minimize.textContent = card.classList.contains('is-minimized') ? 'Expandir' : 'Minimizar';
        });
        card.appendChild(header);

        const body = document.createElement('div');
        body.className = 'withdrawal-alert__body';
        const details = document.createElement('dl');
        [['Chave', context.sala_nome || 'Não informada'], ['Solicitado em', formatDateTime(request.criado_em)], ['Solicitação válida até', formatDateTime(context.expira_em)]].forEach(([term, value]) => {
          appendText(details, 'dt', '', term); appendText(details, 'dd', '', value);
        });
        body.appendChild(details);
        if (context.exige_codigo_temporario && context.codigo_exibicao) {
          const codeBox = document.createElement('div');
          codeBox.className = 'withdrawal-alert__code';
          appendText(codeBox, 'span', '', 'Compare com a senha exibida na tela do usuário');
          appendText(codeBox, 'strong', '', context.codigo_exibicao);
          body.appendChild(codeBox);
        }
        if (context.observacao) appendText(body, 'p', 'withdrawal-alert__note', 'Observação: ' + context.observacao);
        if (context.situacao === 'codigo_expirado') appendText(body, 'p', 'withdrawal-alert__warning', 'Solicitação expirada. Peça ao usuário para gerar outra; este alerta continuará aberto.');
        if ((context.tentativas || 0) > 0) appendText(body, 'p', 'withdrawal-alert__warning', `Tentativas incorretas: ${context.tentativas}.`);

        const actions = document.createElement('div');
        actions.className = 'withdrawal-alert__actions';
        const confirmForm = document.createElement('form');
        confirmForm.method = 'post'; confirmForm.action = payload.confirmar_url; confirmForm.className = 'withdrawal-alert__confirm';
        confirmForm.append(hiddenInput('_csrf', payload.csrf), hiddenInput('notificacao_id', request.id));
        if (context.codigo_legado) {
          const codeInput = document.createElement('input');
          codeInput.type = 'text'; codeInput.name = 'codigo_temporario'; codeInput.placeholder = 'Código antigo de 6 dígitos';
          codeInput.inputMode = 'numeric'; codeInput.pattern = '[0-9]{6}'; codeInput.maxLength = 6; codeInput.autocomplete = 'one-time-code'; codeInput.required = true;
          confirmForm.appendChild(codeInput);
        }
        const confirmButton = appendText(confirmForm, 'button', 'button', 'Aceitar e entregar'); confirmButton.type = 'submit';
        actions.appendChild(confirmForm);

        const closeForm = document.createElement('form');
        closeForm.method = 'post'; closeForm.action = payload.fechar_url; closeForm.className = 'withdrawal-alert__close-form';
        closeForm.append(hiddenInput('_csrf', payload.csrf), hiddenInput('notificacao_id', request.id));
        const closeButton = appendText(closeForm, 'button', 'button button--danger', 'Recusar'); closeButton.type = 'submit';
        closeForm.addEventListener('submit', (event) => {
          if (!window.confirm('Recusar esta solicitação sem entregar a chave?')) event.preventDefault();
        });
        actions.appendChild(closeForm);
        body.appendChild(actions);
        card.appendChild(body);
        itemsContainer.appendChild(card);
      });
    };

    const refreshWithdrawalAlerts = async () => {
      try {
        const response = await window.fetch(listUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) return;
        renderWithdrawalAlerts(await response.json());
      } catch (_) { /* mantém os alertas já carregados */ }
    };

    const centerCollapsed = storageGet('sgrp-retiradas-painel-minimizado') === '1';
    withdrawalCenter.classList.toggle('is-collapsed', centerCollapsed);
    centerToggle.textContent = centerCollapsed ? 'Expandir painel' : 'Minimizar painel';
    centerToggle.setAttribute('aria-expanded', String(!centerCollapsed));
    centerToggle.addEventListener('click', () => {
      withdrawalCenter.classList.toggle('is-collapsed');
      const collapsed = withdrawalCenter.classList.contains('is-collapsed');
      storageSet('sgrp-retiradas-painel-minimizado', collapsed ? '1' : '0');
      centerToggle.textContent = collapsed ? 'Expandir painel' : 'Minimizar painel';
      centerToggle.setAttribute('aria-expanded', String(!collapsed));
    });
    groupFilter?.addEventListener('change', () => {
      lastSignature = '';
      if (latestPayload) renderWithdrawalAlerts(latestPayload);
    });

    refreshWithdrawalAlerts();
    window.setInterval(refreshWithdrawalAlerts, 8000);
  }

  document.querySelectorAll('[data-print-page]').forEach((button) => {
    button.addEventListener('click', () => window.print());
  });

  const normalizeAutocompleteText = (value) => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();

  document.querySelectorAll('[data-room-autocomplete]').forEach((autocomplete) => {
    const input = autocomplete.querySelector('[data-room-autocomplete-input]');
    const list = autocomplete.querySelector('[data-room-autocomplete-list]');
    const options = Array.from(autocomplete.querySelectorAll('[data-room-autocomplete-option]'));
    const emptyMessage = autocomplete.querySelector('[data-room-autocomplete-empty]');
    let activeIndex = -1;

    if (!input || !list) {
      return;
    }

    const visibleOptions = () => options.filter((option) => !option.hidden);
    const setActiveOption = (index) => {
      const visible = visibleOptions();
      activeIndex = index >= 0 && visible.length
        ? (index + visible.length) % visible.length
        : -1;

      options.forEach((option) => {
        option.classList.remove('is-active');
        option.setAttribute('aria-selected', 'false');
      });

      if (activeIndex >= 0) {
        visible[activeIndex].classList.add('is-active');
        visible[activeIndex].setAttribute('aria-selected', 'true');
        visible[activeIndex].scrollIntoView({ block: 'nearest' });
      }
    };

    const filterOptions = () => {
      const query = normalizeAutocompleteText(input.value);
      let matches = 0;

      options.forEach((option) => {
        const matchesQuery = query === ''
          || normalizeAutocompleteText(option.dataset.value).includes(query);
        option.hidden = !matchesQuery;
        if (matchesQuery) matches += 1;
      });

      if (emptyMessage) {
        emptyMessage.hidden = matches > 0;
      }
      setActiveOption(-1);
    };

    const openList = () => {
      filterOptions();
      list.hidden = false;
      input.setAttribute('aria-expanded', 'true');
    };

    const closeList = () => {
      list.hidden = true;
      input.setAttribute('aria-expanded', 'false');
      setActiveOption(-1);
    };

    const chooseOption = (option) => {
      input.value = option.dataset.value || option.textContent.trim();
      closeList();
      input.focus();
    };

    input.addEventListener('focus', openList);
    input.addEventListener('click', openList);
    input.addEventListener('input', openList);
    input.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeList();
        return;
      }

      if (!['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) {
        return;
      }

      if (list.hidden) {
        openList();
      }

      const visible = visibleOptions();
      if (!visible.length) {
        return;
      }

      if (event.key === 'ArrowDown') {
        event.preventDefault();
        setActiveOption(activeIndex + 1);
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        setActiveOption(activeIndex <= 0 ? visible.length - 1 : activeIndex - 1);
      } else if (event.key === 'Enter' && activeIndex >= 0) {
        event.preventDefault();
        chooseOption(visible[activeIndex]);
      }
    });

    options.forEach((option) => {
      option.addEventListener('mousedown', (event) => event.preventDefault());
      option.addEventListener('click', () => chooseOption(option));
    });

    autocomplete.addEventListener('focusout', () => {
      window.setTimeout(() => {
        if (!autocomplete.contains(document.activeElement)) {
          closeList();
        }
      }, 0);
    });

    document.addEventListener('pointerdown', (event) => {
      if (!autocomplete.contains(event.target)) {
        closeList();
      }
    });
  });

  const padDatePart = (value) => String(value).padStart(2, '0');
  const isoDateToBr = (value, kind) => {
    const text = String(value || '').trim();
    if (text === '') {
      return '';
    }
    if (/^\d{2}\/\d{2}\/\d{4}(?: \d{2}:\d{2})?$/.test(text)) {
      return text;
    }

    const match = text.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::\d{2})?)?$/);
    if (!match) {
      return text;
    }

    const [, year, month, day, hour = '00', minute = '00'] = match;
    return kind === 'datetime'
      ? `${day}/${month}/${year} ${hour}:${minute}`
      : `${day}/${month}/${year}`;
  };

  const maskBrDate = (value, kind) => {
    const text = String(value || '').trim();
    if (/^\d{4}-\d{2}-\d{2}/.test(text)) {
      return isoDateToBr(text, kind);
    }

    const limit = kind === 'datetime' ? 12 : 8;
    const digits = text.replace(/\D/g, '').slice(0, limit);
    let formatted = digits.slice(0, 2);
    if (digits.length > 2) formatted += `/${digits.slice(2, 4)}`;
    if (digits.length > 4) formatted += `/${digits.slice(4, 8)}`;
    if (kind === 'datetime' && digits.length > 8) formatted += ` ${digits.slice(8, 10)}`;
    if (kind === 'datetime' && digits.length > 10) formatted += `:${digits.slice(10, 12)}`;
    return formatted;
  };

  const brDateToIso = (value, kind) => {
    const text = String(value || '').trim();
    const expression = kind === 'datetime'
      ? /^(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2})$/
      : /^(\d{2})\/(\d{2})\/(\d{4})$/;
    const match = text.match(expression);
    if (!match) {
      return null;
    }

    const [, dayText, monthText, yearText, hourText = '00', minuteText = '00'] = match;
    const day = Number(dayText);
    const month = Number(monthText);
    const year = Number(yearText);
    const hour = Number(hourText);
    const minute = Number(minuteText);
    const date = new Date(Date.UTC(year, month - 1, day, hour, minute));
    const valid = date.getUTCFullYear() === year
      && date.getUTCMonth() === month - 1
      && date.getUTCDate() === day
      && date.getUTCHours() === hour
      && date.getUTCMinutes() === minute;

    if (!valid) {
      return null;
    }

    const isoDate = `${yearText}-${padDatePart(month)}-${padDatePart(day)}`;
    return kind === 'datetime'
      ? `${isoDate}T${padDatePart(hour)}:${padDatePart(minute)}`
      : isoDate;
  };

  const createTimeSelect = (label, maximum, selectedValue) => {
    const select = document.createElement('select');
    select.className = 'datetime-br-control__select';
    select.setAttribute('aria-label', label);

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '--';
    select.appendChild(placeholder);

    for (let value = 0; value <= maximum; value += 1) {
      const formatted = padDatePart(value);
      const option = document.createElement('option');
      option.value = formatted;
      option.textContent = formatted;
      select.appendChild(option);
    }

    select.value = selectedValue || '';
    return select;
  };

  const compositeDateTimeControls = [];
  const compositeSources = Array.from(document.querySelectorAll(
    'input[type="datetime-local"], input[type="time"], input[data-date-br="datetime"]'
  )).filter((input) => !input.hasAttribute('data-date-native'));

  compositeSources.forEach((source) => {
    const sourceType = source.getAttribute('type');
    const kind = sourceType === 'time' ? 'time' : 'datetime';
    const currentValue = String(source.value || '').trim();
    const required = source.required;
    const originalId = source.id;
    let dateValue = '';
    let hourValue = '';
    let minuteValue = '';

    if (kind === 'datetime') {
      const brMatch = currentValue.match(/^(\d{2}\/\d{2}\/\d{4})(?:\s+)(\d{2}):(\d{2})$/);
      const isoMatch = currentValue.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/);
      if (brMatch) {
        [, dateValue, hourValue, minuteValue] = brMatch;
      } else if (isoMatch) {
        dateValue = `${isoMatch[3]}/${isoMatch[2]}/${isoMatch[1]}`;
        hourValue = isoMatch[4];
        minuteValue = isoMatch[5];
      }
    } else {
      const timeMatch = currentValue.match(/^(\d{2}):(\d{2})/);
      if (timeMatch) {
        hourValue = timeMatch[1];
        minuteValue = timeMatch[2];
      }
    }

    const wrapper = document.createElement('div');
    wrapper.className = `datetime-br-control datetime-br-control--${kind}`;
    wrapper.dataset.datetimeBrControl = kind;

    let dateField = null;
    if (kind === 'datetime') {
      const datePart = document.createElement('span');
      datePart.className = 'datetime-br-control__part datetime-br-control__part--date';
      const dateLabel = document.createElement('span');
      dateLabel.className = 'datetime-br-control__label';
      dateLabel.textContent = 'Data';
      dateField = document.createElement('input');
      dateField.type = 'text';
      dateField.className = 'datetime-br-control__date';
      dateField.value = dateValue;
      dateField.placeholder = 'dd/mm/aaaa';
      dateField.inputMode = 'numeric';
      dateField.autocomplete = 'off';
      dateField.maxLength = 10;
      dateField.pattern = '\\d{2}/\\d{2}/\\d{4}';
      dateField.title = 'Use o formato dd/mm/aaaa';
      dateField.required = required;
      dateField.setAttribute('aria-label', 'Data no formato dia, mês e ano');
      datePart.append(dateLabel, dateField);
      wrapper.appendChild(datePart);
    }

    const hourPart = document.createElement('span');
    hourPart.className = 'datetime-br-control__part';
    const hourLabel = document.createElement('span');
    hourLabel.className = 'datetime-br-control__label';
    hourLabel.textContent = 'Hora';
    const hourSelect = createTimeSelect('Hora de 00 a 23', 23, hourValue);
    hourSelect.required = required;
    hourPart.append(hourLabel, hourSelect);

    const minutePart = document.createElement('span');
    minutePart.className = 'datetime-br-control__part';
    const minuteLabel = document.createElement('span');
    minuteLabel.className = 'datetime-br-control__label';
    minuteLabel.textContent = 'Min.';
    const minuteSelect = createTimeSelect('Minutos de 00 a 59', 59, minuteValue);
    minuteSelect.required = required;
    minutePart.append(minuteLabel, minuteSelect);
    wrapper.append(hourPart, minutePart);

    source.type = 'hidden';
    source.required = false;
    source.removeAttribute('data-date-br');
    if (originalId) {
      source.removeAttribute('id');
      (dateField || hourSelect).id = originalId;
    }
    if (source.hasAttribute('data-expiration-field')) {
      source.removeAttribute('data-expiration-field');
      wrapper.setAttribute('data-expiration-field', '');
    }
    source.insertAdjacentElement('afterend', wrapper);

    const setDisabled = (disabled) => {
      source.disabled = disabled;
      wrapper.querySelectorAll('input, select').forEach((field) => {
        field.disabled = disabled;
      });
      wrapper.classList.toggle('is-disabled', disabled);
    };

    const syncValue = (reportErrors = false) => {
      const dateText = dateField ? dateField.value.trim() : '';
      const hourText = hourSelect.value;
      const minuteText = minuteSelect.value;
      const allEmpty = kind === 'datetime'
        ? dateText === '' && hourText === '' && minuteText === ''
        : hourText === '' && minuteText === '';

      [dateField, hourSelect, minuteSelect].filter(Boolean).forEach((field) => field.setCustomValidity(''));

      if (allEmpty && !required) {
        source.value = '';
        return true;
      }

      if ((dateField && dateText === '') || hourText === '' || minuteText === '') {
        if (reportErrors) {
          const field = (dateField && dateText === '') ? dateField : (hourText === '' ? hourSelect : minuteSelect);
          field.setCustomValidity('Preencha a data, a hora e os minutos.');
          field.reportValidity();
          field.focus();
        }
        return false;
      }

      const isoDate = dateField ? brDateToIso(dateText, 'date') : null;
      if (dateField && !isoDate) {
        if (reportErrors) {
          dateField.setCustomValidity('Informe uma data válida no formato dd/mm/aaaa.');
          dateField.reportValidity();
          dateField.focus();
        }
        return false;
      }

      source.value = kind === 'datetime'
        ? `${isoDate}T${hourText}:${minuteText}`
        : `${hourText}:${minuteText}`;
      return true;
    };

    dateField?.addEventListener('input', () => {
      dateField.setCustomValidity('');
      dateField.value = maskBrDate(dateField.value, 'date');
      syncValue(false);
    });
    hourSelect.addEventListener('change', () => syncValue(false));
    minuteSelect.addEventListener('change', () => syncValue(false));

    setDisabled(source.disabled);
    syncValue(false);
    compositeDateTimeControls.push({ source, wrapper, syncValue, setDisabled });
  });

  const dateInputs = Array.from(document.querySelectorAll(
    'input[type="date"], input[data-date-br="date"]'
  )).filter((input) => !input.hasAttribute('data-date-native'));

  dateInputs.forEach((input) => {
    const currentValue = input.value;
    input.dataset.dateBr = 'date';
    input.type = 'text';
    input.inputMode = 'numeric';
    input.autocomplete = 'off';
    input.maxLength = 10;
    input.placeholder = 'dd/mm/aaaa';
    input.pattern = '\\d{2}/\\d{2}/\\d{4}';
    input.title = 'Use o formato dd/mm/aaaa';
    input.value = isoDateToBr(currentValue, 'date');

    if (!input.readOnly) {
      input.addEventListener('input', () => {
        input.setCustomValidity('');
        input.value = maskBrDate(input.value, 'date');
      });
    }
  });

  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', (event) => {
      for (const control of compositeDateTimeControls) {
        if (!form.contains(control.source) || control.source.disabled) {
          continue;
        }
        if (!control.syncValue(true)) {
          event.preventDefault();
          return;
        }
      }

      const inputs = Array.from(form.querySelectorAll('input[data-date-br="date"]'));
      for (const input of inputs) {
        if (input.disabled || !input.name || input.value.trim() === '') {
          continue;
        }
        const isoValue = brDateToIso(input.value, 'date');
        if (!isoValue) {
          event.preventDefault();
          input.setCustomValidity('Informe uma data válida no formato dd/mm/aaaa.');
          input.reportValidity();
          input.focus();
          return;
        }
        input.setCustomValidity('');
        input.value = isoValue;
      }
    });
  });

  document.querySelectorAll('.table-wrap table').forEach((table) => {
    const labels = Array.from(table.querySelectorAll('thead th')).map((header) => header.textContent.trim());
    const normalizeLabel = (label) => label
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .trim();

    table.querySelectorAll('tbody tr').forEach((row) => {
      Array.from(row.children).forEach((cell, index) => {
        if (cell.tagName !== 'TD' || cell.hasAttribute('data-label') || cell.hasAttribute('colspan')) {
          return;
        }
        if (labels[index]) {
          cell.setAttribute('data-label', labels[index]);
          if (['acoes', 'retirada', 'devolver'].includes(normalizeLabel(labels[index]))) {
            cell.classList.add('table-cell--stacked');
          }
        }
      });
    });
  });

  const toggle = document.querySelector('[data-menu-toggle]');
  const nav = document.querySelector('[data-admin-nav]');
  if (toggle && nav) {
    const setMenuOpen = (open) => {
      nav.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
    };

    toggle.addEventListener('click', () => setMenuOpen(!nav.classList.contains('is-open')));

    nav.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => setMenuOpen(false));
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setMenuOpen(false);
      }
    });
  }

  document.querySelectorAll('.nav-group__button').forEach((button) => {
    button.addEventListener('click', () => {
      const group = button.closest('.nav-group');
      document.querySelectorAll('.nav-group.is-open').forEach((openGroup) => {
        if (openGroup !== group) {
          openGroup.classList.remove('is-open');
        }
      });
      if (group) {
        group.classList.toggle('is-open');
      }
    });
  });

  document.addEventListener('click', (event) => {
    if (!event.target.closest('.nav-group')) {
      document.querySelectorAll('.nav-group.is-open').forEach((group) => group.classList.remove('is-open'));
    }
  });

  document.querySelectorAll('[data-confirm]').forEach((button) => {
    button.addEventListener('click', (event) => {
      if (!window.confirm(button.getAttribute('data-confirm') || 'Confirmar ação?')) {
        event.preventDefault();
      }
    });
  });

  document.querySelectorAll('[data-reservation-bulk-form]').forEach((form) => {
    const selectAll = document.querySelector('[data-reservation-select-all]');
    const checkboxes = Array.from(document.querySelectorAll('[data-reservation-select]'))
      .filter((checkbox) => checkbox.getAttribute('form') === form.id);
    const countLabel = form.querySelector('[data-reservation-selected-count]');
    const deleteButton = form.querySelector('[data-reservation-delete-selected]');

    const updateSelection = () => {
      const selected = checkboxes.filter((checkbox) => checkbox.checked);
      checkboxes.forEach((checkbox) => {
        checkbox.closest('tr')?.classList.toggle('is-selected', checkbox.checked);
      });
      if (countLabel) {
        countLabel.textContent = selected.length === 1 ? '1 selecionada' : `${selected.length} selecionadas`;
      }
      if (deleteButton) {
        deleteButton.disabled = selected.length === 0;
      }
      if (selectAll) {
        selectAll.checked = checkboxes.length > 0 && selected.length === checkboxes.length;
        selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
      }
    };

    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
    selectAll?.addEventListener('change', () => {
      checkboxes.forEach((checkbox) => {
        checkbox.checked = selectAll.checked;
      });
      updateSelection();
    });
    form.addEventListener('submit', (event) => {
      const total = checkboxes.filter((checkbox) => checkbox.checked).length;
      if (total === 0 || !window.confirm(`Apagar permanentemente ${total} reserva(s) selecionada(s)?`)) {
        event.preventDefault();
      }
    });
    updateSelection();
  });

  document.querySelectorAll('input[type="file"][data-preview]').forEach((input) => {
    input.addEventListener('change', () => {
      const target = document.querySelector(input.dataset.preview);
      const file = input.files && input.files[0];
      if (target && file) {
        target.src = URL.createObjectURL(file);
      }
    });
  });

  const generatePassword = () => {
    const uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const lowercase = 'abcdefghijkmnopqrstuvwxyz';
    const numbers = '23456789';
    const symbols = '@#$%&*?';
    const allChars = uppercase + lowercase + numbers + symbols;
    const randomChar = (pool) => pool[Math.floor(Math.random() * pool.length)];
    const password = [
      randomChar(uppercase),
      randomChar(lowercase),
      randomChar(numbers),
      randomChar(symbols),
    ];

    for (let i = password.length; i < 12; i += 1) {
      password.push(randomChar(allChars));
    }

    for (let i = password.length - 1; i > 0; i -= 1) {
      const j = Math.floor(Math.random() * (i + 1));
      [password[i], password[j]] = [password[j], password[i]];
    }

    return password.join('');
  };

  const fillPassword = (input, overwrite = true) => {
    if (!input || (!overwrite && input.value.trim() !== '')) {
      return;
    }
    input.value = generatePassword();
    input.dispatchEvent(new Event('input', { bubbles: true }));
  };

  document.querySelectorAll('[data-generate-password]').forEach((button) => {
    button.addEventListener('click', () => {
      fillPassword(button.closest('label')?.querySelector('[data-generated-password]'));
    });
  });

  document.querySelectorAll('[data-copy-password]').forEach((button) => {
    button.addEventListener('click', async () => {
      const input = button.closest('label')?.querySelector('[data-generated-password]');
      if (!input) {
        return;
      }
      if (input.value.trim() === '') {
        fillPassword(input);
      }
      input.select();
      try {
        await navigator.clipboard.writeText(input.value);
      } catch (error) {
        document.execCommand('copy');
      }
    });
  });

  document.querySelectorAll('[data-generate-all-passwords]').forEach((button) => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-generated-password]').forEach((input) => fillPassword(input, false));
    });
  });

  document.querySelectorAll('[data-days-picker]').forEach((picker) => {
    const summary = picker.querySelector('[data-days-summary]');
    const inputs = picker.querySelectorAll('input[type="checkbox"]');
    const updateSummary = () => {
      const selected = Array.from(inputs)
        .filter((input) => input.checked)
        .map((input) => input.value);
      if (summary) {
        summary.textContent = selected.length ? selected.join(', ') : 'Todos os dias';
      }
    };

    inputs.forEach((input) => input.addEventListener('change', updateSummary));
    updateSummary();
  });

  document.querySelectorAll('[data-manual-withdrawal-form]').forEach((form) => {
    const select = form.querySelector('[data-manual-withdrawal-select]');
    const wrapper = form.querySelector('[data-manual-withdrawal-name]');
    const input = wrapper?.querySelector('input');

    const updateManualWithdrawal = () => {
      const manual = select?.value === 'nao_cadastrada';
      if (wrapper) {
        wrapper.hidden = !manual;
      }
      if (input) {
        input.disabled = !manual;
        input.required = manual;
        if (!manual) {
          input.value = '';
        }
      }
    };

    select?.addEventListener('change', updateManualWithdrawal);
    updateManualWithdrawal();
  });

  document.querySelectorAll('[data-room-calendar]').forEach((calendar) => {
    const roomFilter = document.querySelector('[data-calendar-room-filter]');
    const visibleCount = document.querySelector('[data-calendar-visible-count]');
    const monthLinks = Array.from(document.querySelectorAll('[data-calendar-month-link]'));
    const days = Array.from(calendar.querySelectorAll('[data-calendar-day]'));

    const updateMonthLinks = (roomId) => {
      monthLinks.forEach((link) => {
        const url = new URL(link.href, window.location.href);
        if (roomId === '0') {
          url.searchParams.delete('sala_id');
        } else {
          url.searchParams.set('sala_id', roomId);
        }
        link.href = url.toString();
      });
    };

    const filterCalendar = (updateAddress = false) => {
      const roomId = roomFilter ? String(roomFilter.value || '0') : null;
      let totalVisible = 0;

      days.forEach((day) => {
        const events = Array.from(day.querySelectorAll('[data-calendar-event]'));
        let dayVisible = 0;

        events.forEach((event) => {
          const show = roomId === null || roomId === '0' || event.dataset.roomId === roomId;
          event.hidden = !show;
          if (show) {
            dayVisible += 1;
            totalVisible += 1;
          }
        });

        const empty = day.querySelector('[data-calendar-day-empty]');
        if (empty) {
          empty.hidden = dayVisible > 0;
        }
      });

      if (visibleCount) {
        visibleCount.textContent = totalVisible === 1
          ? '1 atividade exibida'
          : `${totalVisible} atividades exibidas`;
      }

      if (roomFilter) {
        updateMonthLinks(roomId);
      }

      if (roomFilter && updateAddress && window.history?.replaceState) {
        const url = new URL(window.location.href);
        if (roomId === '0') {
          url.searchParams.delete('sala_id');
        } else {
          url.searchParams.set('sala_id', roomId);
        }
        window.history.replaceState({}, '', url);
      }
    };

    if (roomFilter) {
      roomFilter.addEventListener('change', () => filterCalendar(true));
    }
    filterCalendar(false);
  });

  document.querySelectorAll('form').forEach((form) => {
    const accessTotal = form.querySelector('[data-access-total]');
    const roomField = form.querySelector('[data-access-room]');
    const neverExpire = form.querySelector('[data-never-expire]');
    const expirationField = form.querySelector('[data-expiration-field]');

    const updatePermissionFields = () => {
      if (accessTotal && roomField) {
        roomField.disabled = accessTotal.checked;
      }
      if (neverExpire && expirationField) {
        const compositeControl = compositeDateTimeControls.find((control) => control.wrapper === expirationField);
        if (compositeControl) {
          compositeControl.setDisabled(neverExpire.checked);
          if (!neverExpire.checked) {
            compositeControl.syncValue(false);
          }
        } else if (expirationField.matches('input, select, textarea')) {
          expirationField.disabled = neverExpire.checked;
          if (neverExpire.checked) {
            expirationField.value = '';
          }
        } else {
          expirationField.classList.toggle('is-disabled', neverExpire.checked);
          expirationField.querySelectorAll('input, select, textarea').forEach((field) => {
            if (neverExpire.checked) {
              field.dataset.requiredBeforeNeverExpire = field.required ? '1' : '0';
              field.required = false;
              field.disabled = true;
            } else if (Object.prototype.hasOwnProperty.call(field.dataset, 'requiredBeforeNeverExpire')) {
              field.disabled = false;
              field.required = field.dataset.requiredBeforeNeverExpire === '1';
              delete field.dataset.requiredBeforeNeverExpire;
            }
          });
        }
      }
    };

    accessTotal?.addEventListener('change', updatePermissionFields);
    neverExpire?.addEventListener('change', updatePermissionFields);
    updatePermissionFields();
  });
});
