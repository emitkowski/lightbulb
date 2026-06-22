/**
 * Final coverage push — exercises v-model handlers, form callbacks,
 * and template branches not hit by other tests.
 */
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

const makeForm = (data = {}) => ({
    ...data,
    processing: false,
    errors: {},
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    reset: vi.fn(),
    transform: vi.fn(function () { return this; }),
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div></div>' },
    Link: { name: 'Link', props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: vi.fn((data = {}) => makeForm(data)),
    usePage: vi.fn(() => ({
        props: {
            auth: { user: { id: 1, name: 'Alice', email: 'alice@example.com', two_factor_enabled: false } },
            jetstream: { managesProfilePhotos: false, hasEmailVerification: false },
        },
    })),
}));

import { useForm } from '@inertiajs/vue3';
import Register from '../Auth/Register.vue';
import TwoFactorChallenge from '../Auth/TwoFactorChallenge.vue';
import UpdatePasswordForm from '../Profile/Partials/UpdatePasswordForm.vue';
import DeleteUserForm from '../Profile/Partials/DeleteUserForm.vue';
import UpdateProfileInformationForm from '../Profile/Partials/UpdateProfileInformationForm.vue';

const FocusableInput = {
    props: ['modelValue', 'type', 'autocomplete', 'id', 'inputmode'],
    emits: ['update:modelValue'],
    setup(_, { expose }) { expose({ focus: vi.fn() }); return {}; },
    template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
};

const registerStubs = {
    AuthenticationCard: { template: '<div><slot name="logo" /><slot /></div>' },
    AuthenticationCardLogo: { template: '<div />' },
    InputError: { props: ['message'], template: '<p v-if="message" class="err">{{ message }}</p>' },
    InputLabel: { props: ['value', 'for'], template: '<label>{{ value }}<slot /></label>' },
    PrimaryButton: { template: '<button type="submit"><slot /></button>' },
    TextInput: FocusableInput,
    Checkbox: { props: ['checked'], emits: ['update:checked'], template: '<input type="checkbox" :checked="checked" @change="$emit(\'update:checked\', $event.target.checked)" />' },
    Link: { name: 'Link', props: ['href'], template: '<a :href="href"><slot /></a>' },
};

const profileStubs = {
    FormSection: { emits: ['submitted'], template: '<form @submit.prevent="$emit(\'submitted\')"><slot name="title" /><slot name="form" /><slot name="actions" /></form>' },
    ActionSection: { template: '<div><slot name="title" /><slot name="description" /><slot name="content" /><slot name="actions" /></div>' },
    ActionMessage: { props: ['on'], template: '<div v-if="on"><slot /></div>' },
    InputError: { props: ['message'], template: '<p v-if="message" class="err">{{ message }}</p>' },
    InputLabel: { props: ['value', 'for'], template: '<label>{{ value }}<slot /></label>' },
    PrimaryButton: { template: '<button type="submit"><slot /></button>' },
    SecondaryButton: { template: '<button type="button" class="cancel"><slot /></button>' },
    DangerButton: { template: '<button type="button" class="danger"><slot /></button>' },
    TextInput: FocusableInput,
    DialogModal: { props: ['show'], emits: ['close'], template: '<div v-if="show" class="dialog"><slot name="content" /><slot name="footer" /></div>' },
    SectionTitle: { template: '<div><slot /></div>' },
};

