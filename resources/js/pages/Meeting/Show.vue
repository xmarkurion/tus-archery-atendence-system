<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{ meeting: any }>();

const meeting = ref(props.meeting ?? null);

// helper: convert DB datetime ("YYYY-MM-DD HH:MM:SS") to datetime-local ("YYYY-MM-DDTHH:MM")
const formatForDatetimeLocal = (dbDate: string | null | undefined) => {
    if (!dbDate) return '';
    // Accept both "YYYY-MM-DD HH:MM:SS" and "YYYY-MM-DD HH:MM"
    const s = dbDate.toString().trim();
    // replace space with T and cut seconds if present
    const t = s.replace(' ', 'T');
    return t.length >= 16 ? t.slice(0, 16) : t;
};

// helper: convert datetime-local back to DB format "YYYY-MM-DD HH:MM:SS"
const toDbDateTime = (local: string | null | undefined) => {
    if (!local) return null;
    const s = local.toString().trim().replace('T', ' ');
    // ensure seconds
    return s.length === 16 ? `${s}:00` : s; // if user somehow provides without minutes
};

// init form with values converted to datetime-local for inputs
const form = useForm({
    start_time: formatForDatetimeLocal(meeting.value?.start_time ?? ''),
    end_time: formatForDatetimeLocal(meeting.value?.end_time ?? ''),
    info: meeting.value?.info ?? '',
    pin: meeting.value?.pin ?? ''
});

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

    // Prepare payload: convert datetime-local back to DB datetime format
    const payload = {
        start_time: toDbDateTime(form.start_time),
        end_time: toDbDateTime(form.end_time),
        info: form.info,
        pin: form.pin
    };

    // assign converted values into the form before posting (useForm expects form fields)
    form.start_time = payload.start_time ?? '';
    form.end_time = payload.end_time ?? '';
    form.info = payload.info ?? '';
    form.pin = payload.pin ?? '';

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
                <h1 class="text-2xl font-semibold dark:text-gray-100">Meeting #{{ meeting?.id }}</h1>
                <div class="flex gap-2">
                    <button @click="goBack" class="px-3 py-1 rounded border dark:border-gray-700 dark:text-gray-100">Back</button>
                    <button @click="exportMeeting" class="px-3 py-1 rounded bg-gray-100 dark:bg-gray-700 dark:text-gray-100">Print / PDF</button>
                </div>
            </div>

            <!-- Changed: make a 3-column grid for Start / End / PIN -->
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm dark:text-gray-200">Start time</label>
                    <input v-model="form.start_time" type="datetime-local" class="w-full border rounded p-2 bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700" />
                </div>
                <div>
                    <label class="block text-sm dark:text-gray-200">End time</label>
                    <input v-model="form.end_time" type="datetime-local" class="w-full border rounded p-2 bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700" />
                </div>
                <div>
                    <label class="block text-sm dark:text-gray-200">PIN</label>
                    <input v-model="form.pin" type="text" class="w-full border rounded p-2 bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700" />
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm dark:text-gray-200">Info</label>
                <input v-model="form.info" type="text" class="w-full border rounded p-2 bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700" />
            </div>

            <div class="mb-4">
                <button @click.prevent="save" class="px-4 py-2 bg-primary text-white rounded dark:text-black">Save</button>
            </div>

            <div>
                <!-- search bar -->
                <div class="mb-3">
                    <input v-model="search" type="search" placeholder="Search attendees by name or number..." class="w-full border rounded p-2 bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100 dark:border-gray-700" />
                </div>

                <h2 class="text-lg font-medium mb-2 dark:text-gray-100">Attendees ({{ filteredRegs.length }} / {{ meeting?.regs?.length ?? 0 }})</h2>

                <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded shadow w-full p-2">
                    <table class="min-w-full">
                        <thead>
                            <tr class="text-left">
                                <th class="p-2 text-gray-700 dark:text-gray-200">#</th>
                                <th class="p-2 text-gray-700 dark:text-gray-200">Name</th>
                                <th class="p-2 text-gray-700 dark:text-gray-200">Number</th>
                                <th class="p-2 text-gray-700 dark:text-gray-200">Sessions</th>
                                <th class="p-2 text-gray-700 dark:text-gray-200">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(r, i) in filteredRegs" :key="r.id" class="border-t border-gray-200 dark:border-gray-700">
                                <td class="p-2 text-gray-800 dark:text-gray-100">{{ i + 1 }}</td>
                                <td class="p-2 text-gray-800 dark:text-gray-100">{{ r.name }}</td>
                                <td class="p-2 text-gray-800 dark:text-gray-100">{{ r.number }}</td>
                                <td class="p-2 text-gray-800 dark:text-gray-100">{{ r.sessions_attended }}</td>
                                <td class="p-2"><button @click.prevent="removeAttendee(r.id)" class="text-red-600 dark:text-red-400">Remove</button></td>
                            </tr>
                            <tr v-if="filteredRegs.length === 0" class="border-t border-gray-200 dark:border-gray-700">
                                <td class="p-2 text-gray-800 dark:text-gray-100" colspan="5">No attendees found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
