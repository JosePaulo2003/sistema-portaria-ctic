document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('[data-menu-toggle]');
  const nav = document.querySelector('[data-admin-nav]');
  if (toggle && nav) {
    toggle.addEventListener('click', () => nav.classList.toggle('is-open'));
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
