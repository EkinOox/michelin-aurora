<script setup lang="ts">
import * as THREE from 'three'

const el = useThreeScene(({ THREE: T, scene, camera, renderer }) => {
  renderer.setClearColor(0x000000, 0)
  camera.position.set(0, 0.4, 6.4)
  camera.lookAt(0, 0, 0)
  scene.add(new T.AmbientLight(0x9fb0d0, 0.6))
  const d = new T.DirectionalLight(0xffffff, 1.2)
  d.position.set(4, 5, 5)
  scene.add(d)
  const rim = new T.PointLight(0x84bd00, 1.2, 30)
  rim.position.set(-4, -2, 4)
  scene.add(rim)

  const globe = new T.Group()
  scene.add(globe)
  const R = 2.2
  const ball = new T.Mesh(new T.SphereGeometry(R, 48, 48), new T.MeshStandardMaterial({ color: 0x1c2a4a, roughness: 0.85, metalness: 0.2 }))
  globe.add(ball)
  const wire = new T.Mesh(new T.SphereGeometry(R * 1.004, 26, 18), new T.MeshBasicMaterial({ color: 0x3f5f9e, wireframe: true, transparent: true, opacity: 0.35 }))
  globe.add(wire)

  const dotGeo = new T.BufferGeometry()
  const pos: number[] = []
  for (let i = 0; i < 420; i++) {
    const u = Math.random()
    const v = Math.random()
    const th = 2 * Math.PI * u
    const ph = Math.acos(2 * v - 1)
    pos.push(R * 1.01 * Math.sin(ph) * Math.cos(th), R * 1.01 * Math.cos(ph), R * 1.01 * Math.sin(ph) * Math.sin(th))
  }
  dotGeo.setAttribute('position', new T.Float32BufferAttribute(pos, 3))
  globe.add(new T.Points(dotGeo, new T.PointsMaterial({ color: 0x84bd00, size: 0.045, transparent: true, opacity: 0.7 })))

  const a = new T.Vector3(-1.3, 0.9, 1.5).normalize().multiplyScalar(R)
  const b = new T.Vector3(1.4, -0.2, 1.4).normalize().multiplyScalar(R)
  const mid = a.clone().add(b).multiplyScalar(0.5).normalize().multiplyScalar(R * 1.55)
  const curve = new T.QuadraticBezierCurve3(a, mid, b)
  const tube = new T.Mesh(new T.TubeGeometry(curve, 80, 0.045, 10, false), new T.MeshStandardMaterial({ color: 0xfce500, emissive: 0xfce500, emissiveIntensity: 0.8, roughness: 0.3 }))
  globe.add(tube)
  ;[a, b].forEach((pp, i) => {
    const m = new T.Mesh(new T.SphereGeometry(0.1, 16, 16), new T.MeshStandardMaterial({ color: i ? 0x84bd00 : 0xfce500, emissive: i ? 0x84bd00 : 0xfce500, emissiveIntensity: 0.9 }))
    m.position.copy(pp.clone().multiplyScalar(1.03))
    globe.add(m)
  })

  const comet = new T.Mesh(new T.SphereGeometry(0.08, 14, 14), new T.MeshBasicMaterial({ color: 0xffffff }))
  globe.add(comet)
  const halo = new T.Mesh(new T.SphereGeometry(0.18, 14, 14), new T.MeshBasicMaterial({ color: 0xfce500, transparent: true, opacity: 0.35 }))
  globe.add(halo)

  globe.rotation.x = 0.3

  return {
    update(t: number) {
      globe.rotation.y = t * 0.18
      const p = (t * 0.16) % 1
      const pt = curve.getPoint(p)
      comet.position.copy(pt)
      halo.position.copy(pt)
    },
  }
})
</script>

<template>
  <div ref="el" class="canvas-wrap" style="width: 100%; height: 100%" />
</template>
