<script setup lang="ts">
const props = withDefaults(defineProps<{
  name: string
  size?: number
  color?: string
  stroke?: number
  style?: Record<string, any>
}>(), {
  size: 22,
  color: 'currentColor',
  stroke: 2,
})
</script>

<template>
  <svg :width="props.size" :height="props.size" viewBox="0 0 24 24" :style="props.style" aria-hidden="true">
    <g v-if="props.name === 'home'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M3 11l9-7 9 7" /><path d="M5 10v10h14V10" />
    </g>
    <g v-else-if="props.name === 'route'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="6" cy="6" r="2.4" /><circle cx="18" cy="18" r="2.4" /><path d="M8.4 6H14a3.5 3.5 0 0 1 0 7H9a3.5 3.5 0 0 0 0 7h6.6" />
    </g>
    <g v-else-if="props.name === 'bike'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="6" cy="16.5" r="3.5" /><circle cx="18" cy="16.5" r="3.5" /><path d="M6 16.5l4-7h5l3 7M9.5 9.5h5M10 9.5l2.5 7" />
    </g>
    <g v-else-if="props.name === 'gift'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <rect x="4" y="9" width="16" height="11" rx="1.5" /><path d="M4 12.5h16M12 9v11M12 9c-2.5 0-4-1-4-2.6S9.3 4 12 6.4C14.7 4 16 4.8 16 6.4S14.5 9 12 9z" />
    </g>
    <g v-else-if="props.name === 'user'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="8.5" r="3.6" /><path d="M5 20c.7-3.6 3.6-5.5 7-5.5s6.3 1.9 7 5.5" />
    </g>
    <g v-else-if="props.name === 'gauge'" :stroke-width="props.stroke">
      <path fill="none" :stroke="props.color" stroke-linecap="round" stroke-linejoin="round" d="M4 16a8 8 0 1 1 16 0" />
      <path fill="none" :stroke="props.color" stroke-linecap="round" stroke-linejoin="round" d="M12 16l4-4" />
      <circle cx="12" cy="16" r="1.4" :fill="props.color" stroke="none" />
    </g>
    <g v-else-if="props.name === 'bolt'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M13 2L4 14h7l-1 8 9-12h-7l1-8z" />
    </g>
    <g v-else-if="props.name === 'wind'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M3 8h11a3 3 0 1 0-3-3M3 16h14a3 3 0 1 1-3 3M3 12h17" />
    </g>
    <g v-else-if="props.name === 'drop'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z" />
    </g>
    <g v-else-if="props.name === 'shield'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3z" />
    </g>
    <g v-else-if="props.name === 'target'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="12" r="8" /><circle cx="12" cy="12" r="3.4" /><path d="M12 2v3M12 19v3M2 12h3M19 12h3" />
    </g>
    <g v-else-if="props.name === 'loc'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11z" /><circle cx="12" cy="10" r="2.6" />
    </g>
    <g v-else-if="props.name === 'arrow'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M5 12h14M13 6l6 6-6 6" />
    </g>
    <g v-else-if="props.name === 'chev'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M9 6l6 6-6 6" />
    </g>
    <g v-else-if="props.name === 'chevL'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M15 6l-6 6 6 6" />
    </g>
    <g v-else-if="props.name === 'chevDown'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M6 9l6 6 6-6" />
    </g>
    <g v-else-if="props.name === 'x'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M6 6l12 12M18 6L6 18" />
    </g>
    <g v-else-if="props.name === 'plus'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 5v14M5 12h14" />
    </g>
    <g v-else-if="props.name === 'minus'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M5 12h14" />
    </g>
    <g v-else-if="props.name === 'leaf'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M5 19c0-8 6-13 14-13 0 8-5 14-13 14M5 19c2-4 4.5-6.5 8-8.5" />
    </g>
    <g v-else-if="props.name === 'fire'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 3c1 3 4 4.5 4 8a4 4 0 1 1-8 0c0-1.4.5-2.4 1-3 .2 1 .8 1.6 1.6 1.8C10.5 8 11.6 5.5 12 3z" />
    </g>
    <g v-else-if="props.name === 'star'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 3l2.6 5.6 6 .7-4.4 4 1.2 6-5.4-3-5.4 3 1.2-6L3.4 9.3l6-.7L12 3z" />
    </g>
    <g v-else-if="props.name === 'starF'">
      <path :fill="props.color" stroke="none" d="M12 3l2.6 5.6 6 .7-4.4 4 1.2 6-5.4-3-5.4 3 1.2-6L3.4 9.3l6-.7L12 3z" />
    </g>
    <g v-else-if="props.name === 'cal'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <rect x="4" y="5" width="16" height="16" rx="2.5" /><path d="M4 9h16M8 3v4M16 3v4" />
    </g>
    <g v-else-if="props.name === 'users'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="9" cy="8.5" r="3" /><path d="M3.5 19c.6-3 2.7-4.5 5.5-4.5s4.9 1.5 5.5 4.5" /><path d="M16 5.5a3 3 0 0 1 0 6M17.5 19c-.2-2-1-3.4-2.3-4.2 2.4-.6 5 .8 5.6 4.2" />
    </g>
    <g v-else-if="props.name === 'cart'">
      <circle cx="9" cy="20" r="1.5" :fill="props.color" stroke="none" /><circle cx="17" cy="20" r="1.5" :fill="props.color" stroke="none" />
      <path fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l2.2 11.2a1.5 1.5 0 0 0 1.5 1.2h8.4a1.5 1.5 0 0 0 1.5-1.2L20.5 8H6" />
    </g>
    <g v-else-if="props.name === 'bell'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M6 9a6 6 0 1 1 12 0c0 5 2 6 2 6H4s2-1 2-6z" /><path d="M10 19a2 2 0 0 0 4 0" />
    </g>
    <g v-else-if="props.name === 'speed'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="13" r="8" /><path d="M12 13l4.5-4.5" /><path d="M12 3v2" />
    </g>
    <g v-else-if="props.name === 'thermo'">
      <path fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round" d="M10 4a2 2 0 1 1 4 0v9a4 4 0 1 1-4 0V4z" />
      <circle cx="12" cy="17" r="1.6" :fill="props.color" stroke="none" />
    </g>
    <g v-else-if="props.name === 'flag'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M6 21V4M6 4h11l-2 4 2 4H6" />
    </g>
    <g v-else-if="props.name === 'medal'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="14" r="5" /><path d="M9 9L7 3M15 9l2-6M12 12l.8 1.6 1.7.2-1.2 1.2.3 1.7-1.6-.9-1.6.9.3-1.7-1.2-1.2 1.7-.2L12 12z" />
    </g>
    <g v-else-if="props.name === 'settings'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="12" r="3" /><path d="M12 2v3M12 19v3M22 12h-3M5 12H2M19 5l-2 2M7 17l-2 2M19 19l-2-2M7 7L5 5" />
    </g>
    <g v-else-if="props.name === 'edit'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M4 20h4l10-10-4-4L4 16v4z" /><path d="M13.5 6.5l4 4" />
    </g>
    <g v-else-if="props.name === 'layers'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 3l9 5-9 5-9-5 9-5z" /><path d="M3 13l9 5 9-5M3 16.5l9 5 9-5" />
    </g>
    <g v-else-if="props.name === 'nav'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M12 3l8 18-8-4-8 4 8-18z" />
    </g>
    <g v-else-if="props.name === 'check'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M5 12l4.5 4.5L19 7" />
    </g>
    <g v-else-if="props.name === 'qr'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <rect x="4" y="4" width="6" height="6" rx="1" /><rect x="14" y="4" width="6" height="6" rx="1" /><rect x="4" y="14" width="6" height="6" rx="1" /><path d="M14 14h3v3M20 14v6M14 20h3" />
    </g>
    <g v-else-if="props.name === 'recycle'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M7 17l-2.5-1.5 3-5 2 1.2M17 9l1.2 2.7 5.3-1.5M9 6l1.6-2.5L13 5l1.5 2.4" /><path d="M5 14l-1 4h5M19 11l2 3-2.5 3.5h-5M14 4.5l1.7 2.6 3-1" />
    </g>
    <g v-else-if="props.name === 'trophy'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M7 4h10v4a5 5 0 0 1-10 0V4z" /><path d="M7 6H4v1a3 3 0 0 0 3 3M17 6h3v1a3 3 0 0 1-3 3M9 17h6M10 13v4M14 13v4M8 20h8" />
    </g>
    <g v-else-if="props.name === 'search'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="11" cy="11" r="6.5" /><path d="M16 16l4 4" />
    </g>
    <g v-else-if="props.name === 'sun'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5L19 19M19 5l-1.5 1.5M6.5 17.5L5 19" />
    </g>
    <g v-else-if="props.name === 'cloud'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M7 18a4 4 0 0 1 0-8 5 5 0 0 1 9.6-1.4A3.5 3.5 0 0 1 17 18H7z" />
    </g>
    <g v-else-if="props.name === 'play'">
      <path :fill="props.color" stroke="none" d="M7 4l13 8-13 8V4z" />
    </g>
    <g v-else-if="props.name === 'pause'">
      <rect x="6" y="5" width="4" height="14" rx="1" :fill="props.color" stroke="none" /><rect x="14" y="5" width="4" height="14" rx="1" :fill="props.color" stroke="none" />
    </g>
    <g v-else-if="props.name === 'mtn'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M3 20l6-11 4 6 2-3 6 8H3z" />
    </g>
    <g v-else-if="props.name === 'crown'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M4 8l3 9h10l3-9-5 4-3-6-3 6-5-4z" />
    </g>
    <g v-else-if="props.name === 'logout'" fill="none" :stroke="props.color" :stroke-width="props.stroke" stroke-linecap="round" stroke-linejoin="round">
      <path d="M14 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8" /><path d="M11 12h10M17 8l4 4-4 4" />
    </g>
  </svg>
</template>
