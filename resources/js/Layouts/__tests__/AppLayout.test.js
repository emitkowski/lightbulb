import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div></div>' },
    Link: { name: 'Link', props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { put: vi.fn(), post: vi.fn() },
}));

import { router as mockRouter } from '@inertiajs/vue3';

import AppLayout from '../AppLayout.vue';

const makeUser = (overrides = {}) => ({
    id: 1,
    name: 'Alice',
    email: 'alice@example.com',
    profile_photo_url: null,
    current_team: { id: 1, name: 'My Team' },
    current_team_id: 1,
    all_teams: [{ id: 1, name: 'My Team' }],
    ...overrides,
});

const makePage = (overrides = {}) => ({
    props: {
        auth: { user: makeUser() },
        jetstream: {
            flash: { banner: '', bannerStyle: 'success' },
            hasTeamFeatures: false,
            hasApiFeatures: false,
            canCreateTeams: false,
            managesProfilePhotos: false,
        },
        ...overrides,
    },
});

const layoutStubs = {
    Banner: { template: '<div id="banner" />' },
    ApplicationMark: { template: '<div />' },
    Dropdown: { template: '<div><slot name="trigger" /><slot name="content" /></div>' },
    DropdownLink: { props: ['href', 'as'], template: '<a v-if="href" :href="href"><slot /></a><button v-else><slot /></button>' },
    NavLink: { props: ['href', 'active'], template: '<a :href="href"><slot /></a>' },
    ResponsiveNavLink: { props: ['href', 'active', 'as'], template: '<a v-if="href" :href="href"><slot /></a><button v-else><slot /></button>' },
};

describe('AppLayout — basic rendering', () => {
    it('renders without errors', () => {
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: {
                stubs: layoutStubs,
                mocks: { $page: makePage() },
            },
            slots: { default: '<div id="content">Page content</div>' },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('renders slot content', () => {
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: makePage() } },
            slots: { default: '<p id="main">Content</p>' },
        });
        expect(wrapper.find('#main').exists()).toBe(true);
    });

    it('renders header slot', () => {
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: makePage() } },
            slots: { header: '<h1 id="header-content">My Page</h1>' },
        });
        expect(wrapper.find('#header-content').exists()).toBe(true);
    });

    it('shows user name in nav', () => {
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: makePage() } },
        });
        expect(wrapper.text()).toContain('Alice');
    });

    it('shows Session, Agents, Profile, Team nav links', () => {
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: makePage() } },
        });
        expect(wrapper.text()).toContain('Sessions');
        expect(wrapper.text()).toContain('Agents');
        expect(wrapper.text()).toContain('What I Know About You');
        expect(wrapper.text()).toContain('Team');
    });

    it('renders the Banner component', () => {
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: makePage() } },
        });
        expect(wrapper.find('#banner').exists()).toBe(true);
    });
});

describe('AppLayout — hamburger menu', () => {
    it('toggles responsive nav on hamburger click', async () => {
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: makePage() } },
        });
        const hamburger = wrapper.find('button.inline-flex.items-center.justify-center.p-2');
        await hamburger.trigger('click');
        // After clicking, showingNavigationDropdown becomes true → block class is added
        const allSmHidden = wrapper.findAll('.sm\\:hidden');
        const hasBlockClass = allSmHidden.some(el => el.classes().includes('block'));
        expect(hasBlockClass).toBe(true);
    });

    it('shows responsive nav links', async () => {
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: makePage() } },
        });
        await wrapper.find('button.inline-flex.items-center.justify-center.p-2').trigger('click');
        expect(wrapper.text()).toContain('Log Out');
    });
});

describe('AppLayout — logout', () => {
    beforeEach(() => vi.clearAllMocks());

    it('calls router.post on logout', async () => {
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: makePage() } },
        });
        await wrapper.find('form').trigger('submit');
        expect(mockRouter.post).toHaveBeenCalled();
    });
});

