import { describe, it, expect, vi, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import Dropdown from '../Dropdown.vue';

describe('Dropdown', () => {
    afterEach(() => {
        vi.useRealTimers();
    });

    it('is closed by default', () => {
        const wrapper = mount(Dropdown);
        // v-show sets display:none when closed
        const panel = wrapper.find('.absolute.z-50');
        expect(panel.element.style.display).toBe('none');
    });

    it('opens when trigger is clicked', async () => {
        const wrapper = mount(Dropdown, {
            slots: { trigger: '<button id="trig">open</button>' },
        });
        // The trigger wrapper is the first child div inside .relative
        await wrapper.find('.relative > div').trigger('click');
        const panel = wrapper.find('.absolute.z-50');
        expect(panel.element.style.display).not.toBe('none');
    });

    it('closes when overlay is clicked', async () => {
        const wrapper = mount(Dropdown);
        // Open it first
        await wrapper.find('.relative > div').trigger('click');
        // Click the full-screen overlay
        await wrapper.find('.fixed.inset-0.z-40').trigger('click');
        const panel = wrapper.find('.absolute.z-50');
        expect(panel.element.style.display).toBe('none');
    });

    it('closes when Escape is pressed while open', async () => {
        const wrapper = mount(Dropdown);
        await wrapper.find('.relative > div').trigger('click');
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await wrapper.vm.$nextTick();
        const panel = wrapper.find('.absolute.z-50');
        expect(panel.element.style.display).toBe('none');
    });

    it('does not close on Escape when already closed', async () => {
        const wrapper = mount(Dropdown);
        // Already closed, pressing Escape should not throw
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.absolute.z-50').isVisible()).toBe(false);
    });

    it('removes Escape listener on unmount', () => {
        const wrapper = mount(Dropdown);
        wrapper.unmount();
        // Should not throw
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
    });

    describe('widthClass', () => {
        it('applies w-48 for width=48 (default)', () => {
            const wrapper = mount(Dropdown, { props: { width: '48' } });
            expect(wrapper.html()).toContain('w-48');
        });
    });

    describe('alignmentClasses', () => {
        it('applies left-alignment classes when align=left', () => {
            const wrapper = mount(Dropdown, { props: { align: 'left' } });
            const panel = wrapper.find('.absolute.z-50');
            expect(panel.classes().join(' ')).toContain('start-0');
        });

        it('applies right-alignment classes when align=right (default)', () => {
            const wrapper = mount(Dropdown, { props: { align: 'right' } });
            const panel = wrapper.find('.absolute.z-50');
            expect(panel.classes().join(' ')).toContain('end-0');
        });

        it('applies top-origin when align is neither left nor right', () => {
            const wrapper = mount(Dropdown, { props: { align: 'center' } });
            const panel = wrapper.find('.absolute.z-50');
            expect(panel.classes().join(' ')).toContain('origin-top');
        });
    });

    it('renders trigger slot', () => {
        const wrapper = mount(Dropdown, {
            slots: { trigger: '<span id="my-trigger">click</span>' },
        });
        expect(wrapper.find('#my-trigger').exists()).toBe(true);
    });

    it('renders content slot', () => {
        const wrapper = mount(Dropdown, {
            slots: { content: '<li id="item">Item</li>' },
        });
        expect(wrapper.find('#item').exists()).toBe(true);
    });

    it('closes panel when panel area is clicked', async () => {
        const wrapper = mount(Dropdown);
        await wrapper.find('.relative > div').trigger('click');
        await wrapper.find('.absolute.z-50').trigger('click');
        expect(wrapper.find('.absolute.z-50').isVisible()).toBe(false);
    });

    it('applies custom contentClasses to the inner div', () => {
        const wrapper = mount(Dropdown, {
            props: { contentClasses: ['bg-blue-100', 'p-2'] },
        });
        const inner = wrapper.find('.rounded-md.ring-1');
        expect(inner.classes()).toContain('bg-blue-100');
        expect(inner.classes()).toContain('p-2');
    });
});
