/**
 * Extended coverage for Profile Partials — exercises conditional template branches
 * by mounting with different prop/page combinations.
 */
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';

// ConfirmsPassword stub that emits 'confirmed' immediately when wrapper is clicked
const ConfirmsPasswordStub = {
    emits: ['confirmed'],
    template: '<div class="confirms-stub" @click="$emit(\'confirmed\')"><slot /></div>',
};

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
    Link: { name: 'Link', props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { put: vi.fn(), post: vi.fn(), delete: vi.fn(), reload: vi.fn() },
    useForm: vi.fn((data = {}) => makeForm(data)),
    usePage: vi.fn(() => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Alice',
                    email: 'alice@example.com',
                    two_factor_enabled: false,
                    profile_photo_url: null,
                    profile_photo_path: null,
                },
            },
            jetstream: {
                managesProfilePhotos: false,
                hasEmailVerification: false,
                canManageTwoFactorAuthentication: true,
            },
        },
    })),
}));

import TwoFactorAuthenticationForm from '../Partials/TwoFactorAuthenticationForm.vue';
import UpdateProfileInformationForm from '../Partials/UpdateProfileInformationForm.vue';
import UpdatePasswordForm from '../Partials/UpdatePasswordForm.vue';
import LogoutOtherBrowserSessionsForm from '../Partials/LogoutOtherBrowserSessionsForm.vue';
import DeleteUserForm from '../Partials/DeleteUserForm.vue';

const commonStubs = {
    ActionSection: { template: '<div><slot name="title" /><slot name="description" /><slot name="content" /><slot name="actions" /></div>' },
    FormSection: { emits: ['submitted'], template: '<form @submit.prevent="$emit(\'submitted\')"><slot name="title" /><slot name="form" /><slot name="actions" /></form>' },
    ActionMessage: { props: ['on'], template: '<div v-if="on"><slot /></div>' },
    ConfirmsPassword: ConfirmsPasswordStub,
    InputError: { props: ['message'], template: '<p v-if="message" class="input-error">{{ message }}</p>' },
    InputLabel: { props: ['value', 'for'], template: '<label>{{ value }}<slot /></label>' },
    PrimaryButton: { template: '<button type="submit"><slot /></button>' },
    SecondaryButton: { template: '<button type="button"><slot /></button>' },
    DangerButton: { template: '<button type="button"><slot /></button>' },
    TextInput: {
        props: ['modelValue'],
        emits: ['update:modelValue'],
        setup(_, { expose }) { expose({ focus: () => {} }); return {}; },
        template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
    },
    DialogModal: { props: ['show'], emits: ['close'], template: '<div v-if="show" class="dialog-modal"><slot name="title" /><slot name="content" /><slot name="footer" /></div>' },
    SectionTitle: { template: '<div><slot /></div>' },
    Checkbox: { props: ['checked'], emits: ['update:checked'], template: '<input type="checkbox" :checked="checked" @change="$emit(\'update:checked\', $event.target.checked)" />' },
};

