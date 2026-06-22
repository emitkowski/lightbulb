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
import TwoFactorChallenge from '../TwoFactorChallenge.vue';

const authCardStubs = {
    AuthenticationCard: { template: '<div><slot name="logo" /><slot /></div>' },
    AuthenticationCardLogo: { template: '<div />' },
    InputError: { props: ['message'], template: '<p v-if="message" class="err">{{ message }}</p>' },
    InputLabel: { props: ['value'], template: '<label>{{ value }}<slot /></label>' },
    PrimaryButton: { template: '<button type="submit"><slot /></button>' },
    SecondaryButton: { template: '<button type="button"><slot /></button>' },
    TextInput: {
        props: ['modelValue'],
        emits: ['update:modelValue'],
        template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
    },
    Checkbox: { props: ['checked'], template: '<input type="checkbox" :checked="checked" />' },
};

describe('Login — form interactions', () => {
    beforeEach(() => vi.clearAllMocks());

    it('shows Email and Password labels', () => {
        const wrapper = mount(Login, {
            props: { canResetPassword: true, status: null },
            global: { stubs: authCardStubs },
        });
        expect(wrapper.text()).toContain('Email');
        expect(wrapper.text()).toContain('Password');
    });

    it('shows form processing state (opacity class)', () => {
        useForm.mockReturnValueOnce({
            ...makeForm(),
            processing: true,
            transform: vi.fn(function () { return this; }),
        });
        const wrapper = mount(Login, {
            props: { canResetPassword: true, status: null },
            global: { stubs: authCardStubs },
        });
        expect(wrapper.find('button[type="submit"]').attributes('class')).toContain('opacity-25');
    });

    it('shows validation errors on email field', () => {
        useForm.mockReturnValueOnce({
            ...makeForm({ email: '', password: '' }),
            errors: { email: 'The email is required.', password: '' },
            transform: vi.fn(function () { return this; }),
        });
        const wrapper = mount(Login, {
            props: { canResetPassword: true, status: null },
            global: { stubs: authCardStubs },
        });
        expect(wrapper.find('.err').text()).toContain('The email is required.');
    });

    it('shows validation errors on password field', () => {
        useForm.mockReturnValueOnce({
            ...makeForm({ email: 'a@b.com', password: '' }),
            errors: { email: '', password: 'The password is required.' },
            transform: vi.fn(function () { return this; }),
        });
        const wrapper = mount(Login, {
            props: { canResetPassword: true, status: null },
            global: { stubs: authCardStubs },
        });
        expect(wrapper.find('.err').text()).toContain('The password is required.');
    });
});

describe('Register — form', () => {
    beforeEach(() => vi.clearAllMocks());

    it('shows Name, Email, Password labels', () => {
        const wrapper = mount(Register, {
            global: { stubs: authCardStubs },
        });
        expect(wrapper.text()).toContain('Name');
        expect(wrapper.text()).toContain('Email');
        expect(wrapper.text()).toContain('Password');
    });

    it('shows Terms checkbox when hasTermsAndPrivacyPolicyFeature is true', () => {
        const wrapper = mount(Register, {
            global: {
                stubs: authCardStubs,
                mocks: {
                    $page: {
                        props: {
                            jetstream: { hasTermsAndPrivacyPolicyFeature: true },
                            auth: { user: null },
                        },
                    },
                },
            },
        });
        expect(wrapper.findAll('input[type="checkbox"]').length).toBeGreaterThan(0);
    });

    it('shows validation errors', () => {
        useForm.mockReturnValueOnce({
            ...makeForm(),
            errors: { name: 'Name is required.', email: '', password: '' },
        });
        const wrapper = mount(Register, {
            global: { stubs: authCardStubs },
        });
        expect(wrapper.find('.err').text()).toContain('Name is required.');
    });

    it('disables submit while processing', () => {
        useForm.mockReturnValueOnce({ ...makeForm(), processing: true });
        const wrapper = mount(Register, {
            global: { stubs: authCardStubs },
        });
        expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined();
    });

    it('shows Confirm Password field', () => {
        const wrapper = mount(Register, {
            global: { stubs: authCardStubs },
        });
        expect(wrapper.text()).toContain('Confirm Password');
    });
});

describe('TwoFactorChallenge — form', () => {
    const FocusableInput = {
        props: ['modelValue'],
        emits: ['update:modelValue'],
        setup() { return { focus: vi.fn() }; },
        expose: ['focus'],
        template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
    };

    beforeEach(() => vi.clearAllMocks());

    it('shows Authentication Code label', () => {
        const wrapper = mount(TwoFactorChallenge, {
            global: { stubs: { ...authCardStubs, TextInput: FocusableInput } },
        });
        expect(wrapper.text()).toContain('Code');
    });

    it('shows recovery code toggle button', () => {
        const wrapper = mount(TwoFactorChallenge, {
            global: { stubs: { ...authCardStubs, TextInput: FocusableInput } },
        });
        expect(wrapper.find('button[type="button"]').exists()).toBe(true);
    });

    it('submits authentication code on form submit', async () => {
        const postMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm(), post: postMock });
        const wrapper = mount(TwoFactorChallenge, {
            global: { stubs: { ...authCardStubs, TextInput: FocusableInput } },
        });
        await wrapper.find('form').trigger('submit');
        expect(postMock).toHaveBeenCalled();
    });
});
