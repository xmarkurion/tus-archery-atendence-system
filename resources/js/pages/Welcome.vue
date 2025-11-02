<script setup lang="ts">
import { dashboard, login } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onBeforeUnmount, onMounted, computed } from 'vue';
import { useDateFormat } from '@vueuse/core';


withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

// Form fields and state for quick registration
const name = ref('');
const number = ref('');
const message = ref('');
const loading = ref(false);
// timeout id to auto-clear success message
const timeoutId = ref<number | null>(null);

function setMessage(text: string, autoHide = true) {
    message.value = text;

    if (timeoutId.value !== null) {
        clearTimeout(timeoutId.value);
        timeoutId.value = null;
    }

    if (autoHide) {
        timeoutId.value = window.setTimeout(() => {
            message.value = '';
            timeoutId.value = null;
        }, 2000);
    }
}

onBeforeUnmount(() => {
    if (timeoutId.value !== null) {
        clearTimeout(timeoutId.value);
        timeoutId.value = null;
    }
});

// meeting flow state
const meeting = ref<any | null>(null);
const pinNeeded = ref(false);
const pinValue = ref('');
const pinVerified = ref(false);

// computed helper: can register when a meeting exists AND (no pin required OR pin verified)
const canRegisterLocal = computed(() => {
    return meeting.value !== null && (!pinNeeded.value || pinVerified.value);
});

async function loadTodayMeeting() {
    try {
        const url = `${window.location.origin}/api/meeting/today`;
        console.log('loadTodayMeeting -> fetching', url);
        const res = await fetch(url, { credentials: 'same-origin' });
        console.log('loadTodayMeeting -> response status', res.status);
        if (!res.ok) {
            console.warn('loadTodayMeeting -> fetch not ok');
            return;
        }
        const data = await res.json();
        console.log('loadTodayMeeting -> data', data);
        meeting.value = data.meeting;
        pinNeeded.value = !!(meeting.value && meeting.value.requires_pin);
        pinVerified.value = !pinNeeded.value;
    } catch (err) { console.error('loadTodayMeeting -> error', err); }
}

// helper to read cookie
const getCookie = (name: string) => {
    const match = document.cookie.match(new RegExp('(^|;)\\s*' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : null;
};

async function verifyPin() {
    if (!meeting.value) return setMessage('No meeting today', false);
    if (!pinValue.value) return setMessage('Enter pin', false);
    loading.value = true;
    try {
        const xsrf = getCookie('XSRF-TOKEN');
        const metaToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const res = await fetch(`${window.location.origin}/api/meeting/verify-pin`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': metaToken,
                'X-XSRF-TOKEN': xsrf || ''
            },
            credentials: 'same-origin',
            body: JSON.stringify({ meeting_id: meeting.value.id, pin: pinValue.value }),
        });
        const data = await res.json();
        if (res.ok && data.ok) {
            pinVerified.value = true;
            setMessage('PIN verified', true);
        } else {
            setMessage(data.message || 'Wrong PIN', false);
        }
    } catch (err) { void err; setMessage('Network error', false); } finally {
        loading.value = false;
    }
}

