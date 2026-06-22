import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div></div>' },
    Link: { name: 'Link', props: ['href'], template: '<a :href="href"><slot /></a>' },
    router: { visit: vi.fn(), reload: vi.fn(), get: vi.fn(), post: vi.fn() },
    usePage: vi.fn(() => ({
        props: {
            auth: {
                user: { id: 1, name: 'Alice', email: 'alice@example.com' },
            },
            jetstream: { flash: {}, hasTeamFeatures: true },
        },
    })),
}));

import TeamIndex from '../Index.vue';
import AcceptInvitation from '../AcceptInvitation.vue';

const AppLayoutStub = { name: 'AppLayout', template: '<div><slot name="header" /><slot /></div>' };

const makeTeam = (overrides = {}) => ({
    id: 1,
    name: 'My Team',
    owner_id: 1,
    members: [],
    invitations: [],
    ...overrides,
});

describe('TeamIndex — no team', () => {
    beforeEach(() => {
        window.axios = {
            post: vi.fn().mockResolvedValue({ data: {} }),
            delete: vi.fn().mockResolvedValue({}),
        };
    });

    it('renders without errors when team is null', () => {
        const wrapper = mount(TeamIndex, {
            props: { team: null },
            global: { stubs: { AppLayout: AppLayoutStub } },
        });
        expect(wrapper.exists()).toBe(true);
    });

    it('shows create team form when no team', () => {
        const wrapper = mount(TeamIndex, {
            props: { team: null },
            global: { stubs: { AppLayout: AppLayoutStub } },
        });
        expect(wrapper.text()).toContain('Create a Team');
    });

    it('creates a team', async () => {
        const wrapper = mount(TeamIndex, {
            props: { team: null },
            global: { stubs: { AppLayout: AppLayoutStub } },
        });
        const input = wrapper.find('input[type="text"]');
        await input.setValue('New Team');
        await wrapper.find('form').trigger('submit');
        expect(window.axios.post).toHaveBeenCalledWith('/api/teams', { name: 'New Team' });
    });

    it('shows validation errors when team creation fails', async () => {
        window.axios.post = vi.fn().mockRejectedValue({
            response: { data: { errors: { name: ['Team name is required.'] } } },
        });
        const wrapper = mount(TeamIndex, {
            props: { team: null },
            global: { stubs: { AppLayout: AppLayoutStub } },
        });
        await wrapper.find('input[type="text"]').setValue('');
        // Enable the button by entering text then clearing, or click directly
        await wrapper.find('form').trigger('submit');
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Team name is required.');
    });
});

describe('TeamIndex — with team (owner)', () => {
    beforeEach(() => {
        window.axios = {
            post: vi.fn().mockResolvedValue({ data: {} }),
            delete: vi.fn().mockResolvedValue({}),
        };
    });

    it('renders team info', () => {
        const wrapper = mount(TeamIndex, {
            props: { team: makeTeam() },
            global: { stubs: { AppLayout: AppLayoutStub } },
        });
        expect(wrapper.text()).toContain('My Team');
    });

    it('shows invite form for owner', () => {
        const wrapper = mount(TeamIndex, {
            props: { team: makeTeam({ owner_id: 1 }) },
            global: { stubs: { AppLayout: AppLayoutStub } },
        });
        expect(wrapper.text()).toContain('Invite Partner');
    });

    it('sends an invite', async () => {
        const wrapper = mount(TeamIndex, {
            props: { team: makeTeam() },
            global: { stubs: { AppLayout: AppLayoutStub } },
        });
        const emailInput = wrapper.find('input[type="email"]');
        await emailInput.setValue('bob@example.com');
        const forms = wrapper.findAll('form');
        await forms[forms.length - 1].trigger('submit');
        expect(window.axios.post).toHaveBeenCalledWith(
            '/api/teams/invite',
            { email: 'bob@example.com' },
        );
    });

    it('shows invite success message', async () => {
        const wrapper = mount(TeamIndex, {
            props: { team: makeTeam() },
            global: { stubs: { AppLayout: AppLayoutStub } },
        });
        await wrapper.find('input[type="email"]').setValue('bob@example.com');
        const forms = wrapper.findAll('form');
        await forms[forms.length - 1].trigger('submit');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Invitation sent!');
    });

    it('shows invite error on failure', async () => {
        window.axios.post = vi.fn().mockRejectedValue({
            response: { data: { message: 'User not found.' } },
        });
        const wrapper = mount(TeamIndex, {
            props: { team: makeTeam() },
            global: { stubs: { AppLayout: AppLayoutStub } },
        });
        await wrapper.find('input[type="email"]').setValue('nobody@example.com');
        const forms = wrapper.findAll('form');
        await forms[forms.length - 1].trigger('submit');
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('User not found.');
    });

    it('shows pending invitations', () => {
        const team = makeTeam({
            invitations: [{ id: 10, email: 'pending@example.com', expires_at: '2025-12-31T00:00:00Z' }],
        });
        const wrapper = mount(TeamIndex, {
            props: { team },
            global: { stubs: { AppLayout: AppLayoutStub } },
        });
        expect(wrapper.text()).toContain('pending@example.com');
    });

    it('shows team members', () => {
        const team = makeTeam({
            members: [{ id: 1, name: 'Alice', email: 'alice@example.com' }],
        });
        const wrapper = mount(TeamIndex, {
            props: { team },
            global: { stubs: { AppLayout: AppLayoutStub } },
        });
        expect(wrapper.text()).toContain('Alice');
    });

    it('removes member when confirmed', async () => {
        const team = makeTeam({
            members: [
                { id: 1, name: 'Alice', email: 'alice@example.com' },
                { id: 2, name: 'Bob', email: 'bob@example.com' },
            ],
        });
        const wrapper = mount(TeamIndex, {
            props: { team },
            global: { stubs: { AppLayout: AppLayoutStub } },
        });
        // Remove button appears for non-owner members (Bob, id=2)
        const removeBtn = wrapper.findAll('button').find(b => b.text() === 'Remove');
        await removeBtn.trigger('click');
        // Modal is Teleported to document.body — click the confirm "Remove" button there
        const confirmBtn = Array.from(document.body.querySelectorAll('button')).find(b => b.textContent.trim() === 'Remove');
        confirmBtn.click();
        await flushPromises();
        expect(window.axios.delete).toHaveBeenCalledWith('/api/teams/members/2');
    });

    it('shows disband button for owner', () => {
        const wrapper = mount(TeamIndex, {
            props: { team: makeTeam() },
            global: { stubs: { AppLayout: AppLayoutStub } },
        });
        expect(wrapper.text()).toContain('Disband');
    });

    it('disbands team when confirmed', async () => {
        const wrapper = mount(TeamIndex, {
            props: { team: makeTeam() },
            global: { stubs: { AppLayout: AppLayoutStub } },
        });
        const disbandBtn = wrapper.findAll('button').find(b => b.text().includes('Disband'));
        await disbandBtn.trigger('click');
        // Modal is Teleported to document.body — click the confirm "Disband" button there
        const confirmBtn = Array.from(document.body.querySelectorAll('button')).find(b => b.textContent.trim() === 'Disband');
        confirmBtn.click();
        await flushPromises();
        expect(window.axios.delete).toHaveBeenCalledWith('/api/teams');
    });
});

