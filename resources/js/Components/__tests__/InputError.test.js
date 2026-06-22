import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import InputError from '../InputError.vue';

describe('InputError', () => {
    it('renders the message when provided', () => {
        const wrapper = mount(InputError, { props: { message: 'This field is required.' } });
        expect(wrapper.text()).toContain('This field is required.');
    });

    it('is hidden when no message is provided', () => {
        const wrapper = mount(InputError, { props: { message: '' } });
        expect(wrapper.find('div').isVisible()).toBe(false);
    });

    it('applies the error text style', () => {
        const wrapper = mount(InputError, { props: { message: 'Error' } });
        expect(wrapper.find('p').classes()).toContain('text-red-600');
    });
});
