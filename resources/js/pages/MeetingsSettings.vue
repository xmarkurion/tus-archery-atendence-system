<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import settingsRoutes from '@/routes/meeting/settings';
import { Button } from '@/components/ui/button';
import { useDateFormat } from '@vueuse/core'
import { Eye, Trash } from 'lucide-vue-next';
import MPaginationSimple from '@/components/MPaginationSimple.vue';

// typed props from Inertia
const props = defineProps<{
    settings?: { selected_days?: string[]; enabled?: boolean; default_start_time?: string; default_duration?: number } | null;
    // meetings is now a paginated object: { data: [], meta: { current_page, last_page, total }, links: [] }
    meetings?: any;
    flash?: { status?: string } | null;
}>();

const settings = ref(props.settings ?? { selected_days: ['Friday'], enabled: true, default_start_time: '18:00:00', default_duration: 60 });
// default shape for paginated meetings
const meetings = ref(props.meetings ?? { data: [], meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 }, links: [] });
const status = ref((props.flash && (props.flash as any).status) || null);

const days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

const form = useForm({ selected_days: settings.value.selected_days ?? [], default_start_time: settings.value?.default_start_time ?? '18:00', default_duration: settings.value?.default_duration ?? 60 });

// react to incoming prop changes from Inertia
watch(
    () => props.settings,
    (newSettings) => {
        if (newSettings) {
            settings.value = newSettings as any;
            form.selected_days = (newSettings as any).selected_days ?? form.selected_days;
            // normalize start time for time input (H:i)
            if ((newSettings as any).default_start_time) {
                const dst = (newSettings as any).default_start_time;
                // if milliseconds included, normalize to HH:MM
                const parts = dst.split(':');
                form.default_start_time = parts.length >= 2 ? `${parts[0].padStart(2,'0')}:${parts[1].padStart(2,'0')}` : dst;
            } else {
                form.default_start_time = '18:00';
            }
            form.default_duration = (newSettings as any).default_duration ?? form.default_duration;
        }
    },
    { immediate: true },
);

watch(
    () => props.meetings,
    (newMeetings) => {
        if (newMeetings) meetings.value = newMeetings;
    },
    { immediate: true },
);

watch(
    () => props.flash,
    (newFlash) => {
        status.value = (newFlash && (newFlash as any).status) || null;
    },
    { immediate: true },
);

const toggleDay = (day: string) => {
    const idx = form.selected_days.indexOf(day);
    if (idx === -1) form.selected_days.push(day);
    else form.selected_days.splice(idx, 1);
};

const save = () => {
    form.post(settingsRoutes.update().url, {
        onSuccess: () => {
            router.get(settingsRoutes.index().url, {}, { preserveState: false });
        },
    });
};

const toggleEnabled = () => {
    form.post(settingsRoutes.toggle().url, {
        onSuccess: () => {
            router.get(settingsRoutes.index().url, {}, { preserveState: false });
        },
    });
};

const runNow = () => {
    form.post(settingsRoutes.runnow().url, {
        onSuccess: () => {
            router.get(settingsRoutes.index().url, {}, { preserveState: false });
        },
    });
};

const enabled = computed(() => settings.value.enabled ?? true);

const deletingMeeting = ref<number | null>(null);

// Pagination helpers using server-provided meta
const items = computed(() => meetings.value?.data ?? []);
const meta = computed(() => meetings.value?.meta ?? { current_page: 1, last_page: 1, per_page: 10, total: 0 });
const totalPages = computed(() => Math.max(1, meta.value.last_page || 1));
const currentPage = computed(() => meta.value.current_page || 1);
// pages not needed when using MPaginationSimple

// remove pageLinks/visitUrl/normalizeUrl complexity; use numeric pages and Inertia router.get to request server page
onMounted(() => {
    try {
        console.debug('MeetingsSettings mounted, meetings prop:', meetings.value);
        console.debug('Pagination meta:', meta.value);
    } catch {
        // ignore
    }
});

watch(meetings, (nv) => {
    console.debug('Meetings prop changed:', nv);
});

const getCookie = (name: string) => {
    const match = document.cookie.match(new RegExp('(^|;)\\s*' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : null;
};

const openDetails = (id?: number) => {
    if (typeof id === 'undefined') return;
    // navigate to the standalone meeting view page
    router.get(`/meeting/${id}/view`);
};

const deleteMeeting = async (id?: number) => {
    if (typeof id === 'undefined') return;
    if (! confirm('Delete this meeting? This will remove all registrations for it.')) return;
    deletingMeeting.value = id;

    const metaToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const xsrf = getCookie('XSRF-TOKEN');

    try {
        const res = await fetch(`/meeting/${id}/delete`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': metaToken,
                'X-XSRF-TOKEN': xsrf || '',
                'Accept': 'application/json'
            },
            body: JSON.stringify({}),
        });

        const ct = res.headers.get('content-type') || '';
        if (!res.ok) {
            if (ct.includes('application/json')) {
                const j = await res.json().catch(() => null);
                alert(j?.message || `Failed to delete meeting (status ${res.status})`);
            } else {
                const text = await res.text();
                alert(text?.substring(0,500) || `Failed to delete meeting (status ${res.status})`);
            }
            return;
        }

        if (ct.includes('application/json')) {
            const j = await res.json().catch(() => null);
            if (j && j.success) {
                // refresh meetings list
                router.get(settingsRoutes.index.url(), {}, { preserveState: false });
            } else {
                alert(j?.message || 'Failed to delete meeting');
            }
        } else {
            router.get(settingsRoutes.index.url(), {}, { preserveState: false });
        }
    } catch (err) {
        console.error('deleteMeeting error', err);
        alert('Network error while deleting meeting');
    } finally {
        deletingMeeting.value = null;
    }
};

