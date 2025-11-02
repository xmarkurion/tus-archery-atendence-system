<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';
import meetingSettings from '@/routes/meeting/settings';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
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
                <PlaceholderPattern />
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
