<script setup lang="ts">
const route = useRoute()
const showtime = String(route.query.showtime || '')
const seats = String(route.query.seats || '')
</script>

<template>
  <div class="max-w-lg mx-auto text-center space-y-8 py-12">
    <div class="space-y-4">
      <div class="w-20 h-20 bg-cinema-100 rounded-full flex items-center justify-center mx-auto">
        <svg class="w-10 h-10 text-cinema-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
      </div>
      <h1 class="text-3xl font-bold text-cinema-800">Booking Confirmed!</h1>
      <p class="text-cinema-600">Your tickets have been reserved successfully.</p>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-cinema-100 p-6 space-y-4">
      <div class="flex justify-between py-2 border-b border-cinema-100">
        <span class="text-cinema-600">Showtime</span>
        <span class="font-semibold text-cinema-900">{{ showtime }}</span>
      </div>
      <div class="flex justify-between py-2">
        <span class="text-cinema-600">Seats</span>
        <span class="font-semibold text-cinema-900">{{ seats }}</span>
      </div>
    </div>

    <NuxtLink class="inline-block bg-cinema-600 text-white px-8 py-3 rounded-lg font-medium hover:bg-cinema-700 transition-colors" to="/">Back to Home</NuxtLink>
  </div>
</template>