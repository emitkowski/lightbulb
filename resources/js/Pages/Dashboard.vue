<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';
import BroadcastPing from '@/Components/BroadcastPing.vue';
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <Welcome />
                </div>

                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-2">
                        <p class="px-4 pt-2 text-xs font-medium text-gray-400 uppercase tracking-wide">Queue + WebSocket</p>
                        <BroadcastPing />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
