import {
  Component, Input, ElementRef, ViewChild,
  AfterViewInit, OnDestroy, NgZone, signal,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { MeshoptDecoder } from 'three/addons/libs/meshopt_decoder.module.js';

// ─────────────────────────────────────────────────────────────────────────
// Carpeta pública donde viven todos los modelos 3D disponibles:
//   frontend/public/models/
// Cualquier archivo que se copie ahí con el patrón "model-3d-N.glb" y se
// añada a esta lista entra automáticamente en el sorteo aleatorio.
//
// IMPORTANTE: cada entrada de esta lista debe tener su archivo .glb
// correspondiente ya subido en /public/models/. Si añades una entrada
// sin subir el archivo, el sorteo la elegirá de vez en cuando, la carga
// fallará (404) y se mostrará el icono de cubo de fallback en su lugar.
// ─────────────────────────────────────────────────────────────────────────
const MODELOS_POR_DEFECTO = [
  '/models/model-3d-1.glb',
  '/models/model-3d-2.glb',
  // '/models/model-3d-3.glb',  // ← descomenta esta línea cuando subas el archivo
];

// Velocidad de giro sobre el eje Y, en radianes/segundo.
// 0.12 ≈ una vuelta completa cada ~52s (giro lento y continuo).
const VELOCIDAD_ROTACION_DEFECTO = 0.12;

// Tamaño de la cuadrícula del halo 2D (NxN celdas). Impar para que
// haya una celda central limpia. Más alto = cubitos más pequeños y
// más densos.
const HALO_GRID = 30;

interface CeldaHalo {
  x: number;
  y: number;
  tam: number;
  opacidad: number;
}

@Component({
  selector: 'app-model-3d-viewer',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './model-3d-viewer.component.html',
  styleUrl: './model-3d-viewer.component.scss',
})
export class Model3dViewerComponent implements AfterViewInit, OnDestroy {

  @ViewChild('canvas', { static: true }) canvasRef!: ElementRef<HTMLCanvasElement>;

  // Lista de modelos entre los que elegir. Por defecto usa el pool común
  // de /public/models/, pero se puede acotar por instancia, p.ej.:
  //   <app-model-3d-viewer [modelos]="['/models/model-3d-2.glb']">
  @Input() modelos: string[] = MODELOS_POR_DEFECTO;

  // Radianes/segundo. Bajarlo = giro más lento.
  @Input() velocidadRotacion = VELOCIDAD_ROTACION_DEFECTO;

  // Halo 2D de fondo (estilo pixel-art) detrás del modelo. No es parte
  // de la escena 3D: es un SVG en capa aparte, por debajo del canvas.
  @Input() halo = true;
  @Input() colorHalo = '#00AADD';

  // Cuadrícula de "cubitos" del halo, calculada una vez (patrón fijo,
  // no depende del modelo cargado). Se consume desde el template con @for.
  readonly celdasHalo: CeldaHalo[] = this.generarCeldasHalo();

  cargando = signal(true);
  errorCarga = signal(false);

  private renderer!: THREE.WebGLRenderer;
  private scene!: THREE.Scene;
  private camera!: THREE.PerspectiveCamera;
  private grupoModelo = new THREE.Group();
  private clock = new THREE.Clock();
  private animFrame = 0;
  private resizeObs!: ResizeObserver;
  private reduceMotion = false;

  constructor(private ngZone: NgZone) {}

  ngAfterViewInit(): void {
    this.reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

    this.initThree();
    this.cargarModeloAleatorio();

    this.ngZone.runOutsideAngular(() => this.animate());

    this.resizeObs = new ResizeObserver(() => this.onResize());
    this.resizeObs.observe(this.canvasRef.nativeElement.parentElement!);
  }

  ngOnDestroy(): void {
    cancelAnimationFrame(this.animFrame);
    this.resizeObs?.disconnect();
    this.liberarModelo();
    this.renderer?.dispose();
  }

  // ── Halo 2D (cuadrícula pixel-art) ───────────────────────────────

  // Genera una nube circular de "cubitos" en coordenadas de un viewBox
  // 0-100, más opacos cerca del centro y desvaneciéndose hacia fuera.
  // Es un patrón fijo (no aleatorio) para que no "parpadee" distinto
  // en cada carga de página.
  private generarCeldasHalo(): CeldaHalo[] {
    const centro = (HALO_GRID - 1) / 2;
    const maxDist = centro * 1.05;
    const cell = 100 / HALO_GRID;
    const celdas: CeldaHalo[] = [];

    for (let i = 0; i < HALO_GRID; i++) {
      for (let j = 0; j < HALO_GRID; j++) {
        const dx = i - centro;
        const dy = j - centro;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist > maxDist) continue;

        const t = 1 - dist / maxDist;
        const opacidad = Math.pow(t, 1.6);
        if (opacidad < 0.04) continue;

        celdas.push({
          x: i * cell,
          y: j * cell,
          tam: cell * 0.82, // deja un pequeño hueco entre cubitos (efecto rejilla)
          opacidad,
        });
      }
    }
    return celdas;
  }

  // ── Three.js ──────────────────────────────────────────────────

  private initThree(): void {
    const canvas = this.canvasRef.nativeElement;
    const el = canvas.parentElement!;
    const w = el.clientWidth || 1;
    const h = el.clientHeight || 1;

    this.renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: true });
    this.renderer.setSize(w, h);
    this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    this.renderer.setClearColor(0x000000, 0); // fondo transparente: se ve el halo 2D por detrás

    this.scene = new THREE.Scene();
    this.scene.add(this.grupoModelo);

    this.camera = new THREE.PerspectiveCamera(32, w / h, 0.1, 1000);
    this.camera.position.set(2.4, 1.6, 3.2);
    this.camera.lookAt(0, 0, 0);

    this.scene.add(new THREE.AmbientLight(0xffffff, 0.9));

    const dirPrincipal = new THREE.DirectionalLight(0xffffff, 1.4);
    dirPrincipal.position.set(5, 8, 5);
    this.scene.add(dirPrincipal);

    // Luz de relleno con el color de acento de la marca (#00AADD),
    // para que el modelo case visualmente con el resto del banner.
    const dirAcento = new THREE.DirectionalLight(0x00aadd, 0.6);
    dirAcento.position.set(-4, 2, -3);
    this.scene.add(dirAcento);
  }

  private cargarModeloAleatorio(): void {
    if (!this.modelos?.length) {
      this.cargando.set(false);
      this.errorCarga.set(true);
      return;
    }

    const url = this.modelos[Math.floor(Math.random() * this.modelos.length)];
    const loader = new GLTFLoader();
    loader.setMeshoptDecoder(MeshoptDecoder);

    loader.load(
      url,
      (gltf) => {
        this.encuadrarModelo(gltf.scene);
        this.grupoModelo.add(gltf.scene);
        this.cargando.set(false);
      },
      undefined,
      (err) => {
        console.error(`[Model3dViewer] No se pudo cargar el modelo "${url}":`, err);
        this.cargando.set(false);
        this.errorCarga.set(true);
      },
    );
  }

  // Centra el modelo en el origen y coloca la cámara a una distancia que
  // encuadra su bounding box completo, sea cual sea la escala real del
  // .glb (cada modelo puede venir exportado a una escala distinta).
  private encuadrarModelo(objeto: THREE.Object3D): void {
    const box = new THREE.Box3().setFromObject(objeto);
    const centro = box.getCenter(new THREE.Vector3());
    const tamano = box.getSize(new THREE.Vector3());

    objeto.position.sub(centro);

    const radio = Math.max(tamano.length() / 2, 0.001);
    const fovRad = (this.camera.fov * Math.PI) / 180;
    const distancia = (radio / Math.sin(fovRad / 2)) * 1.12; // 1.12 = margen (menor = modelo más grande)

    const direccion = new THREE.Vector3(0.7, 0.5, 1).normalize();
    this.camera.position.copy(direccion.multiplyScalar(distancia));
    this.camera.near = distancia / 100;
    this.camera.far = distancia * 100;
    this.camera.lookAt(0, 0, 0);
    this.camera.updateProjectionMatrix();
  }

  private liberarModelo(): void {
    this.grupoModelo.traverse((child) => {
      if (child instanceof THREE.Mesh) {
        child.geometry?.dispose();
        const mats = Array.isArray(child.material) ? child.material : [child.material];
        mats.forEach((m) => m?.dispose());
      }
    });
  }

  private animate(): void {
    this.animFrame = requestAnimationFrame(() => this.animate());
    if (!this.reduceMotion) {
      const delta = this.clock.getDelta();
      this.grupoModelo.rotation.y += delta * this.velocidadRotacion;
    }
    this.renderer.render(this.scene, this.camera);
  }

  private onResize(): void {
    const el = this.canvasRef.nativeElement.parentElement!;
    const w = el.clientWidth || 1;
    const h = el.clientHeight || 1;
    this.camera.aspect = w / h;
    this.camera.updateProjectionMatrix();
    this.renderer.setSize(w, h);
  }
}