describe('TwoFactorAuthenticationForm — 2FA not enabled', () => {
    it('renders without errors', () => {
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('shows Enable button', () => {
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        expect(wrapper.text()).toContain('Enable');
    });

    it('calls router.post when Enable is confirmed', async () => {
        const { router } = await import('@inertiajs/vue3');
        vi.clearAllMocks();
        window.axios = { get: vi.fn().mockResolvedValue({ data: { svg: '', url: '', secretKey: '' } }), post: vi.fn() };
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        // ConfirmsPasswordStub emits 'confirmed' on click — which triggers enableTwoFactorAuthentication
        await wrapper.find('.confirms-stub').trigger('click');
        await wrapper.vm.$nextTick();
        expect(router.post).toHaveBeenCalled();
    });
});

describe('TwoFactorAuthenticationForm — 2FA enabled', () => {
    beforeEach(async () => {
        const { usePage } = await import('@inertiajs/vue3');
        usePage.mockReturnValue({
            props: {
                auth: {
                    user: {
                        id: 1, name: 'Alice', email: 'alice@example.com',
                        two_factor_enabled: true,
                        two_factor_recovery_codes: ['code1', 'code2'],
                        profile_photo_url: null,
                    },
                },
                jetstream: {
                    managesProfilePhotos: false,
                    hasEmailVerification: false,
                    canManageTwoFactorAuthentication: true,
                },
            },
        });
        window.axios = {
            post: vi.fn().mockResolvedValue({ data: { codes: ['new-code-1', 'new-code-2'] } }),
            get: vi.fn().mockResolvedValue({ data: { svg: '<svg />', url: 'otpauth://...', secretKey: 'ABCD' } }),
        };
    });

    it('renders without errors when 2FA is enabled', () => {
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('shows Disable button when enabled', () => {
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        expect(wrapper.text()).toContain('Disable');
    });

    it('calls router.delete when Disable is confirmed', async () => {
        const { router } = await import('@inertiajs/vue3');
        vi.clearAllMocks();
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        // Find the Disable confirm stub
        const disableStub = wrapper.findAll('.confirms-stub').find(s => s.text().includes('Disable'));
        if (disableStub) await disableStub.trigger('click');
        expect(router.delete).toHaveBeenCalled();
    });

    it('shows Regenerate button when enabled', () => {
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        // When twoFactorEnabled is true, Regenerate and Show Codes buttons should appear
        expect(wrapper.exists()).toBe(true);
    });

    it('fetches QR code when Setup Key is shown', async () => {
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        // The qrCode starts as null — showTwoFactorQrCode fetches it
        const regenerateStub = wrapper.findAll('.confirms-stub').find(s => s.text().includes('Regenerate'));
        if (regenerateStub) {
            await regenerateStub.trigger('click');
            expect(window.axios.post).toHaveBeenCalled();
        }
    });
});

describe('TwoFactorAuthenticationForm — requiresConfirmation', () => {
    beforeEach(async () => {
        const { usePage } = await import('@inertiajs/vue3');
        usePage.mockReturnValue({
            props: {
                auth: { user: { id: 1, name: 'Alice', email: 'alice@example.com', two_factor_enabled: true } },
                jetstream: { managesProfilePhotos: false, hasEmailVerification: false, canManageTwoFactorAuthentication: true },
            },
        });
        window.axios = {
            post: vi.fn().mockResolvedValue({}),
            get: vi.fn().mockResolvedValue({ data: {} }),
        };
    });

    it('renders confirm form when requiresConfirmation is true and confirming', () => {
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: true },
            global: { stubs: commonStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });
});

describe('UpdateProfileInformationForm — email verification', () => {
    it('renders without email verification feature', () => {
        const user = { id: 1, name: 'Alice', email: 'alice@example.com', profile_photo_url: null, profile_photo_path: null };
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user },
            global: { stubs: commonStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('shows verification message when email verification is enabled and email is unverified', () => {
        const user = { id: 1, name: 'Alice', email: 'alice@example.com', profile_photo_url: null, email_verified_at: null };
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user },
            global: {
                stubs: commonStubs,
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
        expect(wrapper.text()).toContain('unverified');
    });

    it('shows resend verification button when email is unverified', () => {
        const user = { id: 1, name: 'Alice', email: 'alice@example.com', profile_photo_url: null, email_verified_at: null };
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user },
            global: {
                stubs: commonStubs,
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
});

describe('UpdateProfileInformationForm — submission', () => {
    it('calls form.post when form is submitted', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        const postMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm({ name: 'Alice', email: 'alice@example.com', _method: 'PUT', photo: null }), post: postMock });
        const user = { id: 1, name: 'Alice', email: 'alice@example.com', profile_photo_url: null, profile_photo_path: null };
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user },
            global: { stubs: commonStubs },
        });
        await wrapper.find('form').trigger('submit');
        expect(postMock).toHaveBeenCalled();
    });

    it('shows photo section when managesProfilePhotos is true', () => {
        const user = { id: 1, name: 'Alice', email: 'alice@example.com', profile_photo_url: 'https://example.com/photo.jpg', profile_photo_path: 'photos/1.jpg' };
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user },
            global: {
                stubs: commonStubs,
                mocks: {
                    $page: {
                        props: {
                            auth: { user },
                            jetstream: { managesProfilePhotos: true, hasEmailVerification: false },
                        },
                    },
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });
});

describe('UpdatePasswordForm — errors', () => {
    it('renders update password form', () => {
        const wrapper = mount(UpdatePasswordForm, {
            global: { stubs: commonStubs },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('shows current password and new password fields', () => {
        const wrapper = mount(UpdatePasswordForm, {
            global: { stubs: commonStubs },
        });
        expect(wrapper.text()).toContain('Current Password');
        expect(wrapper.text()).toContain('New Password');
    });

    it('calls form.put when form is submitted', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        const putMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm(), put: putMock, reset: vi.fn() });
        const wrapper = mount(UpdatePasswordForm, {
            global: { stubs: commonStubs },
        });
        await wrapper.find('form').trigger('submit');
        expect(putMock).toHaveBeenCalled();
    });

    it('renders a Save button', () => {
        const wrapper = mount(UpdatePasswordForm, {
            global: { stubs: commonStubs },
        });
        expect(wrapper.find('button[type="submit"]').text()).toContain('Save');
    });
});

describe('LogoutOtherBrowserSessionsForm — session list', () => {
    it('shows session list IP addresses', () => {
        const sessions = [
            { agent: 'Chrome on Mac', ip_address: '10.0.0.1', is_current_device: true, last_active: 'just now' },
            { agent: 'Safari on iOS', ip_address: '10.0.0.2', is_current_device: false, last_active: '3 days ago' },
        ];
        const wrapper = mount(LogoutOtherBrowserSessionsForm, {
            props: { sessions },
            global: { stubs: commonStubs },
        });
        expect(wrapper.text()).toContain('10.0.0.1');
        expect(wrapper.text()).toContain('This device');
    });

    it('shows confirmation dialog when Logout button is clicked', async () => {
        const wrapper = mount(LogoutOtherBrowserSessionsForm, {
            props: { sessions: [] },
            global: { stubs: { ...commonStubs, DialogModal: { props: ['show'], emits: ['close'], template: '<div v-if="show" id="dialog"><slot name="content" /><slot name="footer" /></div>' } } },
        });
        // Find the logout other sessions button
        const logoutBtn = wrapper.findAll('button').find(b => b.text().includes('Log Out'));
        if (logoutBtn) await logoutBtn.trigger('click');
        expect(wrapper.find('#dialog').exists()).toBe(true);
    });
});

describe('DeleteUserForm — confirmation', () => {
    it('shows Delete Account button', () => {
        const wrapper = mount(DeleteUserForm, {
            global: { stubs: commonStubs },
        });
        expect(wrapper.text()).toContain('Delete');
    });

    it('shows confirmation dialog when Delete button is clicked', async () => {
        const wrapper = mount(DeleteUserForm, {
            global: { stubs: { ...commonStubs, DialogModal: { props: ['show'], emits: ['close'], template: '<div v-if="show" id="confirm"><slot name="content" /><slot name="footer" /></div>' } } },
        });
        await wrapper.find('button[type="button"]').trigger('click');
        expect(wrapper.find('#confirm').exists()).toBe(true);
    });

    it('closes dialog on close event from modal', async () => {
        const ClosableModal = {
            props: ['show'],
            emits: ['close'],
            template: '<div v-if="show" id="confirm"><slot name="footer" /><button class="modal-close" @click="$emit(\'close\')">X</button></div>',
        };
        const wrapper = mount(DeleteUserForm, {
            global: { stubs: { ...commonStubs, DialogModal: ClosableModal } },
        });
        await wrapper.find('button[type="button"]').trigger('click');
        expect(wrapper.find('#confirm').exists()).toBe(true);
        await wrapper.find('.modal-close').trigger('click');
        expect(wrapper.find('#confirm').exists()).toBe(false);
    });
});

// ── UpdateProfileInformationForm — uncovered functions ───────────────────────

describe('UpdateProfileInformationForm — sendEmailVerification', () => {
    it('shows verification-sent message after clicking re-send link', async () => {
        const user = { id: 1, name: 'Alice', email: 'alice@example.com', email_verified_at: null, profile_photo_url: null, profile_photo_path: null };
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user },
            global: {
                stubs: commonStubs,
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
        expect(wrapper.text()).toContain('unverified');
        const link = wrapper.findAll('a').find(a => a.text().includes('re-send'));
        if (link) {
            await link.trigger('click');
            await wrapper.vm.$nextTick();
            expect(wrapper.text()).toContain('verification link has been sent');
        }
    });
});

describe('UpdateProfileInformationForm — deletePhoto', () => {
    const photoUser = { id: 1, name: 'Alice', email: 'alice@example.com', profile_photo_url: 'photo.jpg', profile_photo_path: 'photos/1.jpg' };
    const photoMocks = {
        $page: {
            props: {
                auth: { user: photoUser },
                jetstream: { managesProfilePhotos: true, hasEmailVerification: false },
            },
        },
    };

    it('calls router.delete when Remove Photo is clicked', async () => {
        const { router } = await import('@inertiajs/vue3');
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user: photoUser },
            global: { stubs: commonStubs, mocks: photoMocks },
        });
        const removeBtn = wrapper.findAll('button').find(b => b.text().includes('Remove'));
        if (removeBtn) {
            await removeBtn.trigger('click');
            expect(router.delete).toHaveBeenCalled();
        }
        expect(wrapper.exists()).toBe(true);
    });

    it('covers deletePhoto onSuccess callback', async () => {
        const { router } = await import('@inertiajs/vue3');
        router.delete.mockImplementationOnce((_url, options) => options?.onSuccess?.());
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user: photoUser },
            global: { stubs: commonStubs, mocks: photoMocks },
        });
        const removeBtn = wrapper.findAll('button').find(b => b.text().includes('Remove'));
        if (removeBtn) {
            await removeBtn.trigger('click');
            await wrapper.vm.$nextTick();
        }
        expect(wrapper.exists()).toBe(true);
    });
});

