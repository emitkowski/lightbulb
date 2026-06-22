import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => ({
    Link: { name: 'Link', props: ['href'], template: '<a :href="href"><slot /></a>' },
    useForm: vi.fn((data = {}) => ({
        ...data,
        processing: false,
        errors: {},
        post: vi.fn(),
        get: vi.fn(),
        delete: vi.fn(),
        reset: vi.fn(),
    })),
}));

import DropdownLink from '../DropdownLink.vue';
import DialogModal from '../DialogModal.vue';
import ConfirmationModal from '../ConfirmationModal.vue';
import FormSection from '../FormSection.vue';
import ActionSection from '../ActionSection.vue';
import ActionMessage from '../ActionMessage.vue';

// DropdownLink stub Modal to avoid dialog issues
const ModalStub = {
    props: ['show', 'maxWidth'],
    emits: ['close'],
    template: '<div v-if="show"><slot /></div>',
};

describe('DropdownLink', () => {
    it('renders as a button when as="button"', () => {
        const wrapper = mount(DropdownLink, {
            props: { as: 'button' },
            slots: { default: 'Action' },
        });
        expect(wrapper.find('button').exists()).toBe(true);
    });

    it('renders as a native <a> when as="a"', () => {
        const wrapper = mount(DropdownLink, {
            props: { as: 'a', href: '/go' },
            slots: { default: 'Go' },
        });
        expect(wrapper.find('a').exists()).toBe(true);
        expect(wrapper.find('a').attributes('href')).toBe('/go');
    });

    it('renders as an Inertia Link by default', () => {
        const wrapper = mount(DropdownLink, {
            props: { href: '/dashboard' },
            slots: { default: 'Dashboard' },
        });
        expect(wrapper.find('a').attributes('href')).toBe('/dashboard');
    });

    it('renders slot content', () => {
        const wrapper = mount(DropdownLink, {
            props: { as: 'button' },
            slots: { default: 'Click me' },
        });
        expect(wrapper.text()).toBe('Click me');
    });
});

describe('DialogModal', () => {
    it('renders when show is true', () => {
        const wrapper = mount(DialogModal, {
            props: { show: true },
            slots: {
                title: 'My Title',
                content: 'My Content',
                footer: '<button>OK</button>',
            },
            global: { stubs: { Modal: ModalStub } },
        });
        expect(wrapper.text()).toContain('My Title');
        expect(wrapper.text()).toContain('My Content');
    });

    it('does not render slot when show is false', () => {
        const wrapper = mount(DialogModal, {
            props: { show: false },
            slots: { title: 'Hidden Title' },
            global: { stubs: { Modal: ModalStub } },
        });
        expect(wrapper.text()).not.toContain('Hidden Title');
    });

    it('emits close when Modal emits close', async () => {
        const CloseModal = {
            props: ['show'],
            emits: ['close'],
            template: '<div v-if="show"><button @click="$emit(\'close\')">X</button><slot /></div>',
        };
        const wrapper = mount(DialogModal, {
            props: { show: true },
            global: { stubs: { Modal: CloseModal } },
        });
        await wrapper.find('button').trigger('click');
        expect(wrapper.emitted('close')).toBeTruthy();
    });
});

describe('ConfirmationModal', () => {
    it('renders when show is true', () => {
        const wrapper = mount(ConfirmationModal, {
            props: { show: true },
            slots: {
                title: 'Are you sure?',
                content: 'This cannot be undone.',
                footer: '<button>Confirm</button>',
            },
            global: { stubs: { Modal: ModalStub } },
        });
        expect(wrapper.text()).toContain('Are you sure?');
        expect(wrapper.text()).toContain('This cannot be undone.');
    });

    it('does not render when show is false', () => {
        const wrapper = mount(ConfirmationModal, {
            props: { show: false },
            slots: { title: 'Hidden' },
            global: { stubs: { Modal: ModalStub } },
        });
        expect(wrapper.text()).not.toContain('Hidden');
    });

    it('emits close when closed', async () => {
        const CloseModal = {
            props: ['show'],
            emits: ['close'],
            template: '<div v-if="show"><button @click="$emit(\'close\')">X</button><slot /></div>',
        };
        const wrapper = mount(ConfirmationModal, {
            props: { show: true },
            global: { stubs: { Modal: CloseModal } },
        });
        await wrapper.find('button').trigger('click');
        expect(wrapper.emitted('close')).toBeTruthy();
    });
});

describe('FormSection', () => {
    it('renders with title, description, form, and actions slots', () => {
        const wrapper = mount(FormSection, {
            slots: {
                title: 'My Form',
                description: 'Fill this out',
                form: '<input id="f" />',
                actions: '<button type="submit">Save</button>',
            },
        });
        expect(wrapper.text()).toContain('My Form');
        expect(wrapper.text()).toContain('Fill this out');
        expect(wrapper.find('#f').exists()).toBe(true);
        expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
    });

    it('hides the actions footer when actions slot is empty', () => {
        const wrapper = mount(FormSection, {
            slots: {
                title: 'Form',
                form: '<input />',
            },
        });
        // No actions slot — hasActions should be false
        expect(wrapper.find('.flex.items-center.justify-end').exists()).toBe(false);
    });

    it('emits submitted when the form is submitted', async () => {
        const wrapper = mount(FormSection, {
            slots: {
                title: 'F',
                form: '<input />',
                actions: '<button type="submit">Save</button>',
            },
        });
        await wrapper.find('form').trigger('submit');
        expect(wrapper.emitted('submitted')).toBeTruthy();
    });
});

describe('ActionSection', () => {
    it('renders with all slots', () => {
        const wrapper = mount(ActionSection, {
            slots: {
                title: 'Action Title',
                description: 'Action description',
                content: '<p id="content">Content here</p>',
            },
        });
        expect(wrapper.text()).toContain('Action Title');
        expect(wrapper.find('#content').exists()).toBe(true);
    });
});