describe('AppLayout — team features', () => {
    const teamPage = makePage({
        jetstream: {
            flash: {},
            hasTeamFeatures: true,
            hasApiFeatures: false,
            canCreateTeams: true,
            managesProfilePhotos: false,
        },
    });

    it('shows team dropdown when hasTeamFeatures is true', () => {
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: teamPage } },
        });
        expect(wrapper.text()).toContain('My Team');
    });

    it('shows Manage Team links in team dropdown', () => {
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: teamPage } },
        });
        expect(wrapper.text()).toContain('Team Settings');
    });

    it('shows Create New Team when canCreateTeams', () => {
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: teamPage } },
        });
        expect(wrapper.text()).toContain('Create New Team');
    });

    it('shows team switcher when user has multiple teams', () => {
        const multiTeamPage = {
            props: {
                auth: {
                    user: makeUser({
                        all_teams: [
                            { id: 1, name: 'Team A' },
                            { id: 2, name: 'Team B' },
                        ],
                    }),
                },
                jetstream: { ...teamPage.props.jetstream },
            },
        };
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: multiTeamPage } },
        });
        expect(wrapper.text()).toContain('Team A');
        expect(wrapper.text()).toContain('Team B');
    });

    it('calls switchToTeam when team switcher form is submitted', async () => {
        const multiTeamPage = {
            props: {
                auth: {
                    user: makeUser({
                        all_teams: [
                            { id: 1, name: 'Team A' },
                            { id: 2, name: 'Team B' },
                        ],
                    }),
                },
                jetstream: { ...teamPage.props.jetstream },
            },
        };
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: multiTeamPage } },
        });
        const forms = wrapper.findAll('form');
        await forms[0].trigger('submit');
        expect(mockRouter.put).toHaveBeenCalled();
    });
});

describe('AppLayout — profile photo', () => {
    it('shows profile photo img when managesProfilePhotos is true', () => {
        const photoPage = makePage({
            jetstream: {
                flash: {},
                hasTeamFeatures: false,
                hasApiFeatures: false,
                canCreateTeams: false,
                managesProfilePhotos: true,
            },
        });
        const user = makeUser({ profile_photo_url: 'https://example.com/photo.jpg' });
        const page = { props: { auth: { user }, jetstream: photoPage.props.jetstream } };
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: page } },
        });
        expect(wrapper.find('img').exists()).toBe(true);
    });
});

describe('AppLayout — API features', () => {
    it('shows API Tokens link when hasApiFeatures', () => {
        const apiPage = makePage({
            jetstream: {
                flash: {},
                hasTeamFeatures: false,
                hasApiFeatures: true,
                canCreateTeams: false,
                managesProfilePhotos: false,
            },
        });
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: apiPage } },
        });
        expect(wrapper.text()).toContain('API Tokens');
    });
});

describe('AppLayout — responsive team features', () => {
    it('shows responsive team section when hasTeamFeatures and nav is open', async () => {
        const teamPage = {
            props: {
                auth: { user: makeUser() },
                jetstream: {
                    flash: {},
                    hasTeamFeatures: true,
                    hasApiFeatures: false,
                    canCreateTeams: true,
                    managesProfilePhotos: false,
                },
            },
        };
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: teamPage } },
        });
        await wrapper.find('button.inline-flex.items-center.justify-center.p-2').trigger('click');
        expect(wrapper.text()).toContain('Manage Team');
    });

    it('shows responsive team switcher with multiple teams', async () => {
        const multiTeamPage = {
            props: {
                auth: {
                    user: makeUser({
                        all_teams: [
                            { id: 1, name: 'Team A' },
                            { id: 2, name: 'Team B' },
                        ],
                    }),
                },
                jetstream: {
                    flash: {},
                    hasTeamFeatures: true,
                    hasApiFeatures: false,
                    canCreateTeams: false,
                    managesProfilePhotos: false,
                },
            },
        };
        const wrapper = mount(AppLayout, {
            props: { title: 'Test' },
            global: { stubs: layoutStubs, mocks: { $page: multiTeamPage } },
        });
        await wrapper.find('button.inline-flex.items-center.justify-center.p-2').trigger('click');
        expect(wrapper.text()).toContain('Switch Teams');
    });
});
