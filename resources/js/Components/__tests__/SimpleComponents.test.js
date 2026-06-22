import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => ({
    Link: { name: 'Link', props: ['href'], template: '<a :href="href"><slot /></a>' },
}));

import DangerButton from '../DangerButton.vue';
import PrimaryButton from '../PrimaryButton.vue';
import SecondaryButton from '../SecondaryButton.vue';
import SectionBorder from '../SectionBorder.vue';
import InputLabel from '../InputLabel.vue';
import ApplicationMark from '../ApplicationMark.vue';
import AuthenticationCard from '../AuthenticationCard.vue';
import AuthenticationCardLogo from '../AuthenticationCardLogo.vue';

describe('DangerButton', () => {
    it('renders a button with slot', () => {
        const wrapper = mount(DangerButton, { slots: { default: 'Delete' } });
        expect(wrapper.find('button').exists()).toBe(true);
        expect(wrapper.text()).toBe('Delete');
    });

    it('applies danger styles', () => {
        const wrapper = mount(DangerButton);
        expect(wrapper.find('button').classes().join(' ')).toContain('bg-red');
    });

    it('can be disabled', () => {
        const wrapper = mount(DangerButton, { attrs: { disabled: true } });
        expect(wrapper.find('button').attributes('disabled')).toBeDefined();
    });
});

describe('PrimaryButton', () => {
    it('renders a button with slot', () => {
        const wrapper = mount(PrimaryButton, { slots: { default: 'Submit' } });
        expect(wrapper.find('button').exists()).toBe(true);
        expect(wrapper.text()).toBe('Submit');
    });

    it('applies primary styles', () => {
        const wrapper = mount(PrimaryButton);
        const btn = wrapper.find('button');
        expect(btn.attributes('class')).toContain('bg-gray');
    });
});

describe('SecondaryButton', () => {
    it('renders a button with slot', () => {
        const wrapper = mount(SecondaryButton, { slots: { default: 'Cancel' } });
        expect(wrapper.find('button').exists()).toBe(true);
        expect(wrapper.text()).toBe('Cancel');
    });

    it('renders with type button', () => {
        const wrapper = mount(SecondaryButton);
        expect(wrapper.find('button').attributes('type')).toBe('button');
    });
});

describe('SectionBorder', () => {
    it('renders a divider element', () => {
        const wrapper = mount(SectionBorder);
        expect(wrapper.exists()).toBe(true);
    });

    it('has border styling', () => {
        const wrapper = mount(SectionBorder);
        expect(wrapper.html()).toContain('border');
    });
});

describe('InputLabel', () => {
    it('renders the value as text', () => {
        const wrapper = mount(InputLabel, { props: { value: 'Email Address' } });
        expect(wrapper.text()).toContain('Email Address');
    });

    it('renders slot content when no value', () => {
        const wrapper = mount(InputLabel, { slots: { default: 'Password' } });
        expect(wrapper.text()).toContain('Password');
    });

    it('renders a label element', () => {
        const wrapper = mount(InputLabel, { props: { value: 'Name' } });
        expect(wrapper.find('label').exists()).toBe(true);
    });
});

describe('ApplicationMark', () => {
    it('renders without errors', () => {
        const wrapper = mount(ApplicationMark, {
            global: { stubs: { Link: { template: '<a><slot /></a>' } } },
        });
        expect(wrapper.exists()).toBe(true);
    });
});

describe('AuthenticationCard', () => {
    it('renders logo slot and default slot', () => {
        const wrapper = mount(AuthenticationCard, {
            slots: {
                logo: '<div id="logo">Logo</div>',
                default: '<form id="form">Form</form>',
            },
        });
        expect(wrapper.find('#logo').exists()).toBe(true);
        expect(wrapper.find('#form').exists()).toBe(true);
    });
});

describe('AuthenticationCardLogo', () => {
    it('renders without errors', () => {
        const wrapper = mount(AuthenticationCardLogo, {
            global: { stubs: { Link: { template: '<a><slot /></a>' } } },
        });
        expect(wrapper.exists()).toBe(true);
    });
});
