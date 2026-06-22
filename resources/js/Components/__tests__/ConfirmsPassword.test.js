import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => ({}));

import ConfirmsPassword from '../ConfirmsPassword.vue';

const dialogStubs = {
    DialogModal: {
        props: ['show', 'maxWidth'],
        emits: ['close'],
        template: `<div v-if="show" class="confirm-dialog">
            <slot name="title" />
            <slot name="content" />
            <slot name="footer" />
        </div>`,
    },
    InputError: { props: ['message'], template: '<p v-if="message" class="error">{{ message }}</p>' },
    InputLabel: { props: ['value'], template: '<label>{{ value }}</label>' },
    PrimaryButton: { template: '<button type="submit"><slot /></button>' },
    SecondaryButton: { template: '<button type="button" class="cancel-btn"><slot /></button>' },
    TextInput: {
        template: '<input class="pwd-input" />',
        setup() { return { focus: vi.fn() }; },
        expose: ['focus'],
    },
};

describe('ConfirmsPassword', () => {
    beforeEach(() => {
        vi.useRealTimers();
        window.axios = {
            get: vi.fn().mockResolvedValue({ data: { confirmed: true } }),
            post: vi.fn().mockResolvedValue({}),
        };
    });

    it('renders slot content without opening dialog', () => {
        const wrapper = mount(ConfirmsPassword, {
            slots: { default: '<button id="trigger">Confirm Action</button>' },
            global: { stubs: dialogStubs },
        });
        expect(wrapper.find('#trigger').exists()).toBe(true);
        // Dialog not open by default
        expect(wrapper.find('.confirm-dialog').exists()).toBe(false);
    });

    it('emits confirmed immediately when password already confirmed', async () => {
        window.axios.get = vi.fn().mockResolvedValue({ data: { confirmed: true } });
        const wrapper = mount(ConfirmsPassword, {
            slots: { default: '<button id="trigger">Action</button>' },
            global: { stubs: dialogStubs },
        });
        await wrapper.find('#trigger').trigger('click');
        // Flush promise chain
        await new Promise(r => setTimeout(r, 10));
        await wrapper.vm.$nextTick();
        // When already confirmed, emits immediately without showing dialog
        expect(wrapper.emitted('confirmed')).toBeTruthy();
    });

    it('shows dialog when confirmation is required', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        window.axios.get = vi.fn().mockResolvedValue({ data: { confirmed: false } });
        const wrapper = mount(ConfirmsPassword, {
            slots: { default: '<button id="trigger">Action</button>' },
            global: { stubs: dialogStubs },
        });
        wrapper.find('#trigger').trigger('click');
        // Flush the axios promise
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.confirm-dialog').exists()).toBe(true);
        vi.useRealTimers();
    });

    it('closes dialog when Cancel is clicked', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        window.axios.get = vi.fn().mockResolvedValue({ data: { confirmed: false } });
        const wrapper = mount(ConfirmsPassword, {
            slots: { default: '<button id="trigger">Action</button>' },
            global: { stubs: dialogStubs },
        });
        wrapper.find('#trigger').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        await wrapper.find('.cancel-btn').trigger('click');
        expect(wrapper.find('.confirm-dialog').exists()).toBe(false);
        vi.useRealTimers();
    });

    it('submits password on form submit', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        window.axios.get = vi.fn().mockResolvedValue({ data: { confirmed: false } });
        window.axios.post = vi.fn().mockResolvedValue({});
        const wrapper = mount(ConfirmsPassword, {
            slots: { default: '<button id="trigger">Action</button>' },
            global: { stubs: dialogStubs },
        });
        wrapper.find('#trigger').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        await wrapper.find('button[type="submit"]').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        expect(window.axios.post).toHaveBeenCalledOnce();
        vi.useRealTimers();
    });

    it('emits confirmed after successful password submission', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        window.axios.get = vi.fn().mockResolvedValue({ data: { confirmed: false } });
        window.axios.post = vi.fn().mockResolvedValue({});
        const wrapper = mount(ConfirmsPassword, {
            slots: { default: '<button id="trigger">Action</button>' },
            global: { stubs: dialogStubs },
        });
        wrapper.find('#trigger').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        await wrapper.find('button[type="submit"]').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        expect(wrapper.emitted('confirmed')).toBeTruthy();
        vi.useRealTimers();
    });

    it('shows error message when password submission fails', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        window.axios.get = vi.fn().mockResolvedValue({ data: { confirmed: false } });
        window.axios.post = vi.fn().mockRejectedValue({
            response: { data: { errors: { password: ['The password is incorrect.'] } } },
        });
        const wrapper = mount(ConfirmsPassword, {
            slots: { default: '<button id="trigger">Action</button>' },
            global: { stubs: dialogStubs },
        });
        wrapper.find('#trigger').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        await wrapper.find('button[type="submit"]').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.error').exists()).toBe(true);
        vi.useRealTimers();
    });
});
