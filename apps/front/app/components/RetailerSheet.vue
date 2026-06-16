<script setup lang="ts">
interface Retailer {
  name: string
  sub: string
  url: string
}

const { product, close } = useRetailerSheet()
const { show } = useToast()
const apiBase = useApiBase()

const { data: retailers } = await useFetch<Retailer[]>(() => `${apiBase}/api/retailers`, { key: 'retailers', default: () => [] })

const name = computed(() => product.value?.name || 'Produit Michelin')

function go(r: Retailer) {
  try {
    window.open(r.url + encodeURIComponent('michelin ' + name.value), '_blank', 'noopener')
  } catch {
    // ignore popup blockers
  }
  show('Redirection vers ' + r.name, 'arrow')
  close()
}
</script>

<template>
  <Sheet :open="!!product" @close="close">
    <div class="row" style="gap: 12px; margin-bottom: 6px">
      <div style="width: 44px; height: 44px; border-radius: 13px; background: var(--blue); display: flex; align-items: center; justify-content: center; flex: 0 0 auto">
        <Icon name="cart" :size="22" color="#fff" />
      </div>
      <div style="flex: 1">
        <div class="h-sm">Choisir un revendeur agréé</div>
        <div class="tiny" style="margin-top: 2px">{{ name }}</div>
      </div>
    </div>
    <div class="card" style="padding: 12px; margin: 12px 0 14px; display: flex; gap: 10px; align-items: flex-start; background: var(--blue-l1); border-color: transparent">
      <Icon name="shield" :size="18" color="var(--blue)" style="flex: 0 0 auto; margin-top: 1px" />
      <span class="tiny" style="color: var(--ink-2)">Michelin ne vend pas en direct. Votre commande est traitée par l'un de nos <b>revendeurs agréés</b>.</span>
    </div>
    <div style="display: flex; flex-direction: column; gap: 10px">
      <button v-for="r in retailers" :key="r.name" class="card" style="padding: 13px; display: flex; gap: 12px; align-items: center; text-align: left; width: 100%" @click="go(r)">
        <div style="width: 42px; height: 42px; border-radius: 11px; background: var(--surface-2); display: flex; align-items: center; justify-content: center; flex: 0 0 auto; font-weight: 800; color: var(--ink); font-size: 16px">
          {{ r.name[0] }}
        </div>
        <div style="flex: 1; min-width: 0">
          <div class="small" style="font-weight: 700; color: var(--ink)">{{ r.name }}</div>
          <div class="tiny" style="margin-top: 1px">{{ r.sub }}</div>
        </div>
        <span class="badge badge-lime">En stock</span>
        <Icon name="arrow" :size="18" color="var(--mute)" />
      </button>
    </div>
    <div class="tiny" style="text-align: center; margin-top: 14px">Prix indicatifs · stock confirmé sur le site du revendeur</div>
  </Sheet>
</template>
