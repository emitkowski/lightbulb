import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import ActionMessage from '../ActionMessage.vue';

describe('ActionMessage', () => {
    it('shows the inner div when on is true', () => {
        const wrapper = mount(ActionMessage, { props: { on: true } });
        expect(wrapper.find('.text-sm.text-gray-600').isVisible()).toBe(true);
    });

    it('hides the inner div when on is false', () => {
        const wrapper = mount(ActionMessage, { props: { on: false } });
        expect(wrapper.find('.text-sm.text-gray-600').isVisible()).toBe(false);
    });

    it('renders slot content', () => {
        const wrapper = mount(ActionMessage, {
            props: { on: true },
            slots: { default: 'Saved!' },
        });
        expect(wrapper.text()).toBe('Saved!');
    });

    it('renders slot content even when hidden', () => {
        const wrapper = mount(ActionMessage, {
            props: { on: false },
            slots: { default: 'Hidden message' },
        });
        expect(wrapper.html()).toContain('Hidden message');
    });
});