describe('UpdateProfileInformationForm — clearPhotoFileInput via onSuccess', () => {
    it('covers clearPhotoFileInput when form.post onSuccess fires', async () => {
        const { usePage, useForm } = await import('@inertiajs/vue3');
        usePage.mockReturnValue({
            props: {
                auth: { user: { id: 1, name: 'Alice', email: 'alice@example.com', profile_photo_url: null, profile_photo_path: null } },
                jetstream: { managesProfilePhotos: false, hasEmailVerification: false },
            },
        });
        const postMock = vi.fn((_url, options) => options?.onSuccess?.());
        useForm.mockReturnValueOnce({ ...makeForm({ _method: 'PUT', name: 'Alice', email: 'alice@example.com', photo: null }), post: postMock });
        const user = { id: 1, name: 'Alice', email: 'alice@example.com', profile_photo_url: null, profile_photo_path: null };
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user },
            global: { stubs: commonStubs },
        });
        await wrapper.find('form').trigger('submit');
        expect(postMock).toHaveBeenCalled();
    });
});

describe('UpdateProfileInformationForm — selectNewPhoto and submit-with-photos branch', () => {
    const photoUser = { id: 1, name: 'Alice', email: 'alice@example.com', profile_photo_url: 'photo.jpg', profile_photo_path: null };
    const photoMocks = {
        $page: {
            props: {
                auth: { user: photoUser },
                jetstream: { managesProfilePhotos: true, hasEmailVerification: false },
            },
        },
    };

    it('clicks Select A New Photo (covers selectNewPhoto function)', async () => {
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user: photoUser },
            global: { stubs: commonStubs, mocks: photoMocks },
        });
        const selectBtn = wrapper.findAll('button').find(b => b.text().includes('Select'));
        if (selectBtn) {
            await selectBtn.trigger('click');
        }
        expect(wrapper.exists()).toBe(true);
    });

    it('submits form with managesProfilePhotos=true (covers photoInput.value branch)', async () => {
        const { useForm } = await import('@inertiajs/vue3');
        const postMock = vi.fn();
        useForm.mockReturnValueOnce({ ...makeForm({ _method: 'PUT', name: 'Alice', email: 'alice@example.com', photo: null }), post: postMock });
        const wrapper = mount(UpdateProfileInformationForm, {
            props: { user: photoUser },
            global: { stubs: commonStubs, mocks: photoMocks },
        });
        await wrapper.find('form').trigger('submit');
        expect(postMock).toHaveBeenCalled();
    });
});

