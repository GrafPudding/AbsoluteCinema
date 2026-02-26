<script setup lang="ts">
const route = useRoute()
const config = useRuntimeConfig()
const client = useCookie<string>('cinema_client')
const clientToken = computed(() => client.value || '')

const showtimeId = computed(() => String(route.params.id))
const seats = computed(() => {
  const q = route.query.seats
  const raw = Array.isArray(q) ? q[0] : String(q || '')
  return raw ? raw.split(',').filter(Boolean) : []
})

const busy = ref(false)
const message = ref('')

const totalPrice = computed(() => seats.value.length * 100) // €100 per seat for demo

async function pay() {
  message.value = ''
  if (seats.value.length < 1) {
    message.value = 'No seats selected.'
    return
  }
  busy.value = true
  try {
    await $fetch(`${config.public.apiBase}/showtimes/${showtimeId.value}/buy`, {
      method: 'POST',
      headers: { 'X-Client-Token': clientToken.value },
      body: { seats: seats.value },
    })
    await navigateTo(`/success?showtime=${showtimeId.value}&seats=${seats.value.join(',')}`)
  } catch (e: any) {
    message.value = e?.data?.message || 'Payment failed (reservation may have expired).'
  } finally {
    busy.value = false
  }
}

async function cancel() {
  message.value = ''
  if (seats.value.length < 1) {
    await navigateTo(`/showtime/${showtimeId.value}`)
    return
  }
  busy.value = true
  try {
    await $fetch(`${config.public.apiBase}/showtimes/${showtimeId.value}/cancel`, {
      method: 'POST',
      headers: { 'X-Client-Token': clientToken.value },
      body: { seats: seats.value },
    })
    await navigateTo(`/showtime/${showtimeId.value}`)
  } catch (e: any) {
    message.value = e?.data?.message || 'Cancel failed.'
  } finally {
    busy.value = false
  }
}

</script>


<template>
  <div class="max-w-lg mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-cinema-800">Payment</h1>
      <NuxtLink to="/" class="text-cinema-600 hover:text-cinema-800 font-medium">&larr; Back</NuxtLink>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-cinema-100 p-6 space-y-4">
      <div class="flex justify-between py-3 border-b border-cinema-100">
        <span class="text-cinema-600">Showtime</span>
        <span class="font-semibold text-cinema-900">#{{ showtimeId }}</span>
      </div>
      <div class="flex justify-between py-3 border-b border-cinema-100">
        <span class="text-cinema-600">Seats</span>
        <span class="font-semibold text-cinema-900">{{ seats.join(', ') || '—' }}</span>
      </div>
      <div class="flex justify-between py-3">
        <span class="text-cinema-600 font-medium">Total</span>
        <span class="font-bold text-cinema-800 text-xl">€{{ totalPrice }}</span>
      </div>
    </div>

    <div v-if="message" class="text-red-600 bg-red-50 p-4 rounded-lg">{{ message }}</div>

    <div class="flex gap-3">
      <button class="flex-1 bg-cinema-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-cinema-700 transition-colors disabled:opacity-50" :disabled="busy" @click="pay">
        {{ busy ? 'Processing...' : 'Pay Now' }}
      </button>

      <button class="border border-cinema-300 text-cinema-700 px-6 py-3 rounded-lg font-medium hover:bg-cinema-50 transition-colors disabled:opacity-50" :disabled="busy" @click="cancel">
        Cancel
      </button>
    </div>
  </div>
</template>