async function submitForm() {
    // clear any previous message immediately when user presses OK
    setMessage('');

    if (!number.value) {
        setMessage('Please enter a student number.', false);
        return;
    }
    if (!name.value) {
        setMessage('Please enter a name.', false);
        return;
    }

    if (!meeting.value) {
        setMessage('No session today.', false);
        return;
    }

    if (pinNeeded.value && !pinVerified.value) {
        setMessage('Please verify PIN before registering', false);
        return;
    }

    loading.value = true;
    try {
        const xsrf = getCookie('XSRF-TOKEN');
        const metaToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const res = await fetch(`${window.location.origin}/api/meeting/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': metaToken,
                'X-XSRF-TOKEN': xsrf || ''
            },
            credentials: 'same-origin',
            body: JSON.stringify({ meeting_id: meeting.value.id, pin: pinValue.value, name: name.value, number: number.value }),
        });
        const data = await res.json();
        if (res.ok && data.success) {
            // show success and auto-hide after 2s
            setMessage(data.message || `Number ${number.value} was registered.` , true);
            // reset fields
            name.value = '';
            number.value = '';
            pinValue.value = '';
            pinVerified.value = false;

            // reload meeting to update attendees count or pin requirement
            await loadTodayMeeting();
        } else {
            // show error but do not auto-hide
            setMessage(data.message || 'There was an error registering.', false);
        }
    } catch (err) { void err; setMessage('Network error', false); } finally {
        loading.value = false;
    }
}

// call loader immediately and onMounted to ensure it runs in all navigation contexts
void loadTodayMeeting();
onMounted(() => {
    void loadTodayMeeting();
});
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div
        class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]"
    >
        <header
            class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl"
        >
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        Staff Log in
                    </Link>
<!--                    <Link-->
<!--                        v-if="canRegister"-->
<!--                        :href="register()"-->
<!--                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"-->
<!--                    >-->
<!--                        Register-->
<!--                    </Link>-->
                </template>
            </nav>
        </header>
        <div
            class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0"
        >
            <main
                class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row"
            >
                <div
                    class="flex-1 rounded-br-lg rounded-bl-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:rounded-tl-lg lg:rounded-br-none lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                >
                    <h1 class="mb-1 font-medium">Let's register for session</h1>
                    <p class="mb-2 text-[#706f6c] dark:text-[#A1A09A]">
                        Enter your student credentials. <br />
                        This will allow us to get more funding and comply with the sport department.
                    </p>

                    <!-- Inserted centered quick registration form -->
                    <div class="mt-8 flex items-center justify-center">
                        <div class="w-full max-w-md bg-white dark:bg-[#0b0b0b] p-6 rounded shadow text-center">
                            <p class="mb-3 font-medium">Session | {{ useDateFormat(meeting.start_time, 'YYYY-MM-DD') }}</p>
                            <div v-if="meeting == null">
                                <p class="mb-2">No session today.</p>
                            </div>
                            <div v-else>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Attendees: {{ meeting.sessions_attended ?? 0 }}
                                </p>
<!--                                <pre class="text-xs text-left mt-2 break-words">{{ JSON.stringify(meeting, null, 2) }}</pre>-->
                            </div>
                            <div v-if="meeting && pinNeeded && !pinVerified" class="mb-3">
                                <input v-model="pinValue" type="text" placeholder="Session PIN" class="mb-2 w-full rounded border px-3 py-2 text-sm dark:bg-[#121212] dark:border-[#2b2b2b]" />
                                <button @click.prevent="verifyPin" :disabled="loading" class="inline-flex items-center justify-center rounded bg-[#1b1b18] px-4 py-2 text-sm text-white hover:opacity-90 disabled:opacity-60">Verify PIN</button>
                            </div>
                            <div v-if="canRegisterLocal">
                                <input
                                    v-model="name"
                                    type="text"
                                    placeholder="Full name (as in university records)"
                                    class="mb-2 w-full rounded border px-3 py-2 text-sm dark:bg-[#121212] dark:border-[#2b2b2b]"
                                    required
                                />
                                <input
                                    v-model="number"
                                    type="text"
                                    placeholder="Student number"
                                    class="mb-3 w-full rounded border px-3 py-2 text-sm dark:bg-[#121212] dark:border-[#2b2b2b]"
                                />
                                <button
                                    @click.prevent="submitForm"
                                    :disabled="loading"
                                    class="inline-flex items-center justify-center rounded bg-[#1b1b18] px-4 py-2 text-sm text-white hover:opacity-90 disabled:opacity-60"
                                >
                                    OK
                                </button>
                            </div>
                            <p v-if="message" class="mt-3 text-sm">{{ message }}</p>
                        </div>
                    </div>
                </div>
                <div
                    class="relative -mb-px aspect-335/376 w-full shrink-0 overflow-hidden rounded-t-lg bg-[#fff2f2] lg:mb-0 lg:-ml-px lg:aspect-auto lg:w-[438px] lg:rounded-t-none lg:rounded-r-lg dark:bg-[#1D0002]"
                >
                    <!-- Replace SVG artwork with archery.png image -->
                    <img
                        src="/archery.png"
                        alt="Archery"
                        class="w-full h-full object-contain"
                        loading="lazy"
                    />
                </div>
            </main>
        </div>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
