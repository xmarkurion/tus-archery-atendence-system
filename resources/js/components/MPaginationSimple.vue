<script setup lang="ts">
import { defineProps, computed, unref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'

export interface PaginationLinks {
  url: string | null
  label: string
  active: boolean
}

export interface DefaultPaginationData {
  current_page: number
  data: any[]
  first_page_url: string
  from: number | null
  last_page: number
  last_page_url: string
  links: PaginationLinks[]
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number | null
  total: number
}

const props = defineProps<{ items?: DefaultPaginationData | any }>()

// accept either a raw object or a ref to the paginator; normalize to value
const pager = computed(() => unref(props.items) ?? null)
const links = computed(() => (pager.value && Array.isArray(pager.value.links) ? pager.value.links : []))

const normalizeUrl = (url?: string | null) => {
  if (!url) return '#'
  try {
    const u = new URL(String(url), window.location.origin)
    return u.pathname + (u.search || '')
  } catch {
    return String(url)
  }
}
</script>

<template>
  <div v-if="links.length" class="flex flex-wrap items-center gap-2">
    <Link
      v-for="(link, index) in links"
      :key="index"
      :href="normalizeUrl(link.url)"
      :preserve-scroll="true"
      :preserve-state="false"
      :class="{ 'mr-1': true, 'pointer-events-none opacity-50': !link.url }"
    >
      <Button :variant="link.active ? 'default' : 'outline'" :disabled="!link.url">
        <span v-html="link.label" />
      </Button>
    </Link>
  </div>
</template>
