<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import settingsRoutes from '@/routes/meeting/settings';
import { Button } from '@/components/ui/button';
import { useDateFormat } from '@vueuse/core'

// typed props from Inertia
const props = defineProps<{
    settings?: { selected_days?: string[]; enabled?: boolean } | null;
    meetings?: Array<{
        id?: number;
        start_time?: string;
        end_time?: string | null;
        info?: string
        created_at?: string;
        pin?: string;
    }>;
    flash?: { status?: string } | null;
}>();

const settings = ref(props.settings ?? { selected_days: ['Friday'], enabled: true });
const meetings = ref(props.meetings ?? []);
const status = ref((props.flash && (props.flash as any).status) || null);

const days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

const form = useForm({ selected_days: settings.value.selected_days ?? [] });

// react to incoming prop changes from Inertia
watch(
    () => props.settings,
    (newSettings) => {
        if (newSettings) {
            settings.value = newSettings as any;
            form.selected_days = (newSettings as any).selected_days ?? form.selected_days;
        }
    },
    { immediate: true },
);

watch(
    () => props.meetings,
    (newMeetings) => {
        meetings.value = newMeetings ?? [];
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

// expose for template/type checking
defineExpose({ toggleDay, save, toggleEnabled, runNow });
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
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in meetings" :key="m.id" class="border-t">
                                <td class="p-2">{{ m.id }}</td>
                                <td class="p-2">{{ m.pin }}</td>
                                <td class="p-2"> {{ useDateFormat(m.created_at, 'YYYY-MM-DD  HH:mm:ss') }}</td>
                                <td class="p-2">{{ m.info }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
