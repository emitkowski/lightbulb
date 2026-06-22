<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    team: Object, // null if user is not on a team
});

const page = usePage();
const authUser = page.props.auth.user;

// Create team
const newTeamName  = ref('');
const isCreating   = ref(false);
const createErrors = ref({});

async function createTeam() {
    createErrors.value = {};
    isCreating.value = true;
    try {
        await window.axios.post('/api/teams', { name: newTeamName.value });
        router.reload();
    } catch (err) {
        createErrors.value = err.response?.data?.errors ?? {};
        isCreating.value = false;
    }
}

// Invite
const inviteEmail  = ref('');
const isSending    = ref(false);
const inviteError  = ref(null);
const inviteSent   = ref(false);

async function sendInvite() {
    inviteError.value = null;
    inviteSent.value  = false;
    isSending.value   = true;
    try {
        await window.axios.post('/api/teams/invite', { email: inviteEmail.value });
        inviteSent.value = true;
        inviteEmail.value = '';
        router.reload({ only: ['team'] });
    } catch (err) {
        inviteError.value = err.response?.data?.message ?? 'Failed to send invitation.';
    } finally {
        isSending.value = false;
    }
}

// Remove member
const removingId = ref(null);

async function removeMember(userId) {
    if (!confirm('Remove this member from the team?')) return;
    removingId.value = userId;
    try {
        await window.axios.delete(`/api/teams/members/${userId}`);
        router.reload({ only: ['team'] });
    } catch {
        removingId.value = null;
    }
}

// Disband
const isDisbanding = ref(false);

async function disbandTeam() {
    if (!confirm('Disband this team? Shared agents will become personal agents. This cannot be undone.')) return;
    isDisbanding.value = true;
    try {
        await window.axios.delete('/api/teams');
        router.reload();
    } catch {
        isDisbanding.value = false;
    }
}

const isOwner = props.team?.owner_id === authUser?.id;

let userChannel = null;

onMounted(() => {
    if (!window.Echo || !authUser?.id) return;

    userChannel = window.Echo.private(`App.Models.User.${authUser.id}`)
        .listen('.team.member.joined', () => {
            router.reload({ only: ['team'] });
        });
});

onUnmounted(() => {
    if (userChannel) {
        window.Echo.leave(`App.Models.User.${authUser.id}`);
        userChannel = null;
    }
});
</script>

<template>
    <AppLayout title="Team">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Team</h2>
        </template>

        <div class="py-6 sm:py-12">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- No team -->
                <template v-if="!team">
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-semibold text-gray-800">Create a Team</h3>
                            <p class="text-sm text-gray-500 mt-0.5">Invite a business partner to share agents and projects.</p>
                        </div>
                        <div class="px-6 py-5">
                            <form @submit.prevent="createTeam" class="flex gap-3">
                                <div class="flex-1">
                                    <input
                                        v-model="newTeamName"
                                        type="text"
                                        placeholder="e.g. Acme Ventures"
                                        maxlength="100"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"
                                    />
                                    <p v-if="createErrors.name" class="mt-1 text-xs text-red-600">{{ createErrors.name[0] }}</p>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="isCreating || !newTeamName.trim()"
                                    class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700 disabled:opacity-50 transition"
                                >
                                    {{ isCreating ? 'Creating…' : 'Create Team' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </template>

                <!-- Has team -->
                <template v-else>

                    <!-- Members -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-semibold text-gray-800">{{ team.name }}</h3>
                            <p class="text-sm text-gray-500 mt-0.5">{{ team.members.length }} member{{ team.members.length !== 1 ? 's' : '' }}</p>
                        </div>
                        <div class="divide-y divide-gray-50">
                            <div
                                v-for="member in team.members"
                                :key="member.id"
                                class="flex items-center justify-between px-6 py-3"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-sm font-medium text-gray-600">
                                        {{ member.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">
                                            {{ member.name }}
                                            <span v-if="member.id === team.owner_id" class="ml-1.5 text-xs text-gray-400">(Owner)</span>
                                        </p>
                                        <p class="text-xs text-gray-400">{{ member.email }}</p>
                                    </div>
                                </div>
                                <button
                                    v-if="isOwner && member.id !== team.owner_id"
                                    @click="removeMember(member.id)"
                                    :disabled="removingId === member.id"
                                    class="text-xs text-red-400 hover:text-red-600 disabled:opacity-40 transition"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Invite (owner only) -->
                    <div v-if="isOwner" class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h3 class="font-semibold text-gray-800">Invite Partner</h3>
                        </div>
                        <div class="px-6 py-5">
                            <form @submit.prevent="sendInvite" class="flex gap-3">
                                <div class="flex-1">
                                    <input
                                        v-model="inviteEmail"
                                        type="email"
                                        placeholder="partner@example.com"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-gray-800"
                                    />
                                    <p v-if="inviteError" class="mt-1 text-xs text-red-600">{{ inviteError }}</p>
                                    <p v-if="inviteSent" class="mt-1 text-xs text-green-600">Invitation sent!</p>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="isSending || !inviteEmail.trim()"
                                    class="px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700 disabled:opacity-50 transition"
                                >
                                    {{ isSending ? 'Sending…' : 'Send Invite' }}
                                </button>
                            </form>

                            <!-- Pending invitations -->
                            <div v-if="team.invitations.length" class="mt-4 space-y-1">
                                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">Pending</p>
                                <div
                                    v-for="inv in team.invitations"
                                    :key="inv.id"
                                    class="flex items-center justify-between text-sm text-gray-600 py-1"
                                >
                                    <span>{{ inv.email }}</span>
                                    <span class="text-xs text-gray-400">expires {{ new Date(inv.expires_at).toLocaleDateString() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Danger zone (owner only) -->
                    <div v-if="isOwner" class="bg-white rounded-lg shadow overflow-hidden border border-red-100">
                        <div class="px-6 py-4 border-b border-red-50">
                            <h3 class="font-semibold text-red-800">Danger Zone</h3>
                        </div>
                        <div class="px-6 py-5 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-700">Disband team</p>
                                <p class="text-xs text-gray-400 mt-0.5">Shared agents become personal agents. Members are removed.</p>
                            </div>
                            <button
                                @click="disbandTeam"
                                :disabled="isDisbanding"
                                class="px-4 py-2 text-sm text-red-600 border border-red-300 rounded-md hover:bg-red-50 disabled:opacity-50 transition"
                            >
                                {{ isDisbanding ? 'Disbanding…' : 'Disband' }}
                            </button>
                        </div>
                    </div>

                </template>

            </div>
        </div>
    </AppLayout>
</template>
