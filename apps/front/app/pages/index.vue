<script setup lang="ts"></script>

<template>
  <div class="screen" style="background: radial-gradient(120% 80% at 50% 8%, #F5F7F0 0%, var(--bg) 50%, #DEE3D6 100%)">
    <div class="screen-scroll" style="padding-bottom: 34px; display: flex; flex-direction: column">
      <div class="pad safe-top rise" style="padding-top: 50px">
        <div class="between">
          <div style="line-height: 1">
            <div style="font-size: 30px; font-weight: 800; letter-spacing: -.03em; color: var(--ink)">
              AUR<span style="color: var(--yellow)">O</span>RA
            </div>
            <div class="eyebrow" style="margin-top: 6px; color: var(--ink-3)">by Michelin · 2 Wheels</div>
          </div>
          <MichelinLogo :height="26" />
        </div>
      </div>

      <div class="rise d1" style="height: 320px; margin-top: 6px">
        <TireScene />
      </div>

      <div class="pad rise d2" style="margin-top: 4px">
        <div class="eyebrow" style="color: var(--lime-600)">Cycling Intelligence Platform</div>
        <div class="h-xl" style="margin-top: 10px; max-width: 300px">
          La liaison au sol,<br>devenue intelligence.
        </div>
        <div class="body" style="margin-top: 12px; max-width: 300px">
          Itinéraires sur-mesure, pression dynamique, télémétrie temps réel et récompenses à chaque kilomètre.
        </div>
      </div>

      <div class="pad rise d3" style="margin-top: 22px; display: flex; flex-direction: column; gap: 12px">
        <NuxtLink to="/register" class="btn btn-blue btn-block">
          Créer mon profil cycliste <Icon name="arrow" :size="20" />
        </NuxtLink>
        <NuxtLink to="/login" class="btn btn-tertiary btn-block">
          J'ai déjà un compte
        </NuxtLink>
        <div class="tiny" style="text-align: center; margin-top: 4px">
          Michelin LB 2 Wheels · Route · Gravel · VTT · VAE
        </div>
      </div>
    </div>
  </div>
</template>
