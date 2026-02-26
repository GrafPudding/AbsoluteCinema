<script setup lang="ts">
type Movie = {
  id: number
  title: string
  duration_minutes: number | null
  description: string | null
  poster_url: string | null
}

type Showtime = {
  id: number
  movie_id: number
  starts_at: string
  auditorium: string
}

const config = useRuntimeConfig()

const { data: movies } = await useFetch<Movie[]>(
  `${config.public.apiBase}/movies`,
  { default: () => [] }
)

const showtimesByMovie = ref<Record<number, Showtime[]>>({})

await Promise.all(
  movies.value.map(async (m) => {
    const { data } = await useFetch<Showtime[]>(
      `${config.public.apiBase}/movies/${m.id}/showtimes`,
      { default: () => [] }
    )
    showtimesByMovie.value[m.id] = data.value
  })
)

const formatTime = (iso: string) =>
  new Date(iso).toLocaleString([], { weekday: 'short', hour: '2-digit', minute: '2-digit', month: 'short', day: '2-digit' })
</script>

<template>
  <div class="space-y-8">
    <h1 class="text-3xl font-bold text-cinema-800 border-b-2 border-cinema-300 pb-2">Now Playing</h1>

    <div v-for="m in movies" :key="m.id" class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 border border-cinema-100">
      <div class="p-6 space-y-4">
        <div class="font-bold text-xl text-cinema-900">{{ m.title }}</div>

        <div class="text-cinema-600" v-if="m.duration_minutes">
          <span class="font-medium">Duration:</span> {{ m.duration_minutes }} min
        </div>

        <div class="text-gray-600" v-if="m.description">{{ m.description }}</div>

        <div class="pt-2">
          <div class="text-sm font-semibold text-cinema-700 mb-3">Select time:</div>
          <div class="flex flex-wrap gap-3">
            <NuxtLink
              v-for="s in (showtimesByMovie[m.id] || [])"
              :key="s.id"
              :to="`/showtime/${s.id}`"
              class="bg-cinema-100 text-cinema-700 border border-cinema-300 rounded-lg px-4 py-2 hover:bg-cinema-500 hover:text-white hover:border-cinema-500 transition-all duration-200 font-medium"
            >
              {{ formatTime(s.starts_at) }} - Hall {{ s.auditorium }}
            </NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
