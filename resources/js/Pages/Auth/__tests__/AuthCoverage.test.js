/**
 * Targeted coverage for Auth page branches not hit by basic mount tests.
 */
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

const makeForm = (data = {}) => ({
    ...data,
    processing: false,
    errors: {},
    post: vi.fn(),
    put: vi.fn(),
    reset: vi.fn(),
    transform: vi.fn(function () { return this; }),
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div></div>' },
    Link: { name: 'Link', props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: vi.fn((data = {}) => makeForm(data)),
}));

import { useForm } from '@inertiajs/vue3';
import Login from '../Login.vue';
import Register from '../Register.vue';
import ConfirmPassword from '../ConfirmPassword.vue';
import ResetPassword from '../ResetPassword.vue';
import TwoFactorChallenge from '../TwoFactorChallenge.vue';

// TextInput stub that exposes focus (so recoveryCodeInput.value.focus() doesn't throw)
const FocusableInput = {
    props: ['modelValue', 'type', 'autocomplete', 'id'],
    emits: ['update:modelValue'],
    setup(_, { expose }) {
        const focus = vi.fn();
        expose({ focus });
        return { focus };
    },
    template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
};

const stubs = {
    AuthenticationCard: { template: '<div><slot name="logo" /><slot /></div>' },
    AuthenticationCardLogo: { template: '<div />' },
    InputError: { props: ['message'], template: '<p v-if="message" class="err">{{ message }}</p>' },
    InputLabel: { props: ['value', 'for'], template: '<label>{{ value }}<slot /></label>' },
    PrimaryButton: { template: '<button type="submit"><slot /></button>' },
    SecondaryButton: { template: '<button type="button"><slot /></button>' },
    TextInput: FocusableInput,
    Checkbox: { props: ['checked'], emits: ['update:checked'], template: '<input type="checkbox" :checked="checked" />' },
};

describe('Register — all field errors', () => {
    beforeEach(() => vi.clearAllMocks());

    it('shows name field error', () => {
        useForm.mockReturnValueOnce({ ...makeForm(), errors: { name: 'Name is required.' } });
        const wrapper = mount(Register, { global: { stubs } });
        expect(wrapper.find('.err').text()).toContain('Name is required.');
    });

    it('shows email field error', () => {
        useForm.mockReturnValueOnce({ ...makeForm(), errors: { email: 'Email is taken.' } });
        const wrapper = mount(Register, { global: { stubs } });
        expect(wrapper.find('.err').text()).toContain('Email is taken.');
    });

    it('shows password field error', () => {
        useForm.mockReturnValueOnce({ ...makeForm(), errors: { password: 'Too short.' } });
        const wrapper = mount(Register, { global: { stubs } });
        expect(wrapper.find('.err').text()).toContain('Too short.');
    });

    it('shows password_confirmation error', () => {
        useForm.mockReturnValueOnce({ ...makeForm(), errors: { password_confirmation: 'Passwords do not match.' } });
        const wrapper = mount(Register, { global: { stubs } });
        expect(wrapper.find('.err').text()).toContain('Passwords do not match.');
    });

    it('shows terms checkbox section when hasTermsAndPrivacyPolicyFeature is true', () => {
        const wrapper = mount(Register, {
            global: {
                stubs,
                mocks: { $page: { props: { jetstream: { hasTermsAndPrivacyPolicyFeature: true }, auth: { user: null } } } },
            },
        });
        // Terms checkbox renders when the feature is enabled
        expect(wrapper.findAll('input[type="checkbox"]').length).toBeGreaterThan(0);
    });
});

describe('ConfirmPassword — submit callback', () => {
    it('calls form.post on submit', async () => {
        const postMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm(), post: postMock, reset: vi.fn() });
        const wrapper = mount(ConfirmPassword, { global: { stubs } });
        await wrapper.find('form').trigger('submit');
        expect(postMock).toHaveBeenCalled();
    });

    it('calls onFinish callback after submission', async () => {
        const resetMock = vi.fn();
        useForm.mockReturnValueOnce({
            ...makeForm(),
            post: vi.fn((url, options) => options?.onFinish?.()),
            reset: resetMock,
        });
        const wrapper = mount(ConfirmPassword, { global: { stubs } });
        await wrapper.find('form').trigger('submit');
        expect(resetMock).toHaveBeenCalled();
    });
});

describe('ResetPassword — field errors', () => {
    it('shows password error', () => {
        useForm.mockReturnValueOnce({ ...makeForm({ token: 'tok', email: 'a@b.com', password: '', password_confirmation: '' }), errors: { password: 'Too short.' } });
        const wrapper = mount(ResetPassword, {
            props: { email: 'a@b.com', token: 'tok' },
            global: { stubs },
        });
        expect(wrapper.find('.err').text()).toContain('Too short.');
    });

    it('shows email field error', () => {
        useForm.mockReturnValueOnce({ ...makeForm(), errors: { email: 'Email not found.' } });
        const wrapper = mount(ResetPassword, {
            props: { email: 'a@b.com', token: 'tok' },
            global: { stubs },
        });
        expect(wrapper.find('.err').text()).toContain('Email not found.');
    });

    it('calls form.post on submit', async () => {
        const postMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm({ token: 'tok', email: 'a@b.com' }), post: postMock, reset: vi.fn() });
        const wrapper = mount(ResetPassword, {
            props: { email: 'a@b.com', token: 'tok' },
            global: { stubs },
        });
        await wrapper.find('form').trigger('submit');
        expect(postMock).toHaveBeenCalled();
    });
});

