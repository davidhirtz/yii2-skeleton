import {beforeEach, describe, expect, it, vi} from 'vitest';
import {cancelRestore, isRestore, prepareRestore, restoreScroll, saveScroll} from '../../../src/js/includes/historyScroll';

const scrollTo = (top: number) => Object.defineProperty(window, 'scrollY', {value: top, configurable: true});

describe('historyScroll', () => {
    beforeEach(() => {
        cancelRestore();
        history.replaceState({htmx: true}, '', '/admin/trail/index');
        scrollTo(0);
        window.scrollTo = vi.fn() as typeof window.scrollTo;
    });

    it('keeps the offset in the entry beside the htmx state', () => {
        scrollTo(1200);
        saveScroll();

        expect(history.state).toEqual({htmx: true, scrollY: 1200});
    });

    it('leaves an entry htmx does not restore alone', () => {
        history.replaceState(null, '');
        scrollTo(1200);
        saveScroll();

        expect(history.state).toBeNull();
    });

    it('restores the offset of the entry gone back to', () => {
        history.replaceState({htmx: true, scrollY: 1200}, '');
        prepareRestore();
        restoreScroll();

        expect(window.scrollTo).toHaveBeenCalledWith({top: 1200, behavior: 'instant'});
    });

    it('restores the top of an entry that never scrolled', () => {
        prepareRestore();
        restoreScroll();

        expect(window.scrollTo).toHaveBeenCalledWith({top: 0, behavior: 'instant'});
    });

    it('restores once', () => {
        prepareRestore();
        restoreScroll();
        restoreScroll();

        expect(window.scrollTo).toHaveBeenCalledTimes(1);
    });

    it('does not write the page being left into the entry being restored', () => {
        history.replaceState({htmx: true, scrollY: 1200}, '');
        prepareRestore();
        scrollTo(400);
        saveScroll();

        expect(history.state.scrollY).toBe(1200);
    });

    it('saves again after a restore that never swapped', () => {
        prepareRestore();
        cancelRestore();
        restoreScroll();
        scrollTo(400);
        saveScroll();

        expect(window.scrollTo).not.toHaveBeenCalled();
        expect(history.state.scrollY).toBe(400);
    });

    it('tells a restore from a navigation', () => {
        expect(isRestore({request: {headers: {'HX-History-Restore-Request': 'true'}}})).toBe(true);
        expect(isRestore({request: {headers: {}}})).toBe(false);
        expect(isRestore(undefined)).toBe(false);
    });
});
