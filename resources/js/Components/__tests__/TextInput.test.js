import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import TextInput from '../TextInput.vue';

describe('TextInput', () => {
    it('renders an input element', () => {
        const wrapper = mount(TextInput);
        expect(wrapper.find('input').exists()).toBe(true);
    });

    it('displays the modelValue', () => {
        const wrapper = mount(TextInput, { props: { modelValue: 'hello' } });
        expect(wrapper.find('input').element.value).toBe('hello');
    });

    it('emits update:modelValue when the user types', async () => {
        const wrapper = mount(TextInput, { props: { modelValue: '' } });
        await wrapper.find('input').setValue('new value');
        expect(wrapper.emitted('update:modelValue')).toBeTruthy();
        expect(wrapper.emitted('update:modelValue')[0][0]).toBe('new value');
    });

    it('exposes a focus() method that focuses the input', () => {
        const wrapper = mount(TextInput, { attachTo: document.body });
        const focusSpy = vi.spyOn(wrapper.find('input').element, 'focus');
        wrapper.vm.focus();
        expect(focusSpy).toHaveBeenCalled();
        wrapper.unmount();
    });

    it('applies the expected CSS classes', () => {
        const wrapper = mount(TextInput);
        const input = wrapper.find('input');
        expect(input.classes()).toContain('rounded-md');
        expect(input.classes()).toContain('shadow-sm');
    });

    it('auto-focuses when the autofocus attribute is present', () => {
        const div = document.createElement('div');
        document.body.appendChild(div);
        const focusSpy = vi.spyOn(HTMLElement.prototype, 'focus');
        mount(TextInput, { attrs: { autofocus: true }, attachTo: div });
        expect(focusSpy).toHaveBeenCalled();
        document.body.removeChild(div);
    });
});