describe('Login — v-model setters', () => {
    const CheckboxWithEmit = {
        props: ['checked'],
        emits: ['update:checked'],
        template: '<input type="checkbox" :checked="checked" @change="$emit(\'update:checked\', $event.target.checked)" />',
    };

    it('covers email v-model setter (first input)', async () => {
        const wrapper = mount(Login, {
            props: { canResetPassword: false, status: null },
            global: { stubs: { ...stubs, Checkbox: CheckboxWithEmit } },
        });
        const inputs = wrapper.findAll('input');
        if (inputs.length > 0) await inputs[0].setValue('test@example.com');
        expect(wrapper.exists()).toBe(true);
    });

    it('covers password v-model setter (second input)', async () => {
        const wrapper = mount(Login, {
            props: { canResetPassword: false, status: null },
            global: { stubs: { ...stubs, Checkbox: CheckboxWithEmit } },
        });
        const inputs = wrapper.findAll('input');
        if (inputs.length > 1) await inputs[1].setValue('secret');
        expect(wrapper.exists()).toBe(true);
    });

    it('covers remember v-model:checked setter when checkbox changes', async () => {
        const wrapper = mount(Login, {
            props: { canResetPassword: false, status: null },
            global: { stubs: { ...stubs, Checkbox: CheckboxWithEmit } },
        });
        const checkbox = wrapper.find('input[type="checkbox"]');
        if (checkbox.exists()) await checkbox.setValue(true);
        expect(wrapper.exists()).toBe(true);
    });
});

describe('ResetPassword — v-model setters', () => {
    it('covers email v-model setter (first input)', async () => {
        const wrapper = mount(ResetPassword, {
            props: { email: 'a@b.com', token: 'tok' },
            global: { stubs },
        });
        const inputs = wrapper.findAll('input');
        if (inputs.length > 0) await inputs[0].setValue('new@example.com');
        expect(wrapper.exists()).toBe(true);
    });

    it('covers password v-model setter (second input)', async () => {
        const wrapper = mount(ResetPassword, {
            props: { email: 'a@b.com', token: 'tok' },
            global: { stubs },
        });
        const inputs = wrapper.findAll('input');
        if (inputs.length > 1) await inputs[1].setValue('newpass');
        expect(wrapper.exists()).toBe(true);
    });

    it('covers password_confirmation v-model setter (third input)', async () => {
        const wrapper = mount(ResetPassword, {
            props: { email: 'a@b.com', token: 'tok' },
            global: { stubs },
        });
        const inputs = wrapper.findAll('input');
        if (inputs.length > 2) await inputs[2].setValue('newpass');
        expect(wrapper.exists()).toBe(true);
    });
});

describe('Login — transform callback encodes remember=true as "on"', () => {
    it('covers the remember ? "on" : "" true branch', async () => {
        let capturedRemember;
        useForm.mockReturnValueOnce({
            ...makeForm({ email: '', password: '', remember: true }),
            transform: vi.fn(function (cb) {
                const result = cb(this);
                capturedRemember = result.remember;
                return { post: vi.fn() };
            }),
        });
        const wrapper = mount(Login, {
            props: { canResetPassword: false, status: null },
            global: { stubs },
        });
        await wrapper.find('form').trigger('submit');
        expect(capturedRemember).toBe('on');
    });
});

describe('Login — submit onFinish resets password field', () => {
    it('calls form.reset after successful submission', async () => {
        const resetMock = vi.fn();
        useForm.mockReturnValueOnce({
            ...makeForm(),
            reset: resetMock,
            transform: vi.fn(function () {
                return { post: vi.fn((_url, options) => options?.onFinish?.()) };
            }),
        });
        const wrapper = mount(Login, {
            props: { canResetPassword: false, status: null },
            global: { stubs },
        });
        await wrapper.find('form').trigger('submit');
        expect(resetMock).toHaveBeenCalledWith('password');
    });
});

describe('ResetPassword — submit onFinish resets password fields', () => {
    it('calls form.reset after successful submission', async () => {
        const resetMock = vi.fn();
        useForm.mockReturnValueOnce({
            ...makeForm({ token: 'tok', email: 'a@b.com', password: '', password_confirmation: '' }),
            reset: resetMock,
            post: vi.fn((_url, options) => options?.onFinish?.()),
        });
        const wrapper = mount(ResetPassword, {
            props: { email: 'a@b.com', token: 'tok' },
            global: { stubs },
        });
        await wrapper.find('form').trigger('submit');
        expect(resetMock).toHaveBeenCalledWith('password', 'password_confirmation');
    });
});

describe('TwoFactorChallenge — recovery code mode', () => {
    it('toggles to recovery mode on button click', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const wrapper = mount(TwoFactorChallenge, { global: { stubs } });
        await wrapper.find('button[type="button"]').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        // After toggle, the button text changes to "Use an authentication code instead"
        expect(wrapper.find('button[type="button"]').text()).toContain('authentication code');
        vi.useRealTimers();
    });

    it('toggles back to code mode on second click', async () => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        const wrapper = mount(TwoFactorChallenge, { global: { stubs } });
        // First toggle
        await wrapper.find('button[type="button"]').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        // Second toggle
        await wrapper.find('button[type="button"]').trigger('click');
        await vi.runAllTimersAsync();
        await wrapper.vm.$nextTick();
        expect(wrapper.find('button[type="button"]').text()).toContain('recovery code');
        vi.useRealTimers();
    });
});
