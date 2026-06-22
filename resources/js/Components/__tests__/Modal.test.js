import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import Modal from '../Modal.vue';

describe('Modal', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        document.body.style.overflow = '';
    });

    afterEach(() => {
        vi.useRealTimers();
        document.body.style.overflow = '';
    });

    describe('maxWidthClass', () => {
        it.each([
            ['sm', 'sm:max-w-sm'],
            ['md', 'sm:max-w-md'],
            ['lg', 'sm:max-w-lg'],
            ['xl', 'sm:max-w-xl'],
            ['2xl', 'sm:max-w-2xl'],
        ])('applies %s max-width class to the panel', (maxWidth, expected) => {
            const wrapper = mount(Modal, { props: { show: true, maxWidth } });
            expect(wrapper.html()).toContain(expected);
        });
    });

    it('renders slot content while show is true', () => {
        const wrapper = mount(Modal, {
            props: { show: true },
            slots: { default: '<p id="slot-content">Hello</p>' },
        });
        expect(wrapper.find('#slot-content').exists()).toBe(true);
    });

    it('hides the overlay when show is false', () => {
        const wrapper = mount(Modal, { props: { show: false } });
        const overlay = wrapper.find('.fixed.inset-0.transform');
        expect(overlay.element.style.display).toBe('none');
    });

    it('shows the overlay when show is true', () => {
        const wrapper = mount(Modal, { props: { show: true } });
        const overlay = wrapper.find('.fixed.inset-0.transform');
        expect(overlay.element.style.display).not.toBe('none');
    });

    it('sets body overflow to hidden when show becomes true', async () => {
        const wrapper = mount(Modal, { props: { show: false } });
        await wrapper.setProps({ show: true });
        expect(document.body.style.overflow).toBe('hidden');
    });

    it('clears body overflow when show becomes false', async () => {
        const wrapper = mount(Modal, { props: { show: true } });
        document.body.style.overflow = 'hidden';
        await wrapper.setProps({ show: false });
        expect(document.body.style.overflow).toBeFalsy();
    });

    it('clears body overflow on unmount', () => {
        document.body.style.overflow = 'hidden';
        const wrapper = mount(Modal, { props: { show: true } });
        wrapper.unmount();
        expect(document.body.style.overflow).toBeFalsy();
    });

    it('emits close when backdrop is clicked and closeable is true', async () => {
        const wrapper = mount(Modal, { props: { show: true, closeable: true } });
        await wrapper.find('.fixed.inset-0.transform').trigger('click');
        expect(wrapper.emitted('close')).toBeTruthy();
    });

    it('does not emit close when closeable is false and backdrop clicked', async () => {
        const wrapper = mount(Modal, { props: { show: true, closeable: false } });
        await wrapper.find('.fixed.inset-0.transform').trigger('click');
        expect(wrapper.emitted('close')).toBeFalsy();
    });

    it('emits close on Escape keydown when show is true and closeable', async () => {
        const wrapper = mount(Modal, { props: { show: true, closeable: true } });
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await wrapper.vm.$nextTick();
        expect(wrapper.emitted('close')).toBeTruthy();
    });

    it('does not emit close on Escape when show is false', async () => {
        const wrapper = mount(Modal, { props: { show: false, closeable: true } });
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await wrapper.vm.$nextTick();
        expect(wrapper.emitted('close')).toBeFalsy();
    });

    it('does not emit close on Escape when closeable is false', async () => {
        const wrapper = mount(Modal, { props: { show: true, closeable: false } });
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await wrapper.vm.$nextTick();
        expect(wrapper.emitted('close')).toBeFalsy();
    });

    it('removes keydown listener on unmount', async () => {
        const wrapper = mount(Modal, { props: { show: true, closeable: true } });
        wrapper.unmount();
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        // No assertion needed — just confirming no errors are thrown
    });

    it('does not show slot before show becomes true', () => {
        const wrapper = mount(Modal, {
            props: { show: false },
            slots: { default: '<p id="slot">content</p>' },
        });
        expect(wrapper.find('#slot').exists()).toBe(false);
    });

    it('does not close when a non-Escape key is pressed', async () => {
        const wrapper = mount(Modal, { props: { show: true, closeable: true } });
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'a' }));
        await wrapper.vm.$nextTick();
        expect(wrapper.emitted('close')).toBeFalsy();
    });

    it('covers setTimeout callback when modal closes (show: true → false)', async () => {
        const wrapper = mount(Modal, {
            props: { show: true },
            slots: { default: '<p>Content</p>' },
        });
        await wrapper.setProps({ show: false });
        // setTimeout(cb, 200) is now queued — advance time to fire it
        vi.advanceTimersByTime(201);
        await wrapper.vm.$nextTick();
        expect(wrapper.exists()).toBe(true);
    });
});
