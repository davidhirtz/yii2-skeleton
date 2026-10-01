interface Config {
    defaultTimeout: number;
    transitions: boolean;
}

/**
 * htmx aborts a request after `defaultTimeout`, 60 s by default, and the abort is an `AbortError`, which
 * `networkError.ts` ignores — a long save ended without a swap, a flash or an error while the server finished it.
 * PHP's own limits decide instead; htmx sets no timer at 0.
 */
export default (config: Config) => {
    config.defaultTimeout = 0;
    config.transitions = true;
};
