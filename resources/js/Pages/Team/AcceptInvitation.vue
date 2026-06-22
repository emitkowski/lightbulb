<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    valid:        Boolean,
    reason:       String,  // 'expired' | 'already_accepted' | 'not_found'
    token:        String,
    team_name:    String,
    inviter_name: String,
    email:        String,
    expires_at:   String,
});

const isAccepting = ref(false);
const error       = ref(null);

async function accept() {
    isAccepting.value = true;
    error.value = null;
    try {
        await window.axios.post(`/api/teams/invitations/${props.token}/accept`);
        router.visit(route('dashboard'));
    } catch (err) {
        error.value = err.response?.data?.message ?? 'Something went wrong. Please try again.';
        isAccepting.value = false;
    }
}

const reasonMessages = {
    expired:          'This invitation has expired.',
    already_accepted: 'This invitation has already been accepted.',
    not_found:        'This invitation link is invalid.',
};
</script>

<template>
    <Head title="Team Invitation" />

    <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
        <div class="w-full max-w-md">

            <!-- Invalid -->
            <div v-if="!valid" class="bg-white rounded-xl shadow-sm border border-gray-200 px-8 py-10 text-center">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="text-lg font-semibold text-gray-800 mb-2">Invitation Unavailable</h1>
                <p class="text-sm text-gray-500">{{ reasonMessages[reason] ?? 'This invitation is no longer valid.' }}</p>
                <a href="/" class="mt-6 inline-block text-sm text-gray-600 hover:text-gray-800 underline">Go to app</a>
            </div>

            <!-- Valid -->
            <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 px-8 py-10">
                <div class="text-center mb-6">
                    <div class="w-12 h-12 bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <h1 class="text-lg font-semibold text-gray-800">You've been invited!</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        <span class="font-medium text-gray-700">{{ inviter_name ?? 'Someone' }}</span>
                        has invited you to join
                        <span class="font-medium text-gray-700">{{ team_name }}</span>.
                    </p>
                </div>

                <div class="bg-gray-50 rounded-lg px-4 py-3 mb-5 text-sm text-gray-600 text-center">
                    This invitation was sent to <span class="font-medium">{{ email }}</span>.
                    You must be logged in with this email to accept.
                </div>

                <p v-if="error" class="text-sm text-red-600 text-center mb-4">{{ error }}</p>

                <button
                    @click="accept"
                    :disabled="isAccepting"
                    class="w-full py-2.5 bg-gray-800 text-white text-sm font-medium rounded-lg hover:bg-gray-700 disabled:opacity-50 transition"
                >
                    {{ isAccepting ? 'Joining…' : 'Accept Invitation' }}
                </button>

                <p class="text-xs text-gray-400 text-center mt-3">
                    Not you?
                    <a :href="route('login')" class="underline hover:text-gray-600">Log in with a different account</a>
                </p>
            </div>

        </div>
    </div>
</template>
