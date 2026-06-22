import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(() => ({
        props: {
            jetstream: {
                flash: { banner: 'Operation successful', bannerStyle: 'success' },
            },
        },
    })),
}));

import Banner from '../Banner.vue';

describe('Banner', () => {
    it('displays a success banner with the correct message', async () => {
        const wrapper = mount(Banner);
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Operation successful');
    });

    it('applies success background when style is success', async () => {
        const wrapper = mount(Banner);
        await wrapper.vm.$nextTick();
        const banner = wrapper.find('.bg-indigo-500');
        expect(banner.exists()).toBe(true);
    });

    it('hides the banner when the dismiss button is clicked', async () => {
        const wrapper = mount(Banner);
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.bg-indigo-500').exists()).toBe(true);
        await wrapper.find('button[aria-label="Dismiss"]').trigger('click');
        expect(wrapper.find('.bg-indigo-500').exists()).toBe(false);
    });

    it('shows nothing when there is no message', async () => {
        const { usePage } = await import('@inertiajs/vue3');
        usePage.mockReturnValueOnce({
            props: {
                jetstream: { flash: { banner: '', bannerStyle: 'success' } },
            },
        });
        const wrapper = mount(Banner);
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.bg-indigo-500').exists()).toBe(false);
        expect(wrapper.find('.bg-red-700').exists()).toBe(false);
    });
});

describe('Banner — danger style', () => {
    it('applies danger background when style is danger', async () => {
        const { usePage } = await import('@inertiajs/vue3');
        usePage.mockReturnValueOnce({
            props: {
                jetstream: { flash: { banner: 'Something went wrong', bannerStyle: 'danger' } },
            },
        });
        const wrapper = mount(Banner);
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.bg-red-700').exists()).toBe(true);
    });
});
