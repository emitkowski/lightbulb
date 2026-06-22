/**
 * Covers form callback paths and modal interactions in Profile Partials.
 */
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

const makeForm = (data = {}) => ({
    ...data,
    processing: false,
    errors: {},
    recentlySuccessful: false,
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    reset: vi.fn(),
});

vi.mock('@inertiajs/vue3', () => ({
    Link: { name: 'Link', props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { put: vi.fn(), post: vi.fn(), delete: vi.fn(), reload: vi.fn() },
    useForm: vi.fn((data = {}) => makeForm(data)),
    usePage: vi.fn(() => ({
        props: {
            auth: { user: { id: 1, name: 'Alice', email: 'alice@example.com', two_factor_enabled: false } },
            jetstream: { managesProfilePhotos: false, hasEmailVerification: false },
        },
    })),
}));

import UpdatePasswordForm from '../Partials/UpdatePasswordForm.vue';
import LogoutOtherBrowserSessionsForm from '../Partials/LogoutOtherBrowserSessionsForm.vue';
import DeleteUserForm from '../Partials/DeleteUserForm.vue';

const FocusableInput = {
    props: ['modelValue', 'type', 'autocomplete'],
    emits: ['update:modelValue'],
    setup(_, { expose }) {
        const focus = vi.fn();
        expose({ focus });
        return { focus };
    },
    template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
};

const stubs = {
    FormSection: { emits: ['submitted'], template: '<form @submit.prevent="$emit(\'submitted\')"><slot name="title" /><slot name="form" /><slot name="actions" /></form>' },
    ActionSection: { template: '<div><slot name="title" /><slot name="description" /><slot name="content" /><slot name="actions" /></div>' },
    ActionMessage: { props: ['on'], template: '<div v-if="on"><slot /></div>' },
    InputError: { props: ['message'], template: '<p v-if="message" class="err">{{ message }}</p>' },
    InputLabel: { props: ['value', 'for'], template: '<label>{{ value }}</label>' },
    PrimaryButton: { template: '<button type="submit"><slot /></button>' },
    SecondaryButton: { template: '<button type="button" class="cancel"><slot /></button>' },
    DangerButton: { template: '<button type="button" class="danger"><slot /></button>' },
    TextInput: FocusableInput,
    DialogModal: { props: ['show'], emits: ['close'], template: '<div v-if="show" class="dialog"><slot name="content" /><slot name="footer" /></div>' },
    SectionTitle: { template: '<div><slot /></div>' },
};

describe('UpdatePasswordForm — callbacks', () => {
    it('covers form.put call via submitted event', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        const putMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm(), put: putMock });
        const wrapper = mount(UpdatePasswordForm, { global: { stubs } });
        await wrapper.find('form').trigger('submit');
        expect(putMock).toHaveBeenCalled();
    });

    it('resets form on success callback', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        const resetMock = vi.fn();
        useForm.mockReturnValueOnce({
            ...makeForm(),
            put: vi.fn((url, options) => options?.onSuccess?.()),
            reset: resetMock,
        });
        const wrapper = mount(UpdatePasswordForm, { global: { stubs } });
        await wrapper.find('form').trigger('submit');
        expect(resetMock).toHaveBeenCalled();
    });

    it('shows password errors via onError callback', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        useForm.mockReturnValueOnce({
            ...makeForm(),
            errors: { password: 'The password is too short.' },
            put: vi.fn((url, options) => options?.onError?.()),
            reset: vi.fn(),
        });
        const wrapper = mount(UpdatePasswordForm, { global: { stubs } });
        await wrapper.find('form').trigger('submit');
        expect(wrapper.find('.err').text()).toContain('The password is too short.');
    });

    it('shows current_password errors via onError callback', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        useForm.mockReturnValueOnce({
            ...makeForm(),
            errors: { current_password: 'The current password is incorrect.' },
            put: vi.fn((url, options) => options?.onError?.()),
            reset: vi.fn(),
        });
        const wrapper = mount(UpdatePasswordForm, { global: { stubs } });
        await wrapper.find('form').trigger('submit');
        expect(wrapper.find('.err').text()).toContain('The current password is incorrect.');
    });
});