// remove goToPage/prevPage/nextPage navigation complexity; we'll use MPaginationSimple for links
// keep these functions in case buttons are used
const goToPage = (p: number) => {
    if (p < 1) p = 1;
    if (p > totalPages.value) p = totalPages.value;
    const url = settingsRoutes.index.url({ query: { page: p } });
    router.get(url, {}, { preserveState: false });
};

const prevPage = () => {
    const p = (meta.value.current_page ?? 1) - 1;
    if (p < 1) return;
    goToPage(p);
};

const nextPage = () => {
    const p = (meta.value.current_page ?? 1) + 1;
    if (p > totalPages.value) return;
    goToPage(p);
};

// expose for template/type checking
defineExpose({ toggleDay, save, toggleEnabled, runNow, goToPage, prevPage, nextPage, deleteMeeting, openDetails });
</script>

<template>
    <Head title="Meeting Settings" />

    <AppLayout :breadcrumbs="[{ title: 'Meetings', href: settingsRoutes.index().url }]">
        <div class="p-4">
            <div class="mb-4">
                <h1 class="text-lg font-semibold">Meeting Settings</h1>
            </div>

            <div v-if="status" class="mb-4">
                <div class="rounded bg-green-100 px-4 py-2 text-green-800">{{ status }}</div>
            </div>

            <div class="mb-4">
                <label class="block font-medium mb-2">Select days for auto-creation:</label>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="day in days"
                        :key="day"
                        :class="['px-3 py-1 rounded', form.selected_days.includes(day) ? 'bg-primary text-white' : 'bg-gray-100']"
                        type="button"
                        @click="toggleDay(day)"
                    >
                        {{ day }}
                    </button>
                </div>
            </div>

            <div class="flex gap-2">
                <Button :disabled="form.processing" @click.prevent="save">Save</Button>
                <Button :disabled="form.processing" variant="secondary" @click.prevent="toggleEnabled">{{ enabled ? 'Stop Automatic Creation' : 'Start Automatic Creation' }}</Button>
                <Button :disabled="form.processing" variant="default" @click.prevent="runNow">Run Now</Button>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium mb-1">Default start time</label>
                    <input v-model="form.default_start_time" type="time" class="w-full border rounded px-3 py-2" />
                    <p class="text-xs text-gray-500 mt-1">Used when scheduler creates meetings (HH:MM)</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Default duration (minutes)</label>
                    <input v-model.number="form.default_duration" type="number" min="1" max="1440" class="w-full border rounded px-3 py-2" />
                    <p class="text-xs text-gray-500 mt-1">Length of meeting in minutes</p>
                </div>
            </div>

            <div class="mt-6">
                <h2 class="text-md font-medium mb-2">Meetings</h2>
                <div class="overflow-auto bg-white rounded shadow-sm">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="text-left">
                                <th class="p-2">ID</th>
                                <th class="p-2">Pin</th>
                                <th class="p-2">Created at</th>
                                <th class="p-2">Info</th>
                                <th class="p-2">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in items" :key="m.id" class="border-t">
                                <td class="p-2">{{ m.id }}</td>
                                <td class="p-2">{{ m.pin }}</td>
                                <td class="p-2"> {{ useDateFormat(m.created_at, 'YYYY-MM-DD  HH:mm:ss')}}</td>
                                <td class="p-2">{{ m.info }}</td>
                                <td class="p-2 flex items-center gap-2">
                                    <button @click.prevent="openDetails(m.id)" title="View"><Eye/></button>
                                    <button @click.prevent="deleteMeeting(m.id)" :disabled="deletingMeeting === m.id" title="Delete" class="text-red-600">
                                        <template v-if="deletingMeeting === m.id">⏳</template>
                                        <template v-else><Trash/></template>
                                    </button>
                                </td>
                             </tr>
                             <tr v-if="(items && items.length) === 0" class="border-t">
                                <td class="p-2" colspan="5">No meetings found.</td>
                             </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination controls -->
                <div class="flex items-center justify-between mt-3">
                    <div class="text-sm text-gray-600">Page {{ currentPage }} of {{ totalPages }} — Total: {{ meta.total }}</div>
                    <div class="flex items-center gap-2">
                        <MPaginationSimple :items="meetings" class="flex gap-1" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