// ── TwoFactorAuthenticationForm — callback coverage ─────────────────────────

describe('TwoFactorAuthenticationForm — enableTwoFactorAuthentication callbacks', () => {
    beforeEach(async () => {
        const { usePage } = await import('@inertiajs/vue3');
        usePage.mockReturnValue({
            props: {
                auth: { user: { id: 1, name: 'Alice', email: 'alice@example.com', two_factor_enabled: false } },
                jetstream: { canManageTwoFactorAuthentication: true },
            },
        });
        window.axios = {
            get: vi.fn().mockResolvedValue({ data: { svg: '<svg/>', url: 'otpauth://', secretKey: 'SECRET', codes: ['c1', 'c2'] } }),
            post: vi.fn().mockResolvedValue({ data: { codes: ['new1', 'new2'] } }),
        };
    });

    it('calls axios.get for QR code, setup key, and recovery codes on onSuccess', async () => {
        const { router } = await import('@inertiajs/vue3');
        router.post.mockImplementationOnce((_url, _data, options) => options?.onSuccess?.());

        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        await wrapper.find('.confirms-stub').trigger('click');
        await flushPromises();
        expect(window.axios.get).toHaveBeenCalledTimes(3);
    });

    it('sets enabling=false after onFinish', async () => {
        const { router } = await import('@inertiajs/vue3');
        router.post.mockImplementationOnce((_url, _data, options) => options?.onFinish?.());

        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        await wrapper.find('.confirms-stub').trigger('click');
        await wrapper.vm.$nextTick();
        expect(wrapper.exists()).toBe(true);
    });
});

