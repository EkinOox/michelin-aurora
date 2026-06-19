<script setup lang="ts">
interface Retailer {
  name: string
  sub: string
  url: string
}

const { product, close } = useRetailerSheet()
const { show } = useToast()

const { data: retailers } = await useApiFetch<Retailer[]>('/api/retailers', { key: 'retailers', default: () => [] })

const tireName = computed(() => product.value?.name ?? 'Produit Michelin')
const accentColor = computed(() => product.value?.color_token ?? '#27509B')

// Group by sub (country flag + name)
const grouped = computed(() => {
  const map = new Map<string, Retailer[]>()
  for (const r of (retailers.value ?? [])) {
    const list = map.get(r.sub) ?? []
    list.push(r)
    map.set(r.sub, list)
  }
  return Array.from(map.entries()).map(([country, items]) => ({ country, items }))
})

function domainOf(url: string) {
  return url.replace(/https?:\/\/(www\.)?/, '').replace(/\/$/, '')
}

function go(r: Retailer) {
  // URLs ending with = or & are search endpoints — append the tire name
  const url = /[=&]$/.test(r.url)
    ? r.url + encodeURIComponent('michelin ' + tireName.value)
    : r.url
  try {
    window.open(url, '_blank', 'noopener,noreferrer')
  } catch {
    // ignore popup blockers
  }
  show('Redirection vers ' + r.name, 'arrow')
  close()
}
</script>

<template>
  <Sheet :open="!!product" @close="close">

    <!-- Header -->
    <div class="rs-header">
      <div class="rs-icon" :style="{ background: accentColor }">
        <Icon name="cart" :size="22" color="#fff" />
      </div>
      <div style="flex: 1; min-width: 0">
        <div class="h-sm">Revendeurs agréés</div>
        <div class="tiny rs-tire-name">{{ tireName }}</div>
      </div>
      <button class="iconbtn" @click="close">
        <Icon name="x" :size="18" color="var(--ink-3)" />
      </button>
    </div>

    <!-- Info banner -->
    <div class="rs-banner">
      <Icon name="shield" :size="16" color="var(--blue)" style="flex: 0 0 auto; margin-top: 1px" />
      <span class="tiny">Commandez chez un <b>revendeur agréé</b> pour bénéficier de la garantie produit.</span>
    </div>

    <!-- Retailer list grouped by country -->
    <div v-for="group in grouped" :key="group.country" class="rs-group">
      <div class="rs-country-label">{{ group.country }}</div>
      <button
        v-for="r in group.items"
        :key="r.name"
        class="card rs-row"
        @click="go(r)"
      >
        <!-- Logo letter -->
        <div class="rs-logo" :style="{ background: accentColor + '18', color: accentColor }">
          {{ (r.name[0] ?? '?').toUpperCase() }}
        </div>
        <!-- Name + domain -->
        <div style="flex: 1; min-width: 0">
          <div class="small rs-name">{{ r.name }}</div>
          <div class="tiny rs-domain">{{ domainOf(r.url) }}</div>
        </div>
        <!-- Arrow -->
        <Icon name="arrow" :size="16" color="var(--mute)" />
      </button>
    </div>

    <div class="tiny rs-footer">Prix indicatifs · stock confirmé sur le site du revendeur</div>
  </Sheet>
</template>

<style scoped>
.rs-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
}
.rs-icon {
  width: 46px; height: 46px;
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  flex: 0 0 auto;
}
.rs-tire-name {
  margin-top: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--mute);
}

.rs-banner {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  padding: 11px 13px;
  border-radius: 14px;
  background: #E4EBF6;
  margin-bottom: 18px;
  color: var(--ink-2);
}

.rs-group {
  margin-bottom: 16px;
}
.rs-country-label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--mute);
  margin-bottom: 7px;
  padding-left: 2px;
}

.rs-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  text-align: left;
  width: 100%;
  margin-bottom: 7px;
  cursor: pointer;
  transition: opacity .15s;
  border-radius: 16px !important;
}
.rs-row:active { opacity: .75; }

.rs-logo {
  width: 40px; height: 40px;
  border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800;
  font-size: 17px;
  flex: 0 0 auto;
}
.rs-name {
  font-weight: 700;
  color: var(--ink);
}
.rs-domain {
  margin-top: 1px;
  color: var(--mute);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.rs-footer {
  text-align: center;
  margin-top: 6px;
  margin-bottom: 4px;
  color: var(--mute);
}
</style>
