<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{ meeting?: any; }>();

const meeting = ref(props.meeting ?? null);
const pinInput = ref('');
const showForm = ref(false);
const message = ref('');

const form = useForm({ meeting_id: meeting.value ? meeting.value.id : null, pin: '', name: '', number: '' });

watch(() => props.meeting, (m) => {
    meeting.value = m ?? null;
    form.meeting_id = meeting.value ? meeting.value.id : null;
});

const checkPin = () => {
    if (! meeting.value) return;
    if (! meeting.value.pin) {
        // open form if no pin required
        showForm.value = true;
        return;
    }

    if (pinInput.value === String(meeting.value.pin)) {
        showForm.value = true;
        message.value = '';
        form.pin = pinInput.value;
    } else {
        message.value = 'Wrong pin';
    }
};

const submit = () => {
    form.post('/meeting/register', {
        onSuccess: () => {
            // reload or show message; Inertia will handle flash
            // simple: keep the page; server redirect will refresh props
        }
    });
};
</script>

<template>
    <Head title="Register for Meeting" />

    <AppLayout>
        <div class="p-4 max-w-lg mx-auto">
            <h1 class="text-xl font-semibold mb-4">Register for today's meeting</h1>

            <div v-if="!meeting">
                <div class="text-gray-600">No session today.</div>
            </div>

            <div v-else>
                <div class="mb-4">
                    <div class="font-medium">Meeting ID: {{ meeting.id }}</div>
                    <div class="text-sm text-gray-600">Start: {{ meeting.start_time }}</div>
                </div>

                <div v-if="!showForm">
                    <div v-if="meeting.pin">
                        <label class="block mb-2">Enter session PIN</label>
                        <input type="text" v-model="pinInput" class="border p-2 rounded w-full" />
                        <div class="mt-2">
                            <button class="btn btn-primary" @click.prevent="checkPin">Verify PIN</button>
                        </div>
                        <div v-if="message" class="text-red-600 mt-2">{{ message }}</div>
                    </div>
                    <div v-else>
                        <div>No PIN required — you can register directly.</div>
                        <div class="mt-2"><button class="btn btn-primary" @click.prevent="() => showForm = true">Register</button></div>
                    </div>
                </div>

                <div v-if="showForm" class="mt-4">
                    <form @submit.prevent="submit" class="space-y-3">
                        <div>
                            <label class="block">Name (optional)</label>
                            <input type="text" v-model="form.name" class="border p-2 rounded w-full" />
                        </div>
                        <div>
                            <label class="block">Number</label>
                            <input type="text" v-model="form.number" required class="border p-2 rounded w-full" />
                        </div>

                        <div>
                            <button type="submit" class="btn btn-primary">Register</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
