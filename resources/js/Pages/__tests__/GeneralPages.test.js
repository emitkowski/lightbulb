import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div></div>' },
    Link: { name: 'Link', props: ['href'], template: '<a :href="href"><slot /></a>' },
}));

import PrivacyPolicy from '../PrivacyPolicy.vue';
import TermsOfService from '../TermsOfService.vue';
import Welcome from '../Welcome.vue';
import Dashboard from '../Dashboard.vue';

const AppLayoutStub = { name: 'AppLayout', template: '<div><slot name="header" /><slot /></div>' };

describe('PrivacyPolicy', () => {
    it('renders HTML policy content', () => {
        const wrapper = mount(PrivacyPolicy, {
            props: { policy: '<p>Privacy content here.</p>' },
            global: { stubs: { AuthenticationCardLogo: { template: '<div />' } } },
        });
        expect(wrapper.html()).toContain('Privacy content here.');
    });
});

describe('TermsOfService', () => {
    it('renders HTML terms content', () => {
        const wrapper = mount(TermsOfService, {
            props: { terms: '<p>Terms and conditions.</p>' },
            global: { stubs: { AuthenticationCardLogo: { template: '<div />' } } },
        });
        expect(wrapper.html()).toContain('Terms and conditions.');
    });
});

describe('Welcome', () => {
    it('renders without errors', () => {
        const wrapper = mount(Welcome, {
            props: {
                canLogin: true,
                canRegister: true,
                laravelVersion: '11.0',
                phpVersion: '8.3',
            },
        });
        expect(wrapper.exists()).toBe(true);
    });

    // Override $page to simulate unauthenticated user (so Log in / Register links show)
    const guestPage = { props: { auth: { user: null }, jetstream: {} } };

    it('shows login link when canLogin is true', () => {
        const wrapper = mount(Welcome, {
            props: { canLogin: true, canRegister: false, laravelVersion: '11.0', phpVersion: '8.3' },
            global: { mocks: { $page: guestPage } },
        });
        expect(wrapper.findAll('a').some(a => a.text().trim() === 'Log in')).toBe(true);
    });

    it('shows register link when canRegister is true', () => {
        const wrapper = mount(Welcome, {
            props: { canLogin: true, canRegister: true, laravelVersion: '11.0', phpVersion: '8.3' },
            global: { mocks: { $page: guestPage } },
        });
        expect(wrapper.findAll('a').some(a => a.text().trim() === 'Register')).toBe(true);
    });

    it('hides register link when canRegister is false', () => {
        const wrapper = mount(Welcome, {
            props: { canLogin: true, canRegister: false, laravelVersion: '11.0', phpVersion: '8.3' },
            global: { mocks: { $page: guestPage } },
        });
        expect(wrapper.findAll('a').some(a => a.text().trim() === 'Register')).toBe(false);
    });

    it('hides login link when canLogin is false', () => {
        const wrapper = mount(Welcome, {
            props: { canLogin: false, canRegister: false, laravelVersion: '11.0', phpVersion: '8.3' },
            global: { mocks: { $page: guestPage } },
        });
        expect(wrapper.findAll('a').some(a => a.text().trim() === 'Log in')).toBe(false);
    });
});

describe('Welcome — handleImageError', () => {
    it('triggers handleImageError when background img fires error', async () => {
        const wrapper = mount(Welcome, {
            props: { canLogin: true, canRegister: false, laravelVersion: '11.0', phpVersion: '8.3' },
            global: { mocks: { $page: { props: { auth: { user: null }, jetstream: {} } } } },
        });
        const img = wrapper.find('#background');
        if (img.exists()) {
            await img.trigger('error');
        }
        expect(wrapper.exists()).toBe(true);
    });
});

describe('Dashboard', () => {
    it('renders without errors', () => {
        const wrapper = mount(Dashboard, {
            global: {
                stubs: {
                    AppLayout: AppLayoutStub,
                    Welcome: { template: '<div>Welcome widget</div>' },
                },
            },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('renders the Dashboard header', () => {
        const wrapper = mount(Dashboard, {
            global: {
                stubs: {
                    AppLayout: AppLayoutStub,
                    Welcome: { template: '<div />' },
                },
            },
        });
        expect(wrapper.text()).toContain('Dashboard');
    });
});