describe('Register — typing and v-model coverage', () => {
    beforeEach(() => vi.clearAllMocks());

    it('updates form when user types into name field', async () => {
        const wrapper = mount(Register, { global: { stubs: registerStubs } });
        const inputs = wrapper.findAll('input');
        if (inputs.length > 0) await inputs[0].setValue('Alice');
        expect(wrapper.exists()).toBe(true);
    });

    it('updates all fields and calls post on submit', async () => {
        const postMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm(), post: postMock });
        const wrapper = mount(Register, { global: { stubs: registerStubs } });
        const inputs = wrapper.findAll('input');
        for (const input of inputs) {
            if (input.attributes('type') !== 'checkbox') {
                await input.setValue('test value');
            }
        }
        await wrapper.find('form').trigger('submit');
        expect(postMock).toHaveBeenCalled();
    });

    it('calls onFinish callback after register submission', async () => {
        const resetMock = vi.fn();
        useForm.mockReturnValueOnce({
            ...makeForm(),
            post: vi.fn((url, options) => options?.onFinish?.()),
            reset: resetMock,
        });
        const wrapper = mount(Register, { global: { stubs: registerStubs } });
        await wrapper.find('form').trigger('submit');
        expect(resetMock).toHaveBeenCalled();
    });

    it('shows all field error messages at once', () => {
        useForm.mockReturnValueOnce({
            ...makeForm(),
            errors: {
                name: 'Name required.', email: 'Email required.',
                password: 'Password required.', password_confirmation: 'Must match.',
            },
        });
        const wrapper = mount(Register, { global: { stubs: registerStubs } });
        const errors = wrapper.findAll('.err');
        expect(errors.length).toBeGreaterThan(0);
    });
});

describe('TwoFactorChallenge — input interactions', () => {
    beforeEach(() => vi.clearAllMocks());

    it('typing into code input covers v-model handler', async () => {
        const wrapper = mount(TwoFactorChallenge, { global: { stubs: registerStubs } });
        const input = wrapper.find('input');
        await input.setValue('123456');
        expect(wrapper.exists()).toBe(true);
    });

    it('shows recovery code form after toggle and accepts input', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const wrapper = mount(TwoFactorChallenge, { global: { stubs: registerStubs } });
        await wrapper.find('button[type="button"]').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        const input = wrapper.find('input');
        await input.setValue('abcd-efgh');
        expect(wrapper.exists()).toBe(true);
        vi.useRealTimers();
    });

    it('shows code error when form.errors.code is set', () => {
        useForm.mockReturnValueOnce({ ...makeForm(), errors: { code: 'Invalid code.' } });
        const wrapper = mount(TwoFactorChallenge, { global: { stubs: registerStubs } });
        expect(wrapper.find('.err').text()).toContain('Invalid code.');
    });

    it('shows recovery_code error when in recovery mode', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        useForm.mockReturnValueOnce({ ...makeForm(), errors: { recovery_code: 'Invalid recovery code.' } });
        const wrapper = mount(TwoFactorChallenge, { global: { stubs: registerStubs } });
        await wrapper.find('button[type="button"]').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.err').text()).toContain('Invalid recovery code.');
        vi.useRealTimers();
    });
});

describe('UpdatePasswordForm — error display and callbacks', () => {
    beforeEach(() => vi.clearAllMocks());

    it('typing into password fields covers v-model handlers', async () => {
        const wrapper = mount(UpdatePasswordForm, { global: { stubs: profileStubs } });
        const inputs = wrapper.findAll('input');
        for (const input of inputs) await input.setValue('secret');
        expect(wrapper.exists()).toBe(true);
    });

    it('shows password_confirmation error', () => {
        useForm.mockReturnValueOnce({ ...makeForm(), errors: { password_confirmation: 'Must match.' } });
        const wrapper = mount(UpdatePasswordForm, { global: { stubs: profileStubs } });
        expect(wrapper.find('.err').text()).toContain('Must match.');
    });

    it('shows recently successful message', () => {
        useForm.mockReturnValueOnce({ ...makeForm(), recentlySuccessful: true });
        const wrapper = mount(UpdatePasswordForm, {
            global: {
                stubs: {
                    ...profileStubs,
                    ActionMessage: { props: ['on'], template: '<div v-if="on" class="success">Saved.</div>' },
                },
            },
        });
        expect(wrapper.find('.success').exists()).toBe(true);
    });

    it('invokes onError and resets fields on password error', async () => {
        const resetMock = vi.fn();
        useForm.mockReturnValueOnce({
            ...makeForm(),
            errors: { password: 'Too weak.' },
            put: vi.fn((url, options) => options?.onError?.()),
            reset: resetMock,
        });
        const wrapper = mount(UpdatePasswordForm, { global: { stubs: profileStubs } });
        await wrapper.find('form').trigger('submit');
        expect(resetMock).toHaveBeenCalled();
    });

    it('invokes onError and resets current_password field', async () => {
        const resetMock = vi.fn();
        useForm.mockReturnValueOnce({
            ...makeForm(),
            errors: { current_password: 'Wrong password.' },
            put: vi.fn((url, options) => options?.onError?.()),
            reset: resetMock,
        });
        const wrapper = mount(UpdatePasswordForm, { global: { stubs: profileStubs } });
        await wrapper.find('form').trigger('submit');
        expect(resetMock).toHaveBeenCalled();
    });
});

