import {describe, expect, it} from 'vitest';
import configureHtmx from '../../../src/js/includes/htmxConfig';

describe('htmxConfig', () => {
    it('leaves long requests to PHP\'s limits rather than aborting them', () => {
        const config = {defaultTimeout: 60000, transitions: false};

        configureHtmx(config);

        expect(config.defaultTimeout).toBe(0);
        expect(config.transitions).toBe(true);
    });
});
