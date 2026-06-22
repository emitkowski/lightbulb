import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => ({
    Link: { name: 'Link', inheritAttrs: true, template: '<a v-bind="$attrs"><slot /></a>' },
}));

import NavLink from '../NavLink.vue';

describe('NavLink', () => {
    it('renders a link with the given href', () => {
        const wrapper = mount(NavLink, { props: { href: '/dashboard', active: false } });
        expect(wrapper.find('a').attributes('href')).toBe('/dashboard');
    });

    it('renders slot content', () => {
        const wrapper = mount(NavLink, {
            props: { href: '/dashboard', active: false },
            slots: { default: 'Dashboard' },
        });
        expect(wrapper.text()).toBe('Dashboard');
    });

    it('applies active classes when active is true', () => {
        const wrapper = mount(NavLink, { props: { href: '/dashboard', active: true } });
        expect(wrapper.find('a').attributes('class')).toContain('border-indigo-400');
    });

    it('applies inactive classes when active is false', () => {
        const wrapper = mount(NavLink, { props: { href: '/dashboard', active: false } });
        expect(wrapper.find('a').attributes('class')).toContain('border-transparent');
    });

    it('inactive class contains text-gray-500', () => {
        const wrapper = mount(NavLink, { props: { href: '/', active: false } });
        expect(wrapper.find('a').attributes('class')).toContain('text-gray-500');
    });

    it('active class contains text-gray-900', () => {
        const wrapper = mount(NavLink, { props: { href: '/', active: true } });
        expect(wrapper.find('a').attributes('class')).toContain('text-gray-900');
    });
});
