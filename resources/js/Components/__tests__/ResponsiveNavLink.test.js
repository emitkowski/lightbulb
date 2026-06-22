import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => ({
    Link: { name: 'Link', props: ['href'], template: '<a :href="href"><slot /></a>' },
}));

import ResponsiveNavLink from '../ResponsiveNavLink.vue';

describe('ResponsiveNavLink', () => {
    it('renders a <button> when as="button"', () => {
        const wrapper = mount(ResponsiveNavLink, { props: { as: 'button', href: '/', active: false } });
        expect(wrapper.find('button').exists()).toBe(true);
        expect(wrapper.find('a').exists()).toBe(false);
    });

    it('renders an <a> tag when as="a"', () => {
        const wrapper = mount(ResponsiveNavLink, { props: { as: 'a', href: '/about', active: false } });
        expect(wrapper.find('a').attributes('href')).toBe('/about');
        expect(wrapper.find('button').exists()).toBe(false);
    });

    it('renders a Link component when as is not set', () => {
        const wrapper = mount(ResponsiveNavLink, { props: { href: '/home', active: false } });
        expect(wrapper.find('a').exists()).toBe(true);
    });

    it('applies active classes when active is true', () => {
        const wrapper = mount(ResponsiveNavLink, { props: { as: 'button', href: '/', active: true } });
        expect(wrapper.find('button').attributes('class')).toContain('border-indigo-400');
    });

    it('applies inactive classes when active is false', () => {
        const wrapper = mount(ResponsiveNavLink, { props: { as: 'button', href: '/', active: false } });
        expect(wrapper.find('button').attributes('class')).toContain('border-transparent');
    });

    it('active class contains text-indigo-700', () => {
        const wrapper = mount(ResponsiveNavLink, { props: { as: 'a', href: '/', active: true } });
        expect(wrapper.find('a').attributes('class')).toContain('text-indigo-700');
    });

    it('inactive class contains text-gray-600', () => {
        const wrapper = mount(ResponsiveNavLink, { props: { as: 'a', href: '/', active: false } });
        expect(wrapper.find('a').attributes('class')).toContain('text-gray-600');
    });

    it('renders slot content', () => {
        const wrapper = mount(ResponsiveNavLink, {
            props: { as: 'button', href: '/', active: false },
            slots: { default: 'Menu Item' },
        });
        expect(wrapper.text()).toContain('Menu Item');
    });
});
