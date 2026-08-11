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
        expirationField.disabled = neverExpire.checked;
        if (neverExpire.checked) {
          expirationField.value = '';
        }
      }
    };

    accessTotal?.addEventListener('change', updatePermissionFields);
    neverExpire?.addEventListener('change', updatePermissionFields);
    updatePermissionFields();
  });
});
