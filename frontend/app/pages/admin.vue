<script setup lang="ts">
type Movie = {
  id: number
  title: string
  duration_minutes: number | null
  description: string | null
  poster_url: string | null
  showtimes: Showtime[]
}

type Showtime = {
  id: number
  movie_id: number
  starts_at: string
  auditorium: string
}

const config = useRuntimeConfig()

const { data: movies, refresh } = await useFetch<Movie[]>(
  `${config.public.apiBase}/admin/movies`,
  { default: () => [] }
)

const title = ref('')
const startsAt = ref('')
const auditorium = ref('A')
const durationMinutes = ref<number | null>(null)
const description = ref('')
const saving = ref(false)
const error = ref('')
const success = ref('')

async function addMovie() {
  error.value = ''
  success.value = ''

  if (!title.value || !startsAt.value) {
    error.value = 'Title and date/time are required'
    return
  }

  saving.value = true
  try {
    await $fetch(`${config.public.apiBase}/admin/movies`, {
      method: 'POST',
      body: {
        title: title.value,
        starts_at: startsAt.value,
        auditorium: auditorium.value,
        duration_minutes: durationMinutes.value,
        description: description.value || null,
      },
    })

    title.value = ''
    startsAt.value = ''
    auditorium.value = 'A'
    durationMinutes.value = null
    description.value = ''
    success.value = 'Movie added successfully!'
    await refresh()
  } catch (e: any) {
    error.value = e?.data?.message || 'Failed to add movie'
  } finally {
    saving.value = false
  }
}

async function deleteMovie(id: number) {
  if (!confirm('Are you sure you want to delete this movie and all its showtimes?')) {
    return
  }

  try {
    await $fetch(`${config.public.apiBase}/admin/movies/${id}`, {
      method: 'DELETE',
    })
    await refresh()
  } catch (e: any) {
    alert(e?.data?.message || 'Failed to delete movie')
  }
}

const formatTime = (iso: string) =>
  new Date(iso).toLocaleString([], { weekday: 'short', hour: '2-digit', minute: '2-digit', month: 'short', day: '2-digit' })
</script>

<template>
  <div class="space-y-8">
    <h1 class="text-3xl font-bold text-cinema-800 border-b-2 border-cinema-300 pb-2">Admin Panel</h1>

    <div class="bg-white rounded-xl shadow-md p-6 border border-cinema-100">
      <h2 class="text-xl font-semibold text-cinema-900 mb-4">Add New Movie</h2>
      
      <form @submit.prevent="addMovie" class="space-y-4">
        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-cinema-700 mb-1">Film Title *</label>
            <input
              v-model="title"
              type="text"
              required
              class="w-full px-4 py-2 border border-cinema-300 rounded-lg focus:ring-2 focus:ring-cinema-500 focus:border-cinema-500"
              placeholder="Enter film name"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-cinema-700 mb-1">Date & Time *</label>
            <input
              v-model="startsAt"
              type="datetime-local"
              required
              class="w-full px-4 py-2 border border-cinema-300 rounded-lg focus:ring-2 focus:ring-cinema-500 focus:border-cinema-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-cinema-700 mb-1">Hall / Auditorium</label>
            <input
              v-model="auditorium"
              type="text"
              class="w-full px-4 py-2 border border-cinema-300 rounded-lg focus:ring-2 focus:ring-cinema-500 focus:border-cinema-500"
              placeholder="A"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-cinema-700 mb-1">Duration (minutes)</label>
            <input
              v-model.number="durationMinutes"
              type="number"
              min="1"
              class="w-full px-4 py-2 border border-cinema-300 rounded-lg focus:ring-2 focus:ring-cinema-500 focus:border-cinema-500"
              placeholder="120"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-cinema-700 mb-1">Description</label>
          <textarea
            v-model="description"
            rows="2"
            class="w-full px-4 py-2 border border-cinema-300 rounded-lg focus:ring-2 focus:ring-cinema-500 focus:border-cinema-500"
            placeholder="Optional description"
          ></textarea>
        </div>

        <div v-if="error" class="text-red-600 bg-red-50 p-3 rounded-lg">{{ error }}</div>
        <div v-if="success" class="text-green-600 bg-green-50 p-3 rounded-lg">{{ success }}</div>

        <button
          type="submit"
          :disabled="saving"
          class="bg-cinema-700 text-white px-6 py-2 rounded-lg hover:bg-cinema-800 transition-colors disabled:opacity-50"
        >
          {{ saving ? 'Adding...' : 'Add Movie' }}
        </button>
      </form>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 border border-cinema-100">
      <h2 class="text-xl font-semibold text-cinema-900 mb-4">Existing Movies</h2>
      
      <div v-if="!movies?.length" class="text-cinema-500 py-4">No movies yet</div>
      
      <div v-else class="space-y-4">
        <div v-for="m in movies" :key="m.id" class="flex items-center justify-between border border-cinema-200 rounded-lg p-4">
          <div class="flex-1">
            <div class="font-semibold text-cinema-900">{{ m.title }}</div>
            <div class="text-sm text-cinema-600">
              <span v-if="m.duration_minutes">{{ m.duration_minutes }} min</span>
              <span v-if="m.showtimes.length">
                &bull; {{ m.showtimes.length }} showtime(s)
                <span class="text-cinema-400">
                  ({{ m.showtimes.map(s => formatTime(s.starts_at) + ' Hall ' + s.auditorium).join(', ') }})
                </span>
              </span>
            </div>
          </div>
          <button
            @click="deleteMovie(m.id)"
            class="text-red-600 hover:text-red-800 border border-red-300 hover:bg-red-50 px-4 py-2 rounded-lg transition-colors"
          >
            Delete
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
