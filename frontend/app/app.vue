<template>
  <div class="min-h-screen bg-gradient-to-br from-cinema-50 via-cinema-100 to-cinema-200 text-gray-800 font-body">
    <header class="bg-gradient-to-r from-cinema-900 via-cinema-800 to-cinema-900 text-white py-6 shadow-xl">
      <div class="container mx-auto px-6">
        <h1 class="text-3xl font-display font-bold tracking-widest uppercase">Absolute Cinema</h1>
        <p class="text-cinema-300 text-sm tracking-wider mt-1 font-light">Premium Experience</p>
      </div>
    </header>

    <main class="container mx-auto px-4 py-10">
      <NuxtPage />
    </main>

    <footer class="bg-cinema-950 text-cinema-400 py-6 mt-auto">
      <div class="container mx-auto px-4 text-center">
        <p class="tracking-wide font-light">&copy; 2026 Absolute Cinema. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap');

body {
  font-family: 'Cormorant Garamond', serif;
}

.font-display {
  font-family: 'Playfair Display', serif;
}
</style>