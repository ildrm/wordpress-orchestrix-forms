"use strict";
const root = document.querySelector('#orchestrix-admin-root');
const adminWindow = window;
const api = async (path, init = {}) => {
    const response = await fetch(adminWindow.OrchestrixAdmin.root + path, {
        ...init,
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': adminWindow.OrchestrixAdmin.nonce, ...(init.headers || {}) },
    });
    const body = await response.json();
    if (!response.ok)
        throw new Error(body.message || 'Request failed');
    return body;
};
const element = (tag, className = '', text = '') => {
    const node = document.createElement(tag);
    node.className = className;
    node.textContent = text;
    return node;
};
async function listForms() {
    if (!root)
        return;
    const forms = await api('forms');
    root.replaceChildren();
    const header = element('div', 'orch-toolbar');
    header.append(element('h1', '', 'Forms'));
    const create = element('button', 'button button-primary', 'Create form');
    create.addEventListener('click', async () => {
        const created = await api('forms', { method: 'POST', body: JSON.stringify({ name: 'Untitled form', schema: { fields: [] } }) });
        location.hash = `form=${created.id}`;
        await builder(created.id);
    });
    header.append(create);
    root.append(header);
    if (!forms.length)
        root.append(element('p', 'orch-empty', 'Create your first form. Add fields, publish, then embed it with the shortcode shown here.'));
    const table = element('table', 'widefat striped');
    table.innerHTML = '<thead><tr><th>Name</th><th>Status</th><th>Shortcode</th></tr></thead>';
    const tbody = element('tbody');
    forms.forEach((form) => {
        const row = element('tr');
        const nameCell = element('td');
        const link = element('button', 'button-link', form.name);
        link.addEventListener('click', () => { location.hash = `form=${form.id}`; void builder(form.id); });
        nameCell.append(link);
        row.append(nameCell, element('td', '', form.status), element('td', '', `[orchestrix_form id="${form.id}"]`));
        tbody.append(row);
    });
    table.append(tbody);
    root.append(table);
}
async function builder(id) {
    if (!root)
        return;
    const [form, definitions] = await Promise.all([api(`forms/${id}`), api('fields')]);
    const schema = form.schema || { fields: [] };
    schema.fields || (schema.fields = []);
    const history = [JSON.stringify(schema.fields)];
    let historyIndex = 0;
    let selected = 0;
    let dirty = false;
    root.replaceChildren();
    const toolbar = element('header', 'orch-toolbar');
    const title = element('input', 'orch-title');
    title.value = form.name;
    title.setAttribute('aria-label', 'Form title');
    const status = element('span', 'orch-save-status', 'Saved');
    const undo = element('button', 'button', 'Undo');
    const redo = element('button', 'button', 'Redo');
    const save = element('button', 'button', 'Save draft');
    const publish = element('button', 'button button-primary', 'Publish');
    toolbar.append(title, status, undo, redo, save, publish);
    root.append(toolbar);
    const mode = element('div', 'orch-mode');
    mode.innerHTML = '<button class="is-active" data-mode="simple">Simple</button><button data-mode="advanced">Advanced</button>';
    root.append(mode);
    const layout = element('div', 'orch-builder');
    const library = element('aside', 'orch-library');
    const canvas = element('main', 'orch-canvas');
    const inspector = element('aside', 'orch-inspector');
    library.innerHTML = '<h2>Field library</h2><input type="search" placeholder="Search fields" aria-label="Search fields"><div class="orch-field-list"></div>';
    canvas.innerHTML = '<h2>Canvas</h2><div class="orch-canvas-list" aria-live="polite"></div>';
    inspector.innerHTML = '<h2>Inspector</h2><div class="orch-inspector-body"></div>';
    layout.append(library, canvas, inspector);
    root.append(layout);
    const fieldList = library.querySelector('.orch-field-list');
    const canvasList = canvas.querySelector('.orch-canvas-list');
    const checkpoint = () => { history.splice(historyIndex + 1); history.push(JSON.stringify(schema.fields)); historyIndex++; dirty = true; status.textContent = 'Unsaved changes'; render(); };
    const add = (type) => { const n = schema.fields.length + 1; schema.fields.push({ key: `${type}_${n}`, type, label: type.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()) }); selected = schema.fields.length - 1; checkpoint(); };
    Object.values(definitions).forEach((definition) => { const button = element('button', 'button orch-field-button', definition.type.replace(/_/g, ' ')); button.dataset.search = definition.type; button.addEventListener('click', () => add(definition.type)); fieldList.append(button); });
    library.querySelector('input')?.addEventListener('input', (event) => { const query = event.target.value.toLowerCase(); fieldList.querySelectorAll('button').forEach((button) => { button.hidden = !(button.dataset.search || '').includes(query); }); });
    function render() {
        canvasList.replaceChildren();
        schema.fields.forEach((field, index) => {
            const card = element('article', `orch-field-card${index === selected ? ' is-selected' : ''}`);
            card.draggable = true;
            card.tabIndex = 0;
            card.dataset.index = String(index);
            card.append(element('strong', '', field.label), element('small', '', field.type));
            const controls = element('span', 'orch-card-actions');
            const up = element('button', 'button', '↑');
            up.title = 'Move up';
            up.disabled = index === 0;
            up.addEventListener('click', (e) => { e.stopPropagation(); [schema.fields[index - 1], schema.fields[index]] = [schema.fields[index], schema.fields[index - 1]]; selected = index - 1; checkpoint(); });
            const down = element('button', 'button', '↓');
            down.title = 'Move down';
            down.disabled = index === schema.fields.length - 1;
            down.addEventListener('click', (e) => { e.stopPropagation(); [schema.fields[index + 1], schema.fields[index]] = [schema.fields[index], schema.fields[index + 1]]; selected = index + 1; checkpoint(); });
            const duplicate = element('button', 'button', 'Duplicate');
            duplicate.addEventListener('click', (e) => { e.stopPropagation(); schema.fields.splice(index + 1, 0, { ...field, key: `${field.key}_copy` }); selected = index + 1; checkpoint(); });
            const remove = element('button', 'button', 'Remove');
            remove.addEventListener('click', (e) => { e.stopPropagation(); schema.fields.splice(index, 1); selected = Math.max(0, index - 1); checkpoint(); });
            controls.append(up, down, duplicate, remove);
            card.append(controls);
            card.addEventListener('click', () => { selected = index; render(); });
            card.addEventListener('dragstart', (e) => e.dataTransfer?.setData('text/plain', String(index)));
            card.addEventListener('dragover', (e) => e.preventDefault());
            card.addEventListener('drop', (e) => { e.preventDefault(); const from = Number(e.dataTransfer?.getData('text/plain')); const [moved] = schema.fields.splice(from, 1); schema.fields.splice(index, 0, moved); selected = index; checkpoint(); });
            canvasList.append(card);
        });
        renderInspector();
        undo.disabled = historyIndex <= 0;
        redo.disabled = historyIndex >= history.length - 1;
    }
    function renderInspector() {
        const body = inspector.querySelector('.orch-inspector-body');
        body.replaceChildren();
        const field = schema.fields[selected];
        if (!field) {
            body.append(element('p', '', 'Select a field to edit its settings.'));
            return;
        }
        [['Label', 'label'], ['Developer key', 'key'], ['Description', 'description'], ['Placeholder', 'placeholder']].forEach(([labelText, key]) => {
            const label = element('label', '', labelText);
            const input = element('input');
            input.value = String(field[key] || '');
            input.addEventListener('change', () => { field[key] = input.value; checkpoint(); });
            label.append(input);
            body.append(label);
        });
        const required = element('label', '', ' Required');
        const checkbox = element('input');
        checkbox.type = 'checkbox';
        checkbox.checked = !!field.required;
        checkbox.addEventListener('change', () => { field.required = checkbox.checked; checkpoint(); });
        required.prepend(checkbox);
        body.append(required);
    }
    const persist = async () => { status.textContent = 'Saving…'; await api(`forms/${id}/draft`, { method: 'PUT', body: JSON.stringify({ schema }) }); dirty = false; status.textContent = 'Saved'; };
    save.addEventListener('click', () => void persist());
    publish.addEventListener('click', async () => { if (dirty)
        await persist(); if (!confirm('Publish this immutable version? Existing submissions keep their original schema.'))
        return; await api(`forms/${id}/publish`, { method: 'POST' }); status.textContent = 'Published'; });
    undo.addEventListener('click', () => { if (historyIndex > 0) {
        historyIndex--;
        schema.fields = JSON.parse(history[historyIndex]);
        dirty = true;
        render();
    } });
    redo.addEventListener('click', () => { if (historyIndex < history.length - 1) {
        historyIndex++;
        schema.fields = JSON.parse(history[historyIndex]);
        dirty = true;
        render();
    } });
    window.onbeforeunload = () => dirty ? 'You have unsaved changes.' : null;
    render();
}
async function entries() {
    if (!root)
        return;
    const items = await api('submissions');
    const table = element('table', 'widefat striped');
    table.innerHTML = '<thead><tr><th>ID</th><th>Form</th><th>Status</th><th>Date</th></tr></thead>';
    const body = element('tbody');
    items.forEach((item) => { const row = element('tr'); row.append(element('td', '', item.id), element('td', '', item.form_id), element('td', '', item.status), element('td', '', item.created_at)); body.append(row); });
    table.append(body);
    root.replaceChildren(table);
}
const match = /form=(\d+)/.exec(location.hash);
if (adminWindow.OrchestrixAdmin.page === 'orchestrix-entries')
    void entries();
else if (match)
    void builder(Number(match[1]));
else
    void listForms();
