<script setup lang="ts">
type SeatStatus = 'available' | 'reserved' | 'bought'

type SeatDTO = {
  seat: string
  status: SeatStatus
  reservedByMe: boolean
}

type SeatsResponse = {
  showtime: {
    id: number
    movie_id: number
    starts_at: string
    auditorium: string
  }
  layout: { rows: number; cols: number }
  seats: SeatDTO[]
}

const route = useRoute()
const config = useRuntimeConfig()

const client = useCookie<string>('cinema_client')
const clientToken = computed(() => client.value || '')

const showtimeId = computed(() => String(route.params.id))

onMounted(async () => {
  const ok = await enter()

  if (ok) {
    await refreshSeats()     // <-- THIS fixes your “need to click Refresh” issue
    startHeartbeat()
    return
  }

  // queue mode
  queueTimer = setInterval(async () => {
    const ok2 = await enter()
    if (ok2) {
      stopTimers()
      await refreshSeats()
      startHeartbeat()
    }
  }, 2000)

  window.addEventListener('beforeunload', leaveBeacon)
})

onBeforeUnmount(() => {
  stopTimers()
  window.removeEventListener('beforeunload', leaveBeacon)
})

const { data, pending, error, refresh: refreshSeats } = await useFetch<SeatsResponse>(
  () => `${config.public.apiBase}/showtimes/${showtimeId.value}/seats`,
  {
    immediate: false,
    default: () => ({
      showtime: { id: 0, movie_id: 0, starts_at: '', auditorium: 'A' },
      layout: { rows: 8, cols: 10 },
      seats: [],
    }),
    headers: computed(() => ({
      'X-Client-Token': clientToken.value,
    })),
  }
)

type EnterResponse = {
  ok: boolean
  allowed: boolean
  position: number | null
  capacity: number
  ttl_seconds: number
}

const allowed = ref(false)
const queuePosition = ref<number | null>(null)
const queueCapacity = ref(5)
const gateError = ref<string>('')

let queueTimer: ReturnType<typeof setInterval> | null = null
let heartbeatTimer: ReturnType<typeof setInterval> | null = null

async function enter() {
  gateError.value = ''
  try {
    const res = await $fetch<EnterResponse>(
      `${config.public.apiBase}/showtimes/${showtimeId.value}/enter`,
      {
        method: 'POST',
        headers: { 'X-Client-Token': clientToken.value },
      }
    )

    allowed.value = !!res.allowed
    queuePosition.value = res.position
    queueCapacity.value = res.capacity
    return res.allowed
  } catch (e: any) {
    gateError.value =
      e?.data?.message ||
      e?.message ||
      'Failed to enter queue (check backend /enter route).'
    allowed.value = false
    queuePosition.value = null
    return false
  }
}

function startHeartbeat() {
  if (heartbeatTimer) return
  heartbeatTimer = setInterval(async () => {
    try {
      await $fetch(`${config.public.apiBase}/showtimes/${showtimeId.value}/heartbeat`, {
        method: 'POST',
        headers: { 'X-Client-Token': clientToken.value },
      })
    } catch {
      // don't spam UI; heartbeat is best-effort
    }
  }, 10000)
}

function stopTimers() {
  if (queueTimer) clearInterval(queueTimer)
  queueTimer = null
  if (heartbeatTimer) clearInterval(heartbeatTimer)
  heartbeatTimer = null
}

function leaveBeacon() {
  // best effort on tab close
  try {
    const url = `${config.public.apiBase}/showtimes/${showtimeId.value}/leave`
    navigator.sendBeacon(url, new Blob([], { type: 'text/plain' }))
  } catch {}
}


const selected = ref<string[]>([])
const busy = ref(false)
const message = ref<string>('')

const seatsMap = computed<Record<string, SeatDTO>>(() => {
  const m: Record<string, SeatDTO> = {}
  for (const s of data.value.seats) m[s.seat] = s
  return m
})

function seatClass(s: SeatDTO | undefined, isSelected: boolean) {
  if (!s) return 'bg-white/60 border-cinema-300/50 backdrop-blur-sm'
  if (s.status === 'bought') return 'bg-cinema-300/40 text-cinema-500/60 border-cinema-300/30 cursor-not-allowed'
  if (s.status === 'reserved' && !s.reservedByMe) return 'bg-cinema-400/30 text-cinema-700/60 border-cinema-400/30 cursor-not-allowed'
  if (s.status === 'reserved' && s.reservedByMe) return 'bg-cinema-600/80 text-white border-cinema-700 shadow-soft'
  if (isSelected) return 'bg-cinema-700 text-white border-cinema-800 shadow-glamour'
  return 'bg-white/80 border-cinema-400/60 hover:bg-cinema-100 hover:border-cinema-500 hover:shadow-soft backdrop-blur-sm'
}

