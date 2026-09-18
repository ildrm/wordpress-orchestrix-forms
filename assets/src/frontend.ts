type ErrorPayload = { message?: string; data?: { fields?: Record<string, string[]> } };

function values(form: HTMLFormElement): Record<string, FormDataEntryValue | FormDataEntryValue[]> {
  const result: Record<string, FormDataEntryValue | FormDataEntryValue[]> = {};
  for (const [name, value] of new FormData(form).entries()) {
    const match = /^orchestrix\[([^\]]+)\](?:\[\])?$/.exec(name);
    if (!match) continue;
    const key = match[1];
    if (key in result) {
      const current = result[key];
      result[key] = Array.isArray(current) ? [...current, value] : [current, value];
    } else {
      result[key] = value;
    }
  }
  return result;
}

function clearErrors(form: HTMLFormElement): void {
  form.querySelectorAll('[aria-invalid="true"]').forEach((node) => node.removeAttribute('aria-invalid'));
  form.querySelectorAll('.orchestrix-field-error').forEach((node) => node.remove());
}

function showErrors(form: HTMLFormElement, payload: ErrorPayload): void {
  const summary = form.querySelector<HTMLElement>('.orchestrix-error-summary');
  if (!summary) return;
  summary.replaceChildren();
  const heading = document.createElement('strong');
  heading.textContent = payload.message || 'Please correct the highlighted fields.';
  summary.append(heading);
  const list = document.createElement('ul');
  const errors = payload.data?.fields || {};
  Object.entries(errors).forEach(([key, messages]) => {
    const input = form.querySelector<HTMLElement>(`[name="orchestrix[${CSS.escape(key)}]"]`);
    if (input) {
      input.setAttribute('aria-invalid', 'true');
      const error = document.createElement('p');
      error.className = 'orchestrix-field-error';
      error.textContent = messages.join(' ');
      input.insertAdjacentElement('afterend', error);
    }
    const item = document.createElement('li');
    item.textContent = messages.join(' ');
    list.append(item);
  });
  if (list.childElementCount) summary.append(list);
  summary.hidden = false;
  summary.focus();
}

document.querySelectorAll<HTMLFormElement>('[data-orchestrix-form]').forEach((form) => {
  form.addEventListener('submit', async (event) => {
    if (!window.fetch) return;
    event.preventDefault();
    clearErrors(form);
    const config = JSON.parse(form.dataset.config || '{}') as { endpoint?: string };
    if (!config.endpoint) return;
    const button = form.querySelector<HTMLButtonElement>('[type="submit"]');
    const status = form.querySelector<HTMLElement>('.orchestrix-status');
    if (button) button.disabled = true;
    form.setAttribute('aria-busy', 'true');
    if (status) status.textContent = 'Submitting…';
    try {
      const response = await fetch(config.endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          token: form.querySelector<HTMLInputElement>('[name="orchestrix_token"]')?.value,
          rendered_at: Number(form.querySelector<HTMLInputElement>('[name="orchestrix_rendered_at"]')?.value || 0),
          website: form.querySelector<HTMLInputElement>('[name="website"]')?.value || '',
          source_url: location.href,
          values: values(form),
        }),
      });
      const payload = (await response.json()) as ErrorPayload & { message?: string };
      if (!response.ok) {
        showErrors(form, payload);
      } else {
        form.reset();
        const summary = form.querySelector<HTMLElement>('.orchestrix-error-summary');
        if (summary) summary.hidden = true;
        if (status) status.textContent = payload.message || 'Thank you. Your submission was received.';
      }
    } catch {
      if (status) status.textContent = 'The form could not be submitted. Check your connection and try again.';
    } finally {
      if (button) button.disabled = false;
      form.removeAttribute('aria-busy');
    }
  });
});