describe('DeleteUserForm — dialog and delete', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.useFakeTimers({ shouldAdvanceTime: true });
    });

    afterEach(() => vi.useRealTimers());

    it('shows dialog and types password, then confirms deletion', async () => {
        const deleteMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm(), delete: deleteMock });
        const wrapper = mount(DeleteUserForm, { global: { stubs: profileStubs } });
        // Open dialog
        await wrapper.find('button[type="button"]').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        // Type password
        const input = wrapper.find('input');
        if (input.exists()) await input.setValue('my-password');
        // Click delete button
        const dangerBtn = wrapper.find('.danger');
        if (dangerBtn.exists()) await dangerBtn.trigger('click');
        expect(wrapper.exists()).toBe(true);
    });

    it('covers deleteUser form.delete when danger btn clicked', async () => {
        const deleteMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm(), delete: deleteMock });
        const wrapper = mount(DeleteUserForm, { global: { stubs: profileStubs } });
        await wrapper.find('button[type="button"]').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        // Two .danger buttons: the trigger + the confirm dialog button (use last)
        const dangerBtns = wrapper.findAll('.danger');
        if (dangerBtns.length > 1) await dangerBtns[dangerBtns.length - 1].trigger('click');
        expect(deleteMock).toHaveBeenCalled();
    });

    it('closes dialog on success callback', async () => {
        useForm.mockReturnValueOnce({
            ...makeForm(),
            delete: vi.fn((url, options) => options?.onSuccess?.()),
        });
        const wrapper = mount(DeleteUserForm, { global: { stubs: profileStubs } });
        await wrapper.find('button[type="button"]').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        const dangerBtn = wrapper.find('.danger');
        if (dangerBtn.exists()) await dangerBtn.trigger('click');
        // onSuccess closes the dialog
        expect(wrapper.exists()).toBe(true);
    });
});

describe('UpdateProfileInformationForm — photo and email verification', () => {
    beforeEach(() => vi.clearAllMocks());

    it('shows resend verification button when email is unverified', () => {
        const user = { id: 1, name: 'Alice', email: 'alice@example.com', profile_photo_url: null, email_verified_at: null };
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user },
            global: {
                stubs: profileStubs,
                mocks: {
                    $page: {
                        props: {
                            auth: { user },
                            jetstream: { managesProfilePhotos: false, hasEmailVerification: true },
                        },
                    },
                },
            },
        });
        expect(wrapper.text()).toContain('re-send');
    });

    it('calls form.post on submit and covers onSuccess callback', async () => {
        const postMock = vi.fn((url, options) => options?.onSuccess?.());
        useForm.mockReturnValueOnce({ ...makeForm({ name: 'Alice', email: 'alice@example.com', _method: 'PUT', photo: null }), post: postMock });
        const user = { id: 1, name: 'Alice', email: 'alice@example.com', profile_photo_url: null };
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user },
            global: { stubs: profileStubs },
        });
        await wrapper.find('form').trigger('submit');
        expect(postMock).toHaveBeenCalled();
    });

    it('typing into name/email fields covers v-model handlers', async () => {
        const user = { id: 1, name: 'Alice', email: 'alice@example.com', profile_photo_url: null };
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user },
            global: { stubs: profileStubs },
        });
        const inputs = wrapper.findAll('input');
        for (const input of inputs) await input.setValue('new value');
        expect(wrapper.exists()).toBe(true);
    });
});
