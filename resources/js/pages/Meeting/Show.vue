<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{ meeting: any }>();

const meeting = ref(props.meeting ?? null);
const form = useForm({ start_time: meeting.value.start_time ?? '', end_time: meeting.value.end_time ?? '', info: meeting.value.info ?? '', pin: meeting.value.pin ?? '' });

// search for attendees
const search = ref('');
const filteredRegs = computed(() => {
    if (!meeting.value || !Array.isArray(meeting.value.regs)) return [];
    const q = (search.value || '').toString().trim().toLowerCase();
    if (!q) return meeting.value.regs;
    return meeting.value.regs.filter((r: any) => {
        return ((r.name || '') + ' ' + (r.number || '')).toLowerCase().includes(q);
    });
});

// helper to read cookie (used for CSRF/XSRF)
const getCookie = (name: string) => {
    const match = document.cookie.match(new RegExp('(^|;)\\s*' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : null;
};

const save = () => {
    if (!meeting.value) return;
    form.post(`/meeting/${meeting.value.id}/update`, {
        onSuccess: () => {
            // server issues redirect via Inertia; we'll just let that happen
        }
    });
};

const exportMeeting = () => {
    if (! meeting.value) return;
    const url = `${window.location.origin}/meeting/${meeting.value.id}/export`;
    window.open(url, '_blank');
};

const removeAttendee = async (regId: number) => {
    if (! meeting.value) return;
    if (! confirm('Remove this attendee?')) return;

    const metaToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const xsrf = getCookie('XSRF-TOKEN');

    try {
        const res = await fetch(`/meeting/${meeting.value.id}/remove-attendee/${regId}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': metaToken,
                'X-XSRF-TOKEN': xsrf || ''
            }
        });

        const ct = res.headers.get('content-type') || '';
        if (!res.ok) {
            if (ct.includes('application/json')) {
                const j = await res.json().catch(() => null);
                alert(j?.message || 'Error removing attendee');
            } else {
                const text = await res.text();
                alert((text && text.substring(0,500)) || 'Error removing attendee');
            }
            return;
        }

        const j = ct.includes('application/json') ? await res.json().catch(() => null) : null;
        if (j && j.success) {
            // remove locally
            meeting.value.regs = meeting.value.regs.filter((r: any) => r.id !== regId);
            if (meeting.value.sessions_attended > 0) meeting.value.sessions_attended -= 1;
        } else if (j) {
            alert(j.message || 'Error removing attendee');
        } else {
            // unknown but successful -> reload page
            window.location.reload();
        }
    } catch (err) {
        console.error('remove error', err);
        alert('Network error while removing attendee');
    }
};

const goBack = () => window.history.back();
</script>

<template>
    <Head title="Meeting" />
    <AppLayout>
        <div class="px-2 sm:px-4 lg:px-6 py-6 w-full">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-semibold">Meeting #{{ meeting?.id }}</h1>
                <div class="flex gap-2">
                    <button @click="goBack" class="px-3 py-1 rounded border">Back</button>
                    <button @click="exportMeeting" class="px-3 py-1 rounded bg-gray-100">Print / PDF</button>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm">Start time</label>
                    <input v-model="form.start_time" type="datetime-local" class="w-full border rounded p-2" />
                </div>
                <div>
                    <label class="block text-sm">End time</label>
                    <input v-model="form.end_time" type="datetime-local" class="w-full border rounded p-2" />
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm">Info</label>
                <input v-model="form.info" type="text" class="w-full border rounded p-2" />
            </div>

            <div class="mb-4">
                <label class="block text-sm">PIN</label>
                <input v-model="form.pin" type="text" class="w-full border rounded p-2" />
            </div>

            <div class="mb-4">
                <button @click.prevent="save" class="px-4 py-2 bg-primary text-white rounded">Save</button>
            </div>

            <div>
                <!-- search bar -->
                <div class="mb-3">
                    <input v-model="search" type="search" placeholder="Search attendees by name or number..." class="w-full border rounded p-2" />
                </div>

                <h2 class="text-lg font-medium mb-2">Attendees ({{ filteredRegs.length }} / {{ meeting?.regs?.length ?? 0 }})</h2>

                <div class="overflow-x-auto bg-white rounded shadow w-full p-2">
                    <table class="min-w-full">
                        <thead>
                            <tr class="text-left">
                                <th class="p-2">#</th>
                                <th class="p-2">Name</th>
                                <th class="p-2">Number</th>
                                <th class="p-2">Sessions</th>
                                <th class="p-2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(r, i) in filteredRegs" :key="r.id" class="border-t">
                                <td class="p-2">{{ i + 1 }}</td>
                                <td class="p-2">{{ r.name }}</td>
                                <td class="p-2">{{ r.number }}</td>
                                <td class="p-2">{{ r.sessions_attended }}</td>
                                <td class="p-2"><button @click.prevent="removeAttendee(r.id)" class="text-red-600">Remove</button></td>
                            </tr>
                            <tr v-if="filteredRegs.length === 0" class="border-t">
                                <td class="p-2" colspan="5">No attendees found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
