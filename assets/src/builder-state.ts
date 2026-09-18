export type BuilderField = { key: string; type: string; label: string };

export function moveField(fields: BuilderField[], from: number, to: number): BuilderField[] {
  if (from < 0 || to < 0 || from >= fields.length || to >= fields.length) return [...fields];
  const copy = fields.map((field) => ({ ...field }));
  const [moved] = copy.splice(from, 1);
  copy.splice(to, 0, moved);
  return copy;
}

export function duplicateField(fields: BuilderField[], index: number): BuilderField[] {
  if (!fields[index]) return [...fields];
  const copy = fields.map((field) => ({ ...field }));
  const source = copy[index];
  const used = new Set(copy.map((field) => field.key));
  let suffix = 2;
  let key = `${source.key}_copy`;
  while (used.has(key)) key = `${source.key}_copy_${suffix++}`;
  copy.splice(index + 1, 0, { ...source, key });
  return copy;
}