function toggleSeat(seatCode: string) {
  message.value = ''
  const seat = seatsMap.value[seatCode]
  if (!seat) return

  // Can't select bought/reserved-by-others
  if (seat.status === 'bought') return
  if (seat.status === 'reserved' && !seat.reservedByMe) return

  const idx = selected.value.indexOf(seatCode)
  if (idx >= 0) {
    selected.value.splice(idx, 1)
    return
  }

  if (selected.value.length >= 5) {
    message.value = 'You can select up to 5 seats.'
    return
  }

  selected.value.push(seatCode)
}

async function reserveSelected() {
  message.value = ''

  const seatsToReserve = [...selected.value] // snapshot (IMPORTANT)

  if (seatsToReserve.length === 0) {
    message.value = 'Select at least one seat.'
    return
  }

  busy.value = true
  try {
    await $fetch(`${config.public.apiBase}/showtimes/${showtimeId.value}/reserve`, {
      method: 'POST',
      headers: { 'X-Client-Token': clientToken.value },
      body: { seats: seatsToReserve },
    })

    // Navigate with query object (more reliable than string concat)
    await navigateTo({
      path: `/pay/${showtimeId.value}`,
      query: { seats: seatsToReserve.join(',') },
    })
  } catch (e: any) {
    message.value = e?.data?.message || 'Failed to reserve seats.'
    await refreshSeats()
  } finally {
    busy.value = false
  }
}

// purely for display
const rows = computed(() => ['A','B','C','D','E','F','G','H'])
const cols = computed(() => Array.from({ length: 10 }, (_, i) => i + 1))
const seatCode = (r: string, c: number) => `${r}${c}`

