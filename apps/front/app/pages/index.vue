<template>
  <div class="flex flex-col items-center justify-center min-h-screen gap-8 p-8">
    <div class="text-center">
      <p class="text-[#FCE500] font-mono text-xs tracking-widest uppercase mb-2">Michelin LB 2 Wheels</p>
      <h1 class="text-5xl font-black italic">
        <span class="text-[#FCE500]">Michelin</span> Aurora
      </h1>
      <p class="text-gray-400 mt-2 text-sm">Cycling Intelligence Platform</p>
    </div>

    <div class="w-12 h-0.5 bg-[#FCE500] rounded" />

    <div class="bg-[#27509B] rounded-lg px-6 py-4 text-center max-w-sm w-full">
      <p class="font-mono text-[10px] text-[#FCE500] uppercase tracking-widest mb-2">API Status</p>
      <p v-if="pending" class="text-gray-300 text-sm">Connexion en cours…</p>
      <p v-else-if="error" class="text-red-400 text-sm">⚠ API inaccessible</p>
      <p v-else class="text-green-400 font-bold">✓ {{ data?.message }}</p>
    </div>

    <div class="grid grid-cols-3 gap-4 text-center text-xs text-gray-500 font-mono">
      <div><div class="text-[#FCE500] font-bold text-base">M01</div><div>Curated Routes</div></div>
      <div><div class="text-[#FCE500] font-bold text-base">M02</div><div>Pressure Guide</div></div>
      <NuxtLink to="/dashboard" class="hover:text-[#FCE500] transition-colors">
        <div class="text-[#FCE500] font-bold text-base">M03</div><div>Live Telemetry</div>
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()

// En SSR (dans le conteneur Docker) on passe par le réseau interne Docker.
// En CSR (dans le navigateur) on passe par le port exposé sur l'hôte.
const apiBase = import.meta.server
  ? config.apiBaseInternal
  : config.public.apiBase

const { data, pending, error } = await useFetch<{ message: string }>(
  `${apiBase}/api/health`
)
</script>
