export default defineNuxtPlugin(() => {
  const cookie = useCookie<string>('cinema_client', {
    sameSite: 'lax',
    path: '/',
    // not httponly so frontend can read it; MVP only
  })

  /*if (!cookie.value) {
    cookie.value = crypto.randomUUID()
  }*/
  if (!cookie.value) {
    // Browser-only
    const uuid =
      (globalThis.crypto && 'randomUUID' in globalThis.crypto)
        ? globalThis.crypto.randomUUID()
        : `${Date.now()}-${Math.random().toString(16).slice(2)}`

    cookie.value = uuid
  }
})