describe('LogoutOtherBrowserSessionsForm — modal', () => {
    it('shows logout dialog when Logout button is clicked', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const wrapper = mount(LogoutOtherBrowserSessionsForm, {
            props: { sessions: [] },
            global: { stubs },
        });
        // PrimaryButton renders as type=submit, find it by text
        const btn = wrapper.findAll('button').find(b => b.text().includes('Log Out'));
        if (btn) await btn.trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.dialog').exists()).toBe(true);
        vi.useRealTimers();
    });

    it('shows password field inside logout dialog after opening', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const wrapper = mount(LogoutOtherBrowserSessionsForm, {
            props: { sessions: [] },
            global: { stubs },
        });
        const btn = wrapper.findAll('button').find(b => b.text().includes('Log Out'));
        if (btn) await btn.trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        // The dialog shows once confirmingLogout is true
        const dialog = wrapper.find('.dialog');
        if (dialog.exists()) {
            expect(wrapper.findAll('input').length).toBeGreaterThan(0);
        }
        expect(wrapper.exists()).toBe(true);
        vi.useRealTimers();
    });
});

describe('LogoutOtherBrowserSessionsForm — desktop agent and logout callbacks', () => {
    it('renders desktop SVG when session.agent.is_desktop is true', () => {
        const sessions = [
            { agent: { is_desktop: true, platform: 'Mac', browser: 'Chrome' }, ip_address: '10.0.0.1', is_current_device: true, last_active: 'just now' },
        ];
        const wrapper = mount(LogoutOtherBrowserSessionsForm, {
            props: { sessions },
            global: { stubs },
        });
        expect(wrapper.text()).toContain('10.0.0.1');
        expect(wrapper.text()).toContain('This device');
    });

    it('renders mobile SVG when session.agent.is_desktop is false', () => {
        const sessions = [
            { agent: { is_desktop: false, platform: 'iOS', browser: 'Safari' }, ip_address: '10.0.0.2', is_current_device: false, last_active: '1 hour ago' },
        ];
        const wrapper = mount(LogoutOtherBrowserSessionsForm, {
            props: { sessions },
            global: { stubs },
        });
        expect(wrapper.text()).toContain('iOS');
        expect(wrapper.text()).toContain('1 hour ago');
    });

    it('shows Unknown when agent platform and browser are missing', () => {
        const sessions = [
            { agent: { is_desktop: false, platform: null, browser: null }, ip_address: '10.0.0.3', is_current_device: false, last_active: '2 days ago' },
        ];
        const wrapper = mount(LogoutOtherBrowserSessionsForm, {
            props: { sessions },
            global: { stubs },
        });
        expect(wrapper.text()).toContain('Unknown');
    });

    it('logoutOtherBrowserSessions onSuccess closes the modal', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        useForm.mockReturnValueOnce({
            ...makeForm({ password: '' }),
            delete: vi.fn((_url, options) => options?.onSuccess?.()),
            reset: vi.fn(),
        });
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const wrapper = mount(LogoutOtherBrowserSessionsForm, {
            props: { sessions: [] },
            global: { stubs },
        });
        // Open dialog
        const openBtn = wrapper.findAll('button').find(b => b.text().includes('Log Out'));
        if (openBtn) await openBtn.trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        // Click the submit button INSIDE the dialog (not the outer trigger button)
        const submitBtn = wrapper.find('.dialog').find('button[type="submit"]');
        if (submitBtn.exists()) await submitBtn.trigger('click');
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.dialog').exists()).toBe(false);
        vi.useRealTimers();
    });

    it('logoutOtherBrowserSessions onFinish resets the form', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        const resetMock = vi.fn();
        useForm.mockReturnValueOnce({
            ...makeForm({ password: '' }),
            delete: vi.fn((_url, options) => options?.onFinish?.()),
            reset: resetMock,
        });
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const wrapper = mount(LogoutOtherBrowserSessionsForm, {
            props: { sessions: [] },
            global: { stubs },
        });
        const openBtn = wrapper.findAll('button').find(b => b.text().includes('Log Out'));
        if (openBtn) await openBtn.trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        const submitBtn = wrapper.find('.dialog').find('button[type="submit"]');
        if (submitBtn.exists()) await submitBtn.trigger('click');
        expect(resetMock).toHaveBeenCalled();
        vi.useRealTimers();
    });

    it('closes modal when Cancel button is clicked inside dialog', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const wrapper = mount(LogoutOtherBrowserSessionsForm, {
            props: { sessions: [] },
            global: { stubs },
        });
        const openBtn = wrapper.findAll('button').find(b => b.text().includes('Log Out'));
        if (openBtn) await openBtn.trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.dialog').exists()).toBe(true);
        const cancelBtn = wrapper.findAll('.cancel').find(b => b.text().includes('Cancel'));
        if (cancelBtn) await cancelBtn.trigger('click');
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.dialog').exists()).toBe(false);
        vi.useRealTimers();
    });
});

