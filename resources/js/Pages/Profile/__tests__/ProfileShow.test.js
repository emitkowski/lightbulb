import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(() => ({
        props: {
            auth: { user: { id: 1, name: 'Alice', email: 'alice@example.com' } },
            jetstream: {
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

import ProfileShow from '../Show.vue';

const AppLayoutStub = { name: 'AppLayout', template: '<div><slot name="header" /><slot /></div>' };

const stubs = {
    AppLayout: AppLayoutStub,
    UpdateProfileInformationForm: { template: '<div id="update-profile" />' },
    LogoutOtherBrowserSessionsForm: { props: ['sessions'], template: '<div id="sessions-form" />' },
    TwoFactorAuthenticationForm: { props: ['requiresConfirmation'], template: '<div id="two-factor" />' },
    UpdatePasswordForm: { template: '<div id="update-password" />' },
    DeleteUserForm: { template: '<div id="delete-user" />' },
    SectionBorder: { template: '<hr />' },
};

describe('Profile/Show', () => {
    it('renders without errors', () => {
        const wrapper = mount(ProfileShow, {
            props: { confirmsTwoFactorAuthentication: false, sessions: [] },
            global: { stubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('shows Profile header', () => {
        const wrapper = mount(ProfileShow, {
            props: { confirmsTwoFactorAuthentication: false, sessions: [] },
            global: { stubs },
        });
        expect(wrapper.text()).toContain('Profile');
    });

    it('renders update profile form when jetstream.canUpdateProfileInformation is true', () => {
        const wrapper = mount(ProfileShow, {
            props: { confirmsTwoFactorAuthentication: false, sessions: [] },
            global: {
                stubs,
                mocks: {
                    $page: {
                        props: {
                            auth: { user: { id: 1, name: 'Alice', email: 'a@example.com' } },
                            jetstream: {
                                canUpdateProfileInformation: true,
                                canManageTwoFactorAuthentication: false,
                                hasAccountDeletionFeatures: false,
                            },
                        },
                    },
                },
            },
        });
        expect(wrapper.find('#update-profile').exists()).toBe(true);
    });

    it('renders two-factor form when canManageTwoFactorAuthentication is true', () => {
        const wrapper = mount(ProfileShow, {
            props: { confirmsTwoFactorAuthentication: true, sessions: [] },
            global: {
                stubs,
                mocks: {
                    $page: {
                        props: {
                            auth: { user: { id: 1, name: 'Alice', email: 'a@example.com' } },
                            jetstream: {
                                canUpdateProfileInformation: false,
                                canManageTwoFactorAuthentication: true,
                                hasAccountDeletionFeatures: false,
                            },
                        },
                    },
                },
            },
        });
        expect(wrapper.find('#two-factor').exists()).toBe(true);
    });

    it('renders delete user form when hasAccountDeletionFeatures is true', () => {
        const wrapper = mount(ProfileShow, {
            props: { confirmsTwoFactorAuthentication: false, sessions: [] },
            global: {
                stubs,
                mocks: {
                    $page: {
                        props: {
                            auth: { user: { id: 1, name: 'Alice', email: 'a@example.com' } },
                            jetstream: {
                                canUpdateProfileInformation: false,
                                canManageTwoFactorAuthentication: false,
                                hasAccountDeletionFeatures: true,
                            },
                        },
                    },
                },
            },
        });
        expect(wrapper.find('#delete-user').exists()).toBe(true);
    });

    it('hides update profile form when canUpdateProfileInformation is false', () => {
        const wrapper = mount(ProfileShow, {
            props: { confirmsTwoFactorAuthentication: false, sessions: [] },
            global: {
                stubs,
                mocks: {
                    $page: {
                        props: {
                            auth: { user: { id: 1, name: 'Alice', email: 'a@example.com' } },
                            jetstream: {
                                canUpdateProfileInformation: false,
                                canManageTwoFactorAuthentication: false,
                                hasAccountDeletionFeatures: false,
                            },
                        },
                    },
                },
            },
        });
        expect(wrapper.find('#update-profile').exists()).toBe(false);
    });

    it('hides update password form when canUpdatePassword is false', () => {
        const wrapper = mount(ProfileShow, {
            props: { confirmsTwoFactorAuthentication: false, sessions: [] },
            global: {
                stubs,
                mocks: {
                    $page: {
                        props: {
                            auth: { user: { id: 1, name: 'Alice', email: 'a@example.com' } },
                            jetstream: {
                                canUpdateProfileInformation: false,
                                canUpdatePassword: false,
                                canManageTwoFactorAuthentication: false,
                                hasAccountDeletionFeatures: false,
                            },
                        },
                    },
                },
            },
        });
        expect(wrapper.find('#update-password').exists()).toBe(false);
    });

    it('shows update password form when canUpdatePassword is true', () => {
        const wrapper = mount(ProfileShow, {
            props: { confirmsTwoFactorAuthentication: false, sessions: [] },
            global: {
                stubs,
                mocks: {
                    $page: {
                        props: {
                            auth: { user: { id: 1, name: 'Alice', email: 'a@example.com' } },
                            jetstream: {
                                canUpdateProfileInformation: false,
                                canUpdatePassword: true,
                                canManageTwoFactorAuthentication: false,
                                hasAccountDeletionFeatures: false,
                            },
                        },
                    },
                },
            },
        });
        expect(wrapper.find('#update-password').exists()).toBe(true);
    });
});
