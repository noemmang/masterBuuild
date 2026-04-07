import {
  Component, OnDestroy, ElementRef, ViewChild,
  AfterViewInit, signal, computed, ChangeDetectionStrategy, NgZone
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import * as THREE from 'three';

interface Gabinete {
  uuid: string;
  nombre: string;
  tipo: string;
  estructura: string;
  ancho_mm: number;
  alto_mm: number;
  profundidad_mm: number;
  longitud_gpu_max_mm: number;
  altura_cooler_max_mm: number;
  soporte_radiadores: number[]; // ej: [240, 280, 360]
  color: string;
  seleccionado: boolean;
}

type Vista = 'frente' | 'lateral' | 'superior' | '3d';

const COLORES = ['#4A90D9', '#E8A84C', '#6DBF8A', '#D96A6A'];

const GABINETES_MOCK: Omit<Gabinete, 'color' | 'seleccionado'>[] = [
  {
    uuid: '1', nombre: 'Fractal Define 7 XL', tipo: 'Full Tower', estructura: 'ATX/E-ATX',
    ancho_mm: 232, alto_mm: 568, profundidad_mm: 583,
    longitud_gpu_max_mm: 491, altura_cooler_max_mm: 185,
    soporte_radiadores: [120, 140, 240, 280, 360, 420],
  },
  {
    uuid: '2', nombre: 'Lian Li O11 Dynamic EVO', tipo: 'Mid Tower', estructura: 'ATX · Sandwich',
    ancho_mm: 285, alto_mm: 459, profundidad_mm: 459,
    longitud_gpu_max_mm: 420, altura_cooler_max_mm: 165,
    soporte_radiadores: [120, 240, 360],
  },
  {
    uuid: '3', nombre: 'Lian Li Terra', tipo: 'ITX', estructura: 'ITX · Sandwich',
    ancho_mm: 195, alto_mm: 340, profundidad_mm: 290,
    longitud_gpu_max_mm: 322, altura_cooler_max_mm: 130,
    soporte_radiadores: [120, 240],
  },
  {
    uuid: '4', nombre: 'be quiet! Silent Base 802', tipo: 'Mid Tower', estructura: 'ATX',
    ancho_mm: 243, alto_mm: 513, profundidad_mm: 553,
    longitud_gpu_max_mm: 369, altura_cooler_max_mm: 185,
    soporte_radiadores: [120, 140, 240, 280, 360],
  },
];

@Component({
  selector: 'app-case-viewer',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './case-viewer.component.html',
  styleUrl: './case-viewer.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class CaseViewerComponent implements AfterViewInit, OnDestroy {
  @ViewChild('canvas', { static: true }) canvasRef!: ElementRef<HTMLCanvasElement>;

  gabinetes = signal<Gabinete[]>(
    GABINETES_MOCK.map((g, i) => ({ ...g, color: COLORES[i], seleccionado: i < 2 }))
  );

  busqueda           = signal('');
  vistaActual        = signal<Vista>('3d');
  mostrarDiferencias = signal(false);
  readonly escala    = 10;

  private renderer!: THREE.WebGLRenderer;
  private scene!: THREE.Scene;
  private camera!: THREE.OrthographicCamera;
  private meshes: Map<string, THREE.Group> = new Map();
  private animFrame!: number;
  private resizeObs!: ResizeObserver;

  private isDragging = false;
  private prevMouse  = { x: 0, y: 0 };
  private spherical  = { theta: Math.PI / 4, phi: Math.PI / 3 };
  private readonly ORBIT_RADIUS = 180;

  readonly vistas: { key: Vista; label: string }[] = [
    { key: 'frente',   label: 'Frente'   },
    { key: 'lateral',  label: 'Lateral'  },
    { key: 'superior', label: 'Superior' },
    { key: '3d',       label: '3D'       },
  ];

  gabinetesFiltrados = computed(() =>
    this.gabinetes().filter(g =>
      g.nombre.toLowerCase().includes(this.busqueda().toLowerCase())
    )
  );

  // Ordenados de mayor a menor alto_mm para que en lateral el más pequeño no quede tapado
  gabinetesMostrados = computed(() =>
    this.gabinetes()
      .filter(g => g.seleccionado)
      .sort((a, b) => b.alto_mm - a.alto_mm)
  );

  diferencias = computed(() => {
    const gs = this.gabinetesMostrados();
    if (gs.length < 2) return null;

    const toRow = (label: string, key: keyof Gabinete) => {
      const valores = gs.map(g => ({ nombre: g.nombre, color: g.color, valor: g[key] as number }));
      const max = Math.max(...valores.map(v => v.valor));
      const min = Math.min(...valores.map(v => v.valor));
      return { campo: label, valores, max, diferencia: max - min };
    };

    return {
      dimensiones: [
        toRow('Alto',  'alto_mm'),
        toRow('Ancho', 'ancho_mm'),
        toRow('Fondo', 'profundidad_mm'),
      ],
      compatibilidad: [
        toRow('GPU max',         'longitud_gpu_max_mm'),
        toRow('Cooler aire max', 'altura_cooler_max_mm'),
      ],
    };
  });

  // Formato legible de AIOs: [240, 280, 360] → "240 / 280 / 360mm"
  getRadiadoresVisibles(radiadores: number[]): number[] {
    if (!radiadores || radiadores.length === 0) return [];
  
    const rads = [...radiadores].sort((a, b) => a - b);
  
    const grupo120 = rads.filter(r => r % 120 === 0);
    const grupo140 = rads.filter(r => r % 140 === 0);
  
    const max120 = grupo120.length ? Math.max(...grupo120) : null;
    const max140 = grupo140.length ? Math.max(...grupo140) : null;
  
    if (max120 && max140) return [max120, max140];
    if (max120) return [max120];
  
    return [];
  }

  formatRadiadores(radiadores: number[]): string {
    const visibles = this.getRadiadoresVisibles(radiadores);
    if (!visibles.length) return 'Sin AIO';
    return visibles.join(' / ') + 'mm';
  }

  constructor(private ngZone: NgZone) {}

  ngAfterViewInit(): void {
    this.initThree();
    this.construirEscena();
    this.setVista('3d');
    this.ngZone.runOutsideAngular(() => this.animate());
    this.resizeObs = new ResizeObserver(() => this.onResize());
    this.resizeObs.observe(this.canvasRef.nativeElement.parentElement!);
    this.bindMouse();
  }

  ngOnDestroy(): void {
    cancelAnimationFrame(this.animFrame);
    this.resizeObs?.disconnect();
    this.renderer?.dispose();
  }

  // ── Three.js ──────────────────────────────────────────────────

  private initThree(): void {
    const canvas = this.canvasRef.nativeElement;
    const w = canvas.parentElement!.clientWidth;
    const h = canvas.parentElement!.clientHeight;

    this.renderer = new THREE.WebGLRenderer({ canvas, antialias: true, alpha: false });
    this.renderer.setSize(w, h);
    this.renderer.setPixelRatio(window.devicePixelRatio);
    this.renderer.setClearColor(0x1a1c20, 1);

    this.scene = new THREE.Scene();

    const aspect = w / h;
    const f = 120;
    this.camera = new THREE.OrthographicCamera(
      -f * aspect / 2, f * aspect / 2, f / 2, -f / 2, -1000, 1000
    );

    this.scene.add(new THREE.AmbientLight(0xffffff, 0.7));
    const dir = new THREE.DirectionalLight(0xffffff, 0.6);
    dir.position.set(100, 200, 100);
    this.scene.add(dir);
    const dir2 = new THREE.DirectionalLight(0xffffff, 0.2);
    dir2.position.set(-100, -50, -100);
    this.scene.add(dir2);
  }

  private construirEscena(): void {
    this.meshes.forEach(g => this.scene.remove(g));
    this.meshes.clear();

    // Ordenados de mayor a menor: el más grande va primero (izquierda/fondo)
    const mostrados = [...this.gabinetes().filter(g => g.seleccionado)]
      .sort((a, b) => b.alto_mm - a.alto_mm);

    const totalAncho = mostrados.reduce((s, g) => s + g.ancho_mm / this.escala, 0);
    let offsetX = -totalAncho / 2;

    mostrados.forEach(gab => {
      const w = gab.ancho_mm       / this.escala;
      const h = gab.alto_mm        / this.escala;
      const d = gab.profundidad_mm / this.escala;

      const color = new THREE.Color(gab.color);
      const group = new THREE.Group();
      const geo   = new THREE.BoxGeometry(w, h, d);

      group.add(new THREE.Mesh(geo, new THREE.MeshLambertMaterial({ color })));

      const edgeColor = new THREE.Color(gab.color).lerp(new THREE.Color(0xffffff), 0.5);
      group.add(new THREE.LineSegments(
        new THREE.EdgesGeometry(geo),
        new THREE.LineBasicMaterial({ color: edgeColor })
      ));

      group.position.set(offsetX + w / 2, h / 2, 0);
      offsetX += w;

      this.scene.add(group);
      this.meshes.set(gab.uuid, group);
    });
  }

  setVista(vista: Vista): void {
    this.vistaActual.set(vista);
    this.construirEscena();

    const d      = this.ORBIT_RADIUS;
    const target = new THREE.Vector3(0, 30, 0);

    if (vista === '3d') {
      this.updateOrbitalCamera();
      return;
    }

    const configs: Record<Exclude<Vista, '3d'>, { pos: THREE.Vector3; up: THREE.Vector3 }> = {
      frente:   { pos: new THREE.Vector3(0, 30, d),    up: new THREE.Vector3(0, 1, 0) },
      lateral:  { pos: new THREE.Vector3(d, 30, 0),    up: new THREE.Vector3(0, 1, 0) },
      superior: { pos: new THREE.Vector3(0, d, 0.01),  up: new THREE.Vector3(0, 0, -1) },
    };
    const { pos, up } = configs[vista];
    this.camera.position.copy(pos);
    this.camera.up.copy(up);
    this.camera.lookAt(target);
    this.camera.updateProjectionMatrix();
  }

  private updateOrbitalCamera(): void {
    const { theta, phi } = this.spherical;
    const r = this.ORBIT_RADIUS;
    this.camera.position.set(
      r * Math.sin(phi) * Math.sin(theta),
      r * Math.cos(phi) + 30,
      r * Math.sin(phi) * Math.cos(theta)
    );
    this.camera.up.set(0, 1, 0);
    this.camera.lookAt(0, 30, 0);
    this.camera.updateProjectionMatrix();
  }

  private animate(): void {
    this.animFrame = requestAnimationFrame(() => this.animate());
    this.renderer.render(this.scene, this.camera);
  }

  private onResize(): void {
    const el = this.canvasRef.nativeElement.parentElement!;
    const w  = el.clientWidth;
    const h  = el.clientHeight;
    const aspect = w / h;
    const f = 120;
    this.camera.left   = -f * aspect / 2;
    this.camera.right  =  f * aspect / 2;
    this.camera.top    =  f / 2;
    this.camera.bottom = -f / 2;
    this.camera.updateProjectionMatrix();
    this.renderer.setSize(w, h);
  }

  // ── Orbital drag ──────────────────────────────────────────────

  private bindMouse(): void {
    const canvas = this.canvasRef.nativeElement;

    canvas.addEventListener('mousedown', (e) => {
      if (this.vistaActual() !== '3d') return;
      this.isDragging = true;
      this.prevMouse = { x: e.clientX, y: e.clientY };
    });
    window.addEventListener('mousemove', (e) => {
      if (!this.isDragging) return;
      const dx = e.clientX - this.prevMouse.x;
      const dy = e.clientY - this.prevMouse.y;
      this.prevMouse = { x: e.clientX, y: e.clientY };
      this.spherical.theta -= dx * 0.01;
      this.spherical.phi = Math.max(0.1, Math.min(Math.PI / 2 - 0.05,
        this.spherical.phi - dy * 0.01
      ));
      this.updateOrbitalCamera();
    });
    window.addEventListener('mouseup', () => { this.isDragging = false; });

    canvas.addEventListener('touchstart', (e) => {
      if (this.vistaActual() !== '3d') return;
      this.isDragging = true;
      this.prevMouse = { x: e.touches[0].clientX, y: e.touches[0].clientY };
    });
    window.addEventListener('touchmove', (e) => {
      if (!this.isDragging) return;
      const dx = e.touches[0].clientX - this.prevMouse.x;
      const dy = e.touches[0].clientY - this.prevMouse.y;
      this.prevMouse = { x: e.touches[0].clientX, y: e.touches[0].clientY };
      this.spherical.theta -= dx * 0.01;
      this.spherical.phi = Math.max(0.1, Math.min(Math.PI / 2 - 0.05,
        this.spherical.phi - dy * 0.01
      ));
      this.updateOrbitalCamera();
    });
    window.addEventListener('touchend', () => { this.isDragging = false; });
  }

  // ── UI ────────────────────────────────────────────────────────

  toggleGabinete(uuid: string): void {
    const target = this.gabinetes().find(g => g.uuid === uuid)!;
    if (!target.seleccionado && this.numSeleccionados() >= 4) return;
    this.gabinetes.update(gs =>
      gs.map(g => g.uuid === uuid ? { ...g, seleccionado: !g.seleccionado } : g)
    );
    this.construirEscena();
  }

  toggleDiferencias(): void {
    this.mostrarDiferencias.update(v => !v);
  }

  anadirGabinete(): void {
    console.log('Abrir selector de gabinetes');
  }

  setBusqueda(v: string): void {
    this.busqueda.set(v);
  }

  numSeleccionados(): number {
    return this.gabinetes().filter(g => g.seleccionado).length;
  }

  pct(valor: number, max: number): number {
    return Math.round((valor / max) * 100);
  }
}