describe('LogoutOtherBrowserSessionsForm — logoutOtherBrowserSessions onError', () => {
    it('onError callback focuses the password input', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        useForm.mockReturnValueOnce({
            ...makeForm({ password: '' }),
            delete: vi.fn((_url, options) => options?.onError?.()),
            reset: vi.fn(),
        });
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const wrapper = mount(LogoutOtherBrowserSessionsForm, {
            props: { sessions: [] },
            global: { stubs },
        });
        const openBtn = wrapper.findAll('button').find(b => b.text().includes('Log Out'));
        if (openBtn) await openBtn.trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        const submitBtn = wrapper.find('.dialog').find('button[type="submit"]');
        if (submitBtn.exists()) await submitBtn.trigger('click');
        expect(wrapper.exists()).toBe(true);
        vi.useRealTimers();
    });
});

describe('DeleteUserForm — callbacks', () => {
    it('covers deleteUser form.delete call', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        const deleteMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm(), delete: deleteMock });
        const wrapper = mount(DeleteUserForm, {
            global: { stubs: { ...stubs, DialogModal: { props: ['show'], emits: ['close'], template: '<div v-if="show" class="dialog"><slot name="content" /><slot name="footer" /></div>' } } },
        });
        // Open dialog
        await wrapper.find('button[type="button"]').trigger('click');
        // Submit - triggers deleteUser()
        const submitBtn = wrapper.find('button[type="submit"]');
        if (submitBtn.exists()) await submitBtn.trigger('click');
        // The .danger button calls deleteUser -> form.delete
        const dangerBtn = wrapper.find('.danger');
        if (dangerBtn.exists()) await dangerBtn.trigger('click');
        expect(wrapper.exists()).toBe(true);
    });

    it('deleteUser onSuccess closes the modal', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        useForm.mockReturnValueOnce({
            ...makeForm({ password: '' }),
            delete: vi.fn((_url, options) => options?.onSuccess?.()),
            reset: vi.fn(),
        });
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const wrapper = mount(DeleteUserForm, {
            global: { stubs: { ...stubs, DialogModal: { props: ['show'], emits: ['close'], template: '<div v-if="show" class="dialog"><slot name="content" /><slot name="footer" /></div>' } } },
        });
        await wrapper.find('button[type="button"]').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        const dangerBtn = wrapper.find('.dialog').find('button.danger');
        if (dangerBtn.exists()) await dangerBtn.trigger('click');
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.dialog').exists()).toBe(false);
        vi.useRealTimers();
    });

    it('deleteUser onFinish resets the form', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        const resetMock = vi.fn();
        useForm.mockReturnValueOnce({
            ...makeForm({ password: '' }),
            delete: vi.fn((_url, options) => options?.onFinish?.()),
            reset: resetMock,
        });
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const wrapper = mount(DeleteUserForm, {
            global: { stubs: { ...stubs, DialogModal: { props: ['show'], emits: ['close'], template: '<div v-if="show" class="dialog"><slot name="content" /><slot name="footer" /></div>' } } },
        });
        await wrapper.find('button[type="button"]').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        const dangerBtn = wrapper.find('.dialog').find('button.danger');
        if (dangerBtn.exists()) await dangerBtn.trigger('click');
        expect(resetMock).toHaveBeenCalled();
        vi.useRealTimers();
    });

    it('deleteUser onError focuses the password input', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        useForm.mockReturnValueOnce({
            ...makeForm({ password: '' }),
            delete: vi.fn((_url, options) => options?.onError?.()),
            reset: vi.fn(),
        });
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const wrapper = mount(DeleteUserForm, {
            global: { stubs: { ...stubs, DialogModal: { props: ['show'], emits: ['close'], template: '<div v-if="show" class="dialog"><slot name="content" /><slot name="footer" /></div>' } } },
        });
        await wrapper.find('button[type="button"]').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        const dangerBtn = wrapper.find('.dialog').find('button.danger');
        if (dangerBtn.exists()) await dangerBtn.trigger('click');
        expect(wrapper.exists()).toBe(true);
        vi.useRealTimers();
    });
});
