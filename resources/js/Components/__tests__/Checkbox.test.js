import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import Checkbox from '../Checkbox.vue';

describe('Checkbox', () => {
    it('renders a checkbox input', () => {
        const wrapper = mount(Checkbox);
        expect(wrapper.find('input[type="checkbox"]').exists()).toBe(true);
    });

    it('is checked when checked=true', () => {
        const wrapper = mount(Checkbox, { props: { checked: true } });
        expect(wrapper.find('input').element.checked).toBe(true);
    });

    it('is unchecked when checked=false', () => {
        const wrapper = mount(Checkbox, { props: { checked: false } });
        expect(wrapper.find('input').element.checked).toBe(false);
    });

    it('emits update:checked with the new checked value on change', async () => {
        const wrapper = mount(Checkbox, { props: { checked: false } });
        const input = wrapper.find('input');
        input.element.checked = true;
        await input.trigger('change');
        expect(wrapper.emitted('update:checked')).toBeTruthy();
        expect(wrapper.emitted('update:checked')[0][0]).toBe(true);
    });

    it('sets the value attribute when value prop is given', () => {
        const wrapper = mount(Checkbox, { props: { checked: [], value: 'option-a' } });
        expect(wrapper.find('input').attributes('value')).toBe('option-a');
    });

    it('reflects array-based checked state', () => {
        const wrapper = mount(Checkbox, { props: { checked: ['option-a'], value: 'option-a' } });
        expect(wrapper.find('input').element.checked).toBe(true);
    });

    it('applies the expected CSS classes', () => {
        const wrapper = mount(Checkbox);
        expect(wrapper.find('input').classes()).toContain('rounded');
        expect(wrapper.find('input').classes()).toContain('text-indigo-600');
    });
});
