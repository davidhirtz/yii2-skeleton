/**
 * The posted order of a sortable list. Row ids are `${camel2id(formName)}-${primaryKey}`, so the model name may
 * itself contain dashes (e.g. `hotspot-asset-5`); the primary key is the last segment.
 *
 * Indexed, never `name[]` with an array: htmx 4 appends each value once, so an array arrives as one comma-joined
 * string and the server read the first id alone.
 */
export default ($list: HTMLElement): Record<string, string> => {
    const values: Record<string, string> = {};
    const counts: Record<string, number> = {};

    [...$list.children].forEach(($row) => {
        const parts = $row.id.split('-');
        const id = parts.pop()!;
        const name = parts.join('-');

        counts[name] ??= 0;
        values[`${name}[${counts[name]++}]`] = id;
    });

    return values;
};
