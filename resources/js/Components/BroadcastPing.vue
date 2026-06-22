<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const userId = computed(() => page.props.auth?.user?.id);

const status = ref('idle');
const receivedAt = ref(null);
let channel = null;

function ping() {
    status.value = 'waiting';
    receivedAt.value = null;

    window.axios.post('/api/v1/broadcast-ping')
        .catch(() => {
            status.value = 'idle';
        });
}

onMounted(() => {
    if (userId.value) {
        channel = window.Echo.channel('broadcast-ping.' + userId.value);
        channel.listen('.ping', () => {
            status.value = 'received';
            receivedAt.value = new Date().toLocaleTimeString();
        });
    }
});

onUnmounted(() => {
    if (userId.value) {
        window.Echo.leaveChannel('broadcast-ping.' + userId.value);
    }
});
</script>

<template>
    <div class="flex items-center gap-4 p-4">
        <button
            @click="ping"
            :disabled="status === 'waiting'"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
            </svg>
            WebSocket Ping
        </button>

        <span v-if="status === 'waiting'" class="flex items-center gap-2 text-sm text-gray-500">
            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 22 6.477 22 12h-4z" />
            </svg>
            Waiting for queue + WebSocket…
        </span>

        <span v-else-if="status === 'received'" class="flex items-center gap-2 text-sm font-medium text-green-600">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            WebSocket OK — received at {{ receivedAt }}
        </span>
    </div>
</template>