const prettyTime = computed(() => {
  const iso = data.value.showtime.starts_at
  if (!iso) return ''
  return new Date(iso).toLocaleString([], { weekday: 'short', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' })
})
</script>

<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between border-b border-cinema-300/40 pb-4">
      <div>
        <h1 class="text-3xl font-display font-bold text-cinema-900">Select Your Seats</h1>
        <div class="text-cinema-600 mt-1 font-light tracking-wide">
          Showtime #{{ showtimeId }} &bull; {{ prettyTime }} &bull; Hall {{ data.showtime.auditorium }}
        </div>
      </div>
      <NuxtLink to="/" class="text-cinema-600 hover:text-cinema-900 font-medium tracking-wide transition-colors">&larr; Back to Movies</NuxtLink>
    </div>

    <div v-if="!allowed" class="bg-white/70 backdrop-blur-md rounded-2xl shadow-glamour border border-cinema-300/30 p-12 text-center space-y-6 max-w-xl mx-auto">
      <div class="w-20 h-20 mx-auto rounded-full bg-cinema-200/50 flex items-center justify-center">
        <svg class="w-10 h-10 text-cinema-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <div class="text-2xl font-display font-semibold text-cinema-800">You're in the Queue</div>

      <div v-if="gateError" class="text-red-600/80 bg-red-50/80 p-4 rounded-lg backdrop-blur-sm">
        {{ gateError }}
      </div>
      <div v-else class="text-cinema-600">
        <span class="text-cinema-500">Position:</span> <b class="text-cinema-900 text-xl">{{ queuePosition ?? '...' }}</b>
        <span class="text-cinema-400 mx-2">|</span>
        <span class="text-cinema-500">Capacity:</span> <b class="text-cinema-800">{{ queueCapacity }}</b>
      </div>

      <div class="text-sm text-cinema-400 font-light italic">Retrying every 2 seconds...</div>

      <div class="flex gap-4 justify-center pt-4">
        <button class="bg-cinema-700 text-white px-8 py-3 rounded-xl hover:bg-cinema-800 transition-all shadow-soft hover:shadow-glamour font-medium" @click="enter">
          Retry Now
        </button>
        <NuxtLink class="border border-cinema-400/50 text-cinema-700 px-8 py-3 rounded-xl hover:bg-cinema-100/80 transition-colors font-medium" to="/">Back</NuxtLink>
      </div>
    </div>

    <div v-else>
      <div v-if="pending" class="text-center py-20 text-cinema-500 font-light text-xl">Loading your experience...</div>
      <pre v-else-if="error" class="text-red-600 bg-red-50 p-6 rounded-xl">{{ error }}</pre>

      <div v-else class="grid lg:grid-cols-3 gap-8 items-start">
        <div class="lg:col-span-2 order-2 lg:order-1">
          <div class="bg-white/60 backdrop-blur-md rounded-2xl shadow-glamour border border-cinema-300/30 p-8">
            <div class="flex items-center justify-between mb-6">
              <div class="text-lg">
                <span class="text-cinema-500 font-light">Selected:</span> <b class="text-cinema-900 text-2xl font-display">{{ selected.length }}</b><span class="text-cinema-400">/5</span>
                <span v-if="message" class="ml-6 text-red-500/80 bg-red-50/80 px-4 py-1.5 rounded-full text-sm backdrop-blur-sm">{{ message }}</span>
              </div>
            </div>

            <div class="relative mb-10">
              <div class="absolute inset-x-0 top-1/2 h-px bg-gradient-to-r from-transparent via-cinema-400/50 to-transparent"></div>
              <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 flex justify-center">
                <div class="bg-gradient-to-b from-cinema-300/40 to-cinema-400/30 px-16 py-2 rounded-b-3xl">
                  <span class="text-cinema-600/70 text-xs tracking-[0.3em] uppercase font-medium">Screen</span>
                </div>
              </div>
            </div>

            <div class="flex justify-center">
              <div class="bg-cinema-100/50 p-6 rounded-2xl">
                <div v-for="r in rows" :key="r" class="flex items-center gap-3 mb-3">
                  <div class="w-7 text-center text-sm text-cinema-500 font-medium">{{ r }}</div>

                  <button
                    v-for="c in cols"
                    :key="c"
                    class="w-11 h-11 border-2 rounded-xl text-sm font-medium transition-all duration-300 hover:scale-110"
                    :class="seatClass(seatsMap[seatCode(r,c)], selected.includes(seatCode(r,c)))"
                    @click="toggleSeat(seatCode(r,c))"
                  >
                    {{ c }}
                  </button>
                </div>
              </div>
            </div>

            <div class="flex flex-wrap gap-6 mt-8 justify-center text-sm">
              <div class="flex items-center gap-2.5"><span class="inline-block w-5 h-5 border-2 border-cinema-400/60 bg-white/80 rounded-lg backdrop-blur-sm"></span> <span class="text-cinema-600">Available</span></div>
              <div class="flex items-center gap-2.5"><span class="inline-block w-5 h-5 bg-cinema-700 text-white rounded-lg shadow-soft"></span> <span class="text-cinema-700">Selected</span></div>
              <div class="flex items-center gap-2.5"><span class="inline-block w-5 h-5 bg-cinema-400/30 border-2 border-cinema-400/30 rounded-lg"></span> <span class="text-cinema-600">Reserved</span></div>
              <div class="flex items-center gap-2.5"><span class="inline-block w-5 h-5 bg-cinema-300/40 border-2 border-cinema-300/30 rounded-lg"></span> <span class="text-cinema-500">Unavailable</span></div>
            </div>
          </div>
        </div>

        <div class="order-1 lg:order-2 lg:sticky lg:top-4">
          <div class="bg-gradient-to-br from-cinema-800 to-cinema-900 rounded-2xl shadow-glamour p-8 text-white">
            <h2 class="text-xl font-display font-semibold mb-6 tracking-wide">Your Selection</h2>
            
            <div class="space-y-4 mb-8">
              <div v-if="selected.length === 0" class="text-cinema-300/60 font-light italic py-4 text-center border border-dashed border-cinema-600/30 rounded-xl">
                No seats selected
              </div>
              <div v-else class="space-y-2">
                <div v-for="seat in selected" :key="seat" class="flex items-center justify-between bg-white/10 rounded-lg px-4 py-3 backdrop-blur-sm">
                  <span class="font-medium tracking-wider">Seat {{ seat }}</span>
                  <span class="text-cinema-200">€100.00</span>
                </div>
              </div>
            </div>

            <div class="border-t border-cinema-600/40 pt-4 mb-8">
              <div class="flex items-center justify-between text-lg">
                <span class="text-cinema-300">Total</span>
                <span class="font-display font-bold text-xl">${{ (selected.length * 100).toFixed(2) }}</span>
              </div>
            </div>

            <div class="space-y-3">
              <button
                class="w-full bg-white text-cinema-900 px-8 py-4 rounded-xl font-semibold hover:bg-cinema-100 transition-all shadow-lg hover:shadow-xl disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:shadow-lg"
                :disabled="busy || selected.length === 0"
                @click="reserveSelected"
              >
                {{ busy ? 'Processing...' : 'Continue to Payment' }}
              </button>

              <NuxtLink class="block text-center border border-cinema-500/50 text-cinema-300 px-6 py-3 rounded-xl hover:bg-white/10 transition-colors" to="/">Cancel</NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