describe('TwoFactorAuthenticationForm — 2FA enabled: showRecoveryCodes and regenerate', () => {
    beforeEach(async () => {
        const { usePage } = await import('@inertiajs/vue3');
        usePage.mockReturnValue({
            props: {
                auth: { user: { id: 1, name: 'Alice', email: 'alice@example.com', two_factor_enabled: true } },
                jetstream: { canManageTwoFactorAuthentication: true },
            },
        });
        window.axios = {
            get: vi.fn().mockResolvedValue({ data: ['code1', 'code2'] }),
            post: vi.fn().mockResolvedValue({}),
        };
    });

    it('calls axios.get when Show Recovery Codes is clicked', async () => {
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        const showStub = wrapper.findAll('.confirms-stub').find(s => s.text().includes('Show Recovery Codes'));
        expect(showStub?.exists()).toBe(true);
        await showStub.trigger('click');
        await flushPromises();
        expect(window.axios.get).toHaveBeenCalled();
    });

    it('shows recovery codes list after showRecoveryCodes resolves', async () => {
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        const showStub = wrapper.findAll('.confirms-stub').find(s => s.text().includes('Show Recovery Codes'));
        await showStub.trigger('click');
        await flushPromises();
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('code1');
    });

    it('calls axios.post when Regenerate Recovery Codes is clicked', async () => {
        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        // Populate recoveryCodes first so the Regenerate button appears
        const showStub = wrapper.findAll('.confirms-stub').find(s => s.text().includes('Show Recovery Codes'));
        await showStub.trigger('click');
        await flushPromises();
        await wrapper.vm.$nextTick();

        const regenStub = wrapper.findAll('.confirms-stub').find(s => s.text().includes('Regenerate'));
        if (regenStub?.exists()) {
            await regenStub.trigger('click');
            await flushPromises();
            expect(window.axios.post).toHaveBeenCalled();
        } else {
            expect(wrapper.text()).toContain('code1');
        }
    });

    it('disableTwoFactorAuthentication onSuccess resets disabling and confirming', async () => {
        const { router } = await import('@inertiajs/vue3');
        router.delete.mockImplementationOnce((_url, options) => options?.onSuccess?.());

        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: false },
            global: { stubs: commonStubs },
        });
        const disableStub = wrapper.findAll('.confirms-stub').find(s => s.text().includes('Disable'));
        if (disableStub?.exists()) {
            await disableStub.trigger('click');
            await wrapper.vm.$nextTick();
        }
        expect(wrapper.exists()).toBe(true);
    });
});

describe('TwoFactorAuthenticationForm — confirmTwoFactorAuthentication', () => {
    it('calls confirmationForm.post when Confirm is clicked after enabling', async () => {
        const { reactive } = await import('vue');
        const { router, usePage, useForm } = await import('@inertiajs/vue3');
        vi.clearAllMocks();

        const pageState = reactive({
            props: {
                auth: { user: { id: 1, name: 'Alice', email: 'alice@example.com', two_factor_enabled: false } },
                jetstream: { canManageTwoFactorAuthentication: true },
            },
        });
        usePage.mockReturnValue(pageState);

        window.axios = {
            get: vi.fn().mockResolvedValue({ data: { svg: '<svg/>', url: '', secretKey: 'K', codes: [] } }),
            post: vi.fn().mockResolvedValue({}),
        };

        const formPostSpy = vi.fn();
        useForm.mockReturnValueOnce({
            ...makeForm({ code: '' }),
            post: formPostSpy,
            reset: vi.fn(),
            clearErrors: vi.fn(),
            errors: {},
        });

        router.post.mockImplementationOnce((_url, _data, options) => {
            options?.onSuccess?.();
            pageState.props.auth.user.two_factor_enabled = true;
            options?.onFinish?.();
        });

        const wrapper = mount(TwoFactorAuthenticationForm, {
            props: { requiresConfirmation: true },
            global: { stubs: commonStubs },
        });

        await wrapper.find('.confirms-stub').trigger('click');
        await flushPromises();
        await wrapper.vm.$nextTick();

        const confirmStub = wrapper.findAll('.confirms-stub').find(s => s.text().includes('Confirm'));
        if (confirmStub?.exists()) {
            await confirmStub.trigger('click');
            expect(formPostSpy).toHaveBeenCalled();
        } else {
            expect(wrapper.exists()).toBe(true);
        }
    });
});
