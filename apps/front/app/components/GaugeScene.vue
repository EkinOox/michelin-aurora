<script setup lang="ts">
import * as THREE from 'three'

const props = withDefaults(defineProps<{
  value?: number
  color?: number
}>(), {
  value: 0.5,
  color: 0x84bd00,
})

const valRef = ref(props.value)
watch(() => props.value, (v) => { valRef.value = v })

const el = useThreeScene(({ THREE: T, scene, camera, renderer }) => {
  renderer.setClearColor(0x000000, 0)
  camera.position.set(0, -0.2, 7.6)
  camera.lookAt(0, 0.1, 0)
  scene.add(new T.AmbientLight(0xffffff, 0.7))
  const d = new T.DirectionalLight(0xffffff, 1.4)
  d.position.set(2, 4, 6)
  scene.add(d)
  const p = new T.PointLight(props.color, 1.6, 24)
  p.position.set(0, 0, 5)
  scene.add(p)

  const g = new T.Group()
  g.rotation.x = -0.5
  scene.add(g)
  const RANGE = (240 * Math.PI) / 180
  const START = (210 * Math.PI) / 180

  const track = new T.Mesh(
    new T.TorusGeometry(2.1, 0.16, 16, 120, RANGE),
    new T.MeshStandardMaterial({ color: 0xe7e8e3, roughness: 0.6, metalness: 0.1 }),
  )
  track.rotation.z = -Math.PI / 6
  g.add(track)

  const activeMat = new T.MeshStandardMaterial({ color: props.color, emissive: props.color, emissiveIntensity: 0.55, roughness: 0.35, metalness: 0.2 })
  const active = new T.Mesh(new T.TorusGeometry(2.1, 0.2, 16, 120, RANGE * props.value), activeMat)
  active.rotation.z = -Math.PI / 6
  g.add(active)

  const tickMat = new T.MeshStandardMaterial({ color: 0xb9beb2, roughness: 0.7 })
  for (let i = 0; i <= 12; i++) {
    const a = START - (i / 12) * RANGE
    const big = i % 3 === 0
    const tk = new T.Mesh(new T.BoxGeometry(big ? 0.07 : 0.04, big ? 0.34 : 0.2, 0.05), tickMat)
    const rr = 2.46
    tk.position.set(Math.cos(a) * rr, Math.sin(a) * rr, 0)
    tk.rotation.z = a - Math.PI / 2
    g.add(tk)
  }

  const needle = new T.Group()
  const nMat = new T.MeshStandardMaterial({ color: 0x15171a, roughness: 0.35, metalness: 0.5 })
  const blade = new T.Mesh(new T.CylinderGeometry(0.015, 0.07, 1.95, 12), nMat)
  blade.position.y = 0.95
  needle.add(blade)
  const hub = new T.Mesh(new T.CylinderGeometry(0.26, 0.26, 0.18, 28), new T.MeshStandardMaterial({ color: 0x15171a, roughness: 0.3, metalness: 0.7 }))
  hub.rotation.x = Math.PI / 2
  needle.add(hub)
  const hubR = new T.Mesh(new T.TorusGeometry(0.26, 0.04, 10, 28), activeMat)
  needle.add(hubR)
  g.add(needle)

  let shown = props.value

  return {
    update(t: number) {
      const target = valRef.value + Math.sin(t * 6) * 0.006
      shown += (target - shown) * 0.08
      const ang = START - RANGE * shown
      needle.rotation.z = ang - Math.PI / 2
      active.geometry.dispose()
      active.geometry = new T.TorusGeometry(2.1, 0.2, 16, 120, Math.max(0.001, RANGE * shown))
      g.rotation.y = Math.sin(t * 0.4) * 0.12
    },
  }
})
</script>

<template>
  <div ref="el" class="canvas-wrap" style="width: 100%; height: 100%" />
</template>