describe('AcceptInvitation', () => {
    beforeEach(() => {
        window.axios = {
            post: vi.fn().mockResolvedValue({}),
        };
    });

    it('renders valid invitation', () => {
        const wrapper = mount(AcceptInvitation, {
            props: {
                valid: true, reason: null, token: 'tok123',
                team_name: 'Dev Team', inviter_name: 'Alice',
                email: 'bob@example.com', expires_at: '2025-12-31',
                agentName: null, agentColor: null,
            },
            global: { stubs: { Head: { template: '<div></div>' } } },
        });
        expect(wrapper.text()).toContain('Dev Team');
    });

    it('shows expired message when invitation is invalid', () => {
        const wrapper = mount(AcceptInvitation, {
            props: {
                valid: false, reason: 'expired', token: 'tok123',
                team_name: 'Dev Team', inviter_name: 'Alice',
                email: 'bob@example.com', expires_at: '2025-01-01',
                agentName: null, agentColor: null,
            },
            global: { stubs: { Head: { template: '<div></div>' } } },
        });
        expect(wrapper.text()).toContain('expired');
    });

    it('shows already accepted message', () => {
        const wrapper = mount(AcceptInvitation, {
            props: {
                valid: false, reason: 'already_accepted', token: 'tok123',
                team_name: 'Dev Team', inviter_name: 'Alice',
                email: 'bob@example.com', expires_at: '2025-12-31',
                agentName: null, agentColor: null,
            },
            global: { stubs: { Head: { template: '<div></div>' } } },
        });
        expect(wrapper.text()).toContain('already been accepted');
    });

    it('accepts invite on button click', async () => {
        const { router } = await import('@inertiajs/vue3');
        const wrapper = mount(AcceptInvitation, {
            props: {
                valid: true, reason: null, token: 'tok123',
                team_name: 'Dev Team', inviter_name: 'Alice',
                email: 'alice@example.com', expires_at: '2025-12-31',
                agentName: null, agentColor: null,
            },
            global: { stubs: { Head: { template: '<div></div>' } } },
        });
        await wrapper.find('button').trigger('click');
        expect(window.axios.post).toHaveBeenCalledWith('/api/teams/invitations/tok123/accept');
    });

    it('shows error on failed accept', async () => {
        window.axios.post = vi.fn().mockRejectedValue({
            response: { data: { message: 'Token expired.' } },
        });
        const wrapper = mount(AcceptInvitation, {
            props: {
                valid: true, reason: null, token: 'tok123',
                team_name: 'Dev Team', inviter_name: 'Alice',
                email: 'alice@example.com', expires_at: '2025-12-31',
                agentName: null, agentColor: null,
            },
            global: { stubs: { Head: { template: '<div></div>' } } },
        });
        await wrapper.find('button').trigger('click');
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Token expired.');
    });
});
