import * as THREE from 'three'
import type { Ref } from 'vue'

export interface ThreeSceneApi {
  update?: (t: number) => void
  dispose?: () => void
}

export interface ThreeSceneSetupArgs {
  THREE: typeof THREE
  scene: THREE.Scene
  camera: THREE.PerspectiveCamera
  renderer: THREE.WebGLRenderer
  w: number
  h: number
}

export function useThreeScene(
  setup: (args: ThreeSceneSetupArgs) => ThreeSceneApi | void,
): Ref<HTMLDivElement | null> {
  const el = ref<HTMLDivElement | null>(null)

  onMounted(() => {
    const container = el.value
    if (!container) return

    // Différer la création Three.js au temps mort du navigateur —
    // le premier paint et les interactions ne sont plus bloqués.
    const scheduleIdle = (window as any).requestIdleCallback
      ?? ((fn: () => void) => setTimeout(fn, 32))

    let mounted = true
    let raf = 0

    scheduleIdle(() => {
      if (!mounted) return

      const w = container.clientWidth || 320
      const h = container.clientHeight || 320

      const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' })
      renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2))
      renderer.setSize(w, h)
      renderer.outputColorSpace = THREE.SRGBColorSpace
      container.appendChild(renderer.domElement)

      const scene = new THREE.Scene()
      const camera = new THREE.PerspectiveCamera(40, w / h, 0.1, 100)

      const api = setup({ THREE, scene, camera, renderer, w, h }) || {}

      const tick = (t: number) => {
        if (!mounted) return
        api.update?.(t * 0.001)
        renderer.render(scene, camera)
        raf = requestAnimationFrame(tick)
      }
      raf = requestAnimationFrame(tick)

      const onResize = () => {
        const nw = container.clientWidth
        const nh = container.clientHeight
        if (!nw || !nh) return
        camera.aspect = nw / nh
        camera.updateProjectionMatrix()
        renderer.setSize(nw, nh)
      }
      const ro = new ResizeObserver(onResize)
      ro.observe(container)

      onUnmounted(() => {
        mounted = false
        cancelAnimationFrame(raf)
        ro.disconnect()
        api.dispose?.()
        scene.traverse((o: any) => {
          o.geometry?.dispose?.()
          if (o.material) {
            const mats = Array.isArray(o.material) ? o.material : [o.material]
            mats.forEach((m: any) => m.dispose?.())
          }
        })
        renderer.dispose()
        if (renderer.domElement.parentNode) renderer.domElement.parentNode.removeChild(renderer.domElement)
      })
    })

    onUnmounted(() => { mounted = false })
  })

  return el
}

export function addStandardLights(scene: THREE.Scene, opts: { lime?: boolean } = {}) {
  const lime = opts.lime ?? true
  scene.add(new THREE.AmbientLight(0xffffff, 0.55))
  const key = new THREE.DirectionalLight(0xffffff, 1.7)
  key.position.set(4, 6, 6)
  scene.add(key)
  const fill = new THREE.DirectionalLight(0xdfe6ff, 0.6)
  fill.position.set(-5, 2, 3)
  scene.add(fill)
  const rim = new THREE.PointLight(lime ? 0x9fd13a : 0xfce500, lime ? 1.4 : 1.0, 30)
  rim.position.set(-3, -2, 5)
  scene.add(rim)
  const top = new THREE.PointLight(0xffffff, 0.7, 40)
  top.position.set(0, 8, -2)
  scene.add(top)
}
