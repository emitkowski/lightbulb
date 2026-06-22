import { vi } from 'vitest';
import { config } from '@vue/test-utils';

// Global route() stub matching Ziggy's API: route(name) → URL string, route() → router object
const routeStub = vi.fn().mockImplementation((name, params) => {
    if (name === undefined) {
        return { current: vi.fn(() => false) };
    }
    return params !== undefined ? `/${name}/${params}` : `/${name}`;
});
global.route = routeStub;

// Make route() and $page available inside every Vue template under test
config.global.mocks = {
    route: routeStub,
    $page: {
        props: {
            auth: { user: { id: 1, name: 'Test User', email: 'test@example.com', two_factor_enabled: false } },
            jetstream: {
                flash: { banner: '', bannerStyle: 'success' },
                hasTeamFeatures: false,
                hasTermsAndPrivacyPolicyFeature: false,
                canUpdateProfileInformation: true,
                hasAccountDeletionFeatures: true,
                canManageTwoFactorAuthentication: false,
                managesProfilePhotos: false,
                hasEmailVerification: false,
                hasApiFeatures: false,
            },
        },
    },
};

// Clipboard API
Object.defineProperty(navigator, 'clipboard', {
    value: { writeText: vi.fn().mockResolvedValue(undefined) },
    writable: true,
});

// Stable window.location.origin
Object.defineProperty(window, 'location', {
    value: { origin: 'https://example.com', href: 'https://example.com' },
    writable: true,
});

// jsdom doesn't implement HTMLDialogElement.showModal() / close()
if (typeof HTMLDialogElement !== 'undefined') {
    HTMLDialogElement.prototype.showModal = vi.fn();
    HTMLDialogElement.prototype.close = vi.fn();
}
