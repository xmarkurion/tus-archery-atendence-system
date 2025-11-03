<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import meetingSettings from '@/routes/meeting/settings';
import { ref, onMounted, computed } from 'vue';
import { useDateFormat } from '@vueuse/core';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

// meeting state for dashboard display
const meeting = ref<any | null>(null);

const formattedDate = computed(() => {
    if (!meeting.value || !meeting.value.start_time) return '';
    try {
        return useDateFormat(meeting.value.start_time, 'YYYY-MM-DD').value || '';
    } catch {
        return '';
    }
});

async function loadTodayMeeting() {
    try {
        // Use the authenticated endpoint that includes the PIN for logged-in users
        const res = await fetch(`${window.location.origin}/api/meeting/today/with-pin`, { credentials: 'same-origin' });
        if (!res.ok) {
            // If not authorized or not found, treat as "no meeting" on the dashboard
            if (res.status === 401 || res.status === 403) {
                meeting.value = null;
                return;
            }
            console.warn('loadTodayMeeting -> non-ok response', res.status);
            meeting.value = null;
            return;
        }
        const data = await res.json();
        meeting.value = data.meeting;
    } catch (err) {
        console.error('loadTodayMeeting', err);
        meeting.value = null;
    }
}

onMounted(() => {
    void loadTodayMeeting();
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <div class="grid auto-rows-min gap-4 md:grid-cols-2">
                <a :href="meetingSettings.index().url">
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <div class="grid gap-1 text-center justify-items-center p-4">
                        <div class="flex flex-col items-center justify-center gap-2">
                            <span class="underline">Meeting Settings</span>
                            Adjust change meeting settings such as time, info, and PIN.
                        </div>
                    </div>

                </div>
                </a>

                <a href="/reg-settings">
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <div class="grid gap-1 text-center justify-items-center p-4">
                        <span class="underline">Registrant Settings</span>
                        <div class="text-sm">Manage registrants (edit/delete)</div>
                    </div>
                </div>
                </a>

            </div>
            <div
                class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
            >
                <div class="flex h-full w-full items-center justify-center p-8">
                    <div class="text-center">
                        <template v-if="meeting">
                            <div class="text-6xl font-extrabold tracking-tight">{{ meeting.pin ?? '—' }}</div>
                            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">Session | {{ formattedDate }}</div>
                            <div class="mt-2 max-w-xl text-sm text-gray-700 dark:text-gray-300">{{ meeting.info ?? '' }}</div>
                        </template>
                        <template v-else>
                            <div class="text-3xl font-semibold">No meeting today</div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.box {
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px dotted rgb(96 139 168);
}
</style>
