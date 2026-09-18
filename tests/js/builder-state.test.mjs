import assert from 'node:assert/strict';
import test from 'node:test';
import { duplicateField, moveField } from '../../assets/dist/builder-state.js';

const fields = [
  { key: 'name', type: 'text', label: 'Name' },
  { key: 'email', type: 'email', label: 'Email' },
];

test('moveField reorders without mutating input', () => {
  const moved = moveField(fields, 1, 0);
  assert.deepEqual(moved.map((field) => field.key), ['email', 'name']);
  assert.deepEqual(fields.map((field) => field.key), ['name', 'email']);
});

test('duplicateField creates a unique developer key', () => {
  const duplicated = duplicateField([...fields, { ...fields[0], key: 'name_copy' }], 0);
  assert.equal(duplicated[1].key, 'name_copy_2');
});
