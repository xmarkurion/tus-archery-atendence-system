<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps<{ regs: any[] }>();
const regs = ref(props.regs || []);
const editing = ref<number | null>(null);
const form = useForm({ name: '', number: '' });
const deleting = ref<number | null>(null);

const startEdit = (r: any) => {
    editing.value = r.id;
    form.name = r.name ?? '';
    form.number = r.number ?? '';
};

const cancelEdit = () => { editing.value = null; form.reset(); };

const saveEdit = (id: number) => {
    form.post(`/regs/${id}/update`, {
        onSuccess: () => {
            // reload page to refresh regs
            window.location.reload();
        }
    });
};

// helper to read cookie (XSRF)
const getCookie = (name: string) => {
    const match = document.cookie.match(new RegExp('(^|;)\\s*' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : null;
};

const deleteReg = async (id: number) => {
    if (! confirm('Delete registrant?')) return;
    deleting.value = id;
    const metaToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
    const xsrf = getCookie('XSRF-TOKEN');

    try {
        const res = await fetch(`/regs/${id}/delete`, {
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
                alert(j?.message || j?.error || `Failed to delete registrant (status ${res.status})`);
            } else {
                const text = await res.text();
                alert(text?.substring(0, 500) || `Failed to delete registrant (status ${res.status})`);
            }
            return;
        }

        if (ct.includes('application/json')) {
            const j = await res.json().catch(() => null);
            if (j && j.success) {
                window.location.reload();
            } else {
                alert(j?.message || 'Failed to delete registrant');
            }
        } else {
            // success but not JSON -> reload
            window.location.reload();
        }
    } catch (err) {
        console.error('deleteReg error', err);
        alert('Network error while deleting registrant');
    } finally {
        deleting.value = null;
    }
};
</script>

<template>
    <Head title="Registrants" />
    <AppLayout>
        <div class="p-6 max-w-4xl mx-auto">
            <h1 class="text-2xl font-semibold mb-4">Registrant Settings</h1>
            <div class="overflow-auto bg-white rounded shadow">
                <table class="w-full">
                    <thead>
                        <tr class="text-left">
                            <th class="p-2">ID</th>
                            <th class="p-2">Name</th>
                            <th class="p-2">Number</th>
                            <th class="p-2">Sessions</th>
                            <th class="p-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in regs" :key="r.id" class="border-t">
                            <td class="p-2">{{ r.id }}</td>
                            <td class="p-2">
                                <template v-if="editing === r.id">
                                    <input v-model="form.name" class="w-full border rounded p-1" />
                                </template>
                                <template v-else>{{ r.name }}</template>
                            </td>
                            <td class="p-2">
                                <template v-if="editing === r.id">
                                    <input v-model="form.number" class="w-full border rounded p-1" />
                                </template>
                                <template v-else>{{ r.number }}</template>
                            </td>
                            <td class="p-2">{{ r.sessions_attended }}</td>
                            <td class="p-2">
                                <template v-if="editing === r.id">
                                    <button @click.prevent="saveEdit(r.id)" class="px-2 py-1 bg-primary text-white rounded">Save</button>
                                    <button @click.prevent="cancelEdit" class="px-2 py-1 ml-2 rounded border">Cancel</button>
                                </template>
                                <template v-else>
                                    <button @click.prevent="startEdit(r)" class="px-2 py-1 bg-gray-100 rounded">Edit</button>
                                    <button @click.prevent="deleteReg(r.id)" class="px-2 py-1 ml-2 text-red-600" :disabled="deleting === r.id">
                                        <span v-if="deleting === r.id" class="animate-spin">⏳</span>
                                        <span v-else>Delete</span>
                                    </button>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
