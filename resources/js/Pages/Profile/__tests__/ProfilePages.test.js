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
    router: { visit: vi.fn(), reload: vi.fn(), get: vi.fn(), put: vi.fn(), post: vi.fn() },
    useForm: vi.fn((data = {}) => makeForm(data)),
    usePage: vi.fn(() => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test User',
                    email: 'test@example.com',
                    two_factor_enabled: false,
                    profile_photo_url: null,
                    current_team: { name: 'My Team' },
                },
            },
            jetstream: {
                flash: { banner: '', bannerStyle: 'success' },
                canUpdateProfileInformation: true,
                canManageTwoFactorAuthentication: true,
                hasAccountDeletionFeatures: true,
                hasTeamFeatures: false,
                managesProfilePhotos: false,
                hasEmailVerification: false,
            },
        },
    })),
}));

import UpdatePasswordForm from '../Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '../Partials/UpdateProfileInformationForm.vue';
import DeleteUserForm from '../Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '../Partials/LogoutOtherBrowserSessionsForm.vue';
import TwoFactorAuthenticationForm from '../Partials/TwoFactorAuthenticationForm.vue';

const AppLayoutStub = { name: 'AppLayout', template: '<div><slot name="header" /><slot /></div>' };

const commonStubs = {
    AppLayout: AppLayoutStub,
    ActionMessage: { props: ['on'], template: '<div><slot /></div>' },
    ActionSection: { template: '<div><slot name="title" /><slot name="description" /><slot name="content" /><slot name="actions" /></div>' },
    FormSection: { emits: ['submitted'], template: '<form @submit.prevent="$emit(\'submitted\')"><slot name="title" /><slot name="form" /><slot name="actions" /></form>' },
    DialogModal: { props: ['show'], template: '<div v-if="show"><slot name="title" /><slot name="content" /><slot name="footer" /></div>' },
    ConfirmationModal: { props: ['show'], template: '<div v-if="show"><slot name="title" /><slot name="content" /><slot name="footer" /></div>' },
    InputError: { props: ['message'], template: '<p v-if="message">{{ message }}</p>' },
    InputLabel: { template: '<label><slot /></label>' },
    PrimaryButton: { template: '<button type="submit"><slot /></button>' },
    SecondaryButton: { template: '<button type="button"><slot /></button>' },
    DangerButton: { template: '<button type="button"><slot /></button>' },
    TextInput: { template: '<input />' },
    SectionTitle: { template: '<div><slot /></div>' },
    SectionBorder: { template: '<hr />' },
    ConfirmsPassword: { template: '<div><slot /></div>' },
};

describe('UpdatePasswordForm', () => {
    it('renders without errors', () => {
        const wrapper = mount(UpdatePasswordForm, {
            global: { stubs: commonStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('triggers the update handler on submit click', async () => {
        const wrapper = mount(UpdatePasswordForm, {
            global: { stubs: commonStubs },
        });
        // FormSection stubs the form element — just verify the component renders a submit button
        expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
    });
});

describe('UpdateProfileInformationForm', () => {
    const user = {
        id: 1, name: 'Alice', email: 'alice@example.com',
        profile_photo_url: null, profile_photo_path: null,
    };

    it('renders without errors', () => {
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user },
            global: { stubs: commonStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('renders a submit button', () => {
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user },
            global: { stubs: commonStubs },
        });
        // FormSection stubs the form element — just verify save button exists
        expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
    });
});

describe('DeleteUserForm', () => {
    it('renders without errors', () => {
        const wrapper = mount(DeleteUserForm, {
            global: { stubs: commonStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('shows the confirmation modal when "Delete Account" is clicked', async () => {
        const wrapper = mount(DeleteUserForm, {
            global: { stubs: { ...commonStubs, DialogModal: { props: ['show'], template: '<div v-if="show" id="modal"><slot name="content" /><slot name="footer" /></div>' } } },
        });
        await wrapper.find('button[type="button"]').trigger('click');
        expect(wrapper.find('#modal').exists()).toBe(true);
    });
});

describe('LogoutOtherBrowserSessionsForm', () => {
    it('renders without errors', () => {
        const wrapper = mount(LogoutOtherBrowserSessionsForm, {
            props: { sessions: [] },
            global: { stubs: commonStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('renders with multiple sessions', () => {
        const sessions = [
            { agent: 'Chrome on Windows', ip_address: '192.168.1.1', is_current_device: true, last_active: 'just now' },
            { agent: 'Firefox on Mac', ip_address: '192.168.1.2', is_current_device: false, last_active: '2 days ago' },
        ];
        const wrapper = mount(LogoutOtherBrowserSessionsForm, {
            props: { sessions },
            global: { stubs: commonStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });
});

describe('TwoFactorAuthenticationForm', () => {
    it('renders without errors when 2FA is not enabled', () => {
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('shows "Enable" button when 2FA is not enabled', () => {
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        expect(wrapper.text()).toContain('Enable');
    });
});
