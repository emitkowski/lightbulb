import { describe, it, expect, vi } from 'vitest';
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
    transform: vi.fn(function () { return this; }),
});

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div></div>' },
    Link: { name: 'Link', props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: vi.fn((data = {}) => makeForm(data)),
}));

import Login from '../Login.vue';
import Register from '../Register.vue';
import ForgotPassword from '../ForgotPassword.vue';
import ConfirmPassword from '../ConfirmPassword.vue';
import ResetPassword from '../ResetPassword.vue';
import TwoFactorChallenge from '../TwoFactorChallenge.vue';
import VerifyEmail from '../VerifyEmail.vue';

const authCardStubs = {
    AuthenticationCard: { template: '<div><slot name="logo" /><slot /></div>' },
    AuthenticationCardLogo: { template: '<div />' },
    InputError: { props: ['message'], template: '<p>{{ message }}</p>' },
    InputLabel: { template: '<label><slot /></label>' },
    PrimaryButton: { template: '<button type="submit"><slot /></button>' },
    SecondaryButton: { template: '<button type="button"><slot /></button>' },
    TextInput: { template: '<input />' },
    Checkbox: { template: '<input type="checkbox" />' },
};

describe('Login', () => {
    it('renders login form', () => {
        const wrapper = mount(Login, {
            props: { canResetPassword: true, status: null },
            global: { stubs: authCardStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('shows status message when provided', () => {
        const wrapper = mount(Login, {
            props: { canResetPassword: false, status: 'Your password was reset!' },
            global: { stubs: authCardStubs },
        });
        expect(wrapper.text()).toContain('Your password was reset!');
    });

    it('hides reset password link when canResetPassword is false', () => {
        const wrapper = mount(Login, {
            props: { canResetPassword: false, status: null },
            global: { stubs: authCardStubs },
        });
        expect(wrapper.findAll('a[href="/password.request"]')).toHaveLength(0);
    });

    it('shows reset password link when canResetPassword is true', () => {
        const wrapper = mount(Login, {
            props: { canResetPassword: true, status: null },
            global: { stubs: authCardStubs },
        });
        expect(wrapper.find('a').exists()).toBe(true);
    });

    it('submits the form', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        const postMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm(), transform: vi.fn(function() { return { post: postMock }; }) });
        const wrapper = mount(Login, {
            props: { canResetPassword: false, status: null },
            global: { stubs: authCardStubs },
        });
        await wrapper.find('form').trigger('submit');
        expect(postMock).toHaveBeenCalled();
    });
});

describe('Register', () => {
    it('renders registration form', () => {
        const wrapper = mount(Register, {
            global: { stubs: authCardStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('submits the form', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        const postMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm(), post: postMock });
        const wrapper = mount(Register, {
            global: { stubs: authCardStubs },
        });
        await wrapper.find('form').trigger('submit');
        expect(postMock).toHaveBeenCalled();
    });
});

describe('ForgotPassword', () => {
    it('renders forgot password form', () => {
        const wrapper = mount(ForgotPassword, {
            props: { status: null },
            global: { stubs: authCardStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('shows status message when provided', () => {
        const wrapper = mount(ForgotPassword, {
            props: { status: 'We have emailed your password reset link.' },
            global: { stubs: authCardStubs },
        });
        expect(wrapper.text()).toContain('We have emailed');
    });

    it('submits the form', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        const postMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm(), post: postMock });
        const wrapper = mount(ForgotPassword, {
            props: { status: null },
            global: { stubs: authCardStubs },
        });
        await wrapper.find('form').trigger('submit');
        expect(postMock).toHaveBeenCalled();
    });
});

describe('ConfirmPassword', () => {
    it('renders confirm password form', () => {
        const wrapper = mount(ConfirmPassword, {
            global: { stubs: authCardStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('submits the form', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        const postMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm(), post: postMock, reset: vi.fn() });
        const wrapper = mount(ConfirmPassword, {
            global: { stubs: authCardStubs },
        });
        await wrapper.find('form').trigger('submit');
        expect(postMock).toHaveBeenCalled();
    });
});

describe('ResetPassword', () => {
    it('renders reset password form', () => {
        const wrapper = mount(ResetPassword, {
            props: { email: 'user@example.com', token: 'abc123' },
            global: { stubs: authCardStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('submits the form', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        const postMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm(), post: postMock });
        const wrapper = mount(ResetPassword, {
            props: { email: 'user@example.com', token: 'abc123' },
            global: { stubs: authCardStubs },
        });
        await wrapper.find('form').trigger('submit');
        expect(postMock).toHaveBeenCalled();
    });
});

describe('TwoFactorChallenge', () => {
    it('renders two-factor code form by default', () => {
        const wrapper = mount(TwoFactorChallenge, {
            global: { stubs: authCardStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('shows toggle button for recovery code', () => {
        const wrapper = mount(TwoFactorChallenge, {
            global: { stubs: authCardStubs },
        });
        expect(wrapper.find('button[type="button"]').exists()).toBe(true);
        expect(wrapper.find('button[type="button"]').text()).toContain('recovery code');
    });

    it('submits authentication code form', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        const postMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm(), post: postMock });
        const wrapper = mount(TwoFactorChallenge, {
            global: { stubs: authCardStubs },
        });
        await wrapper.find('form').trigger('submit');
        expect(postMock).toHaveBeenCalled();
    });
});

describe('VerifyEmail', () => {
    it('renders verification page', () => {
        const wrapper = mount(VerifyEmail, {
            props: { status: null },
            global: { stubs: authCardStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('shows success message when verification link was sent', () => {
        const wrapper = mount(VerifyEmail, {
            props: { status: 'verification-link-sent' },
            global: { stubs: authCardStubs },
        });
        expect(wrapper.text()).toContain('A new verification link has been sent');
    });

    it('does not show success message when status is different', () => {
        const wrapper = mount(VerifyEmail, {
            props: { status: null },
            global: { stubs: authCardStubs },
        });
        expect(wrapper.text()).not.toContain('A fresh verification link');
    });

    it('renders resend button', () => {
        const wrapper = mount(VerifyEmail, {
            props: { status: null },
            global: { stubs: authCardStubs },
        });
        expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
        expect(wrapper.find('button[type="submit"]').text()).toContain('Resend');
    });
});
