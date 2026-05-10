import {
  Component, OnInit, OnDestroy, ElementRef, ViewChild,
  AfterViewInit, signal, computed, ChangeDetectionStrategy, NgZone
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import * as THREE from 'three';
import { ComponenteService, Componente, ComponenteDetalle } from '../../core/services/componente.service';
import { Subject } from 'rxjs';
import { debounceTime, distinctUntilChanged, catchError } from 'rxjs/operators';
import { of, forkJoin } from 'rxjs';

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
  soporte_radiadores: number[];
  color: string;
}

type Vista = 'frente' | 'lateral' | 'superior' | '3d' | '3d-esquina';

const COLORES = ['#4A90D9', '#E8A84C', '#6DBF8A', '#D96A6A'];

@Component({
  selector: 'app-case-viewer',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './case-viewer.component.html',
  styleUrl: './case-viewer.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class CaseViewerComponent implements OnInit, AfterViewInit, OnDestroy {
  @ViewChild('canvas', { static: true }) canvasRef!: ElementRef<HTMLCanvasElement>;

  // ── Búsqueda ──────────────────────────────────────────────────
  busqueda      = '';
  resultados    = signal<Componente[]>([]);
  cargandoBusq  = signal(false);
  private busq$ = new Subject<string>();

  // ── Gabinetes en comparación ──────────────────────────────────
  comparando    = signal<Gabinete[]>([]);

  vistaActual        = signal<Vista>('3d');
  mostrarDiferencias = signal(false);
  readonly escala    = 10;

  private renderer!: THREE.WebGLRenderer;
  private scene!: THREE.Scene;
  private camera!: THREE.OrthographicCamera;
  private meshes: Map<string, THREE.Group> = new Map();
  private animFrame!: number;
  private resizeObs!: ResizeObserver;
  private sceneReady = false;

  private isDragging = false;
  private prevMouse  = { x: 0, y: 0 };
  private spherical      = { theta: Math.PI / 4, phi: Math.PI / 3 };
  private orbitalTarget  = new THREE.Vector3(0, 30, 0);
  private readonly ORBIT_RADIUS = 180;

  readonly vistas: { key: Vista; label: string }[] = [
    { key: 'frente',   label: 'Frente'   },
    { key: 'lateral',  label: 'Lateral'  },
    { key: 'superior', label: 'Superior' },
    { key: '3d',         label: '3D'         },
    { key: '3d-esquina', label: '3D esquina' },
  ];

  // Ordenados por profundidad descendente: en la vista lateral (cámara en X)
  // el ancho visual de cada gabinete es su profundidad_mm, así el más largo
  // queda siempre delante y no queda tapado por uno más corto pero más alto.
  gabinetesMostrados = computed(() =>
    [...this.comparando()].sort((a, b) => b.profundidad_mm - a.profundidad_mm)
  );

  puedeAnadir = computed(() => this.comparando().length < 4);

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

  constructor(private svc: ComponenteService, private ngZone: NgZone) {}

  ngOnInit(): void {
    this.ejecutarBusqueda('');
    this.busq$.pipe(debounceTime(350), distinctUntilChanged())
      .subscribe(q => this.ejecutarBusqueda(q));
  }

  ngAfterViewInit(): void {
    this.initThree();
    this.construirEscena();
    this.sceneReady = true;
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

  // ── Búsqueda ──────────────────────────────────────────────────

  onBusqueda(): void {
    this.busq$.next(this.busqueda);
  }

  private ejecutarBusqueda(q: string): void {
    this.cargandoBusq.set(true);
    this.svc.buscar({ categoria: 'gabinete', q, page: 1 })
      .pipe(catchError(() => of({ data: [] as Componente[] })))
      .subscribe(res => {
        this.resultados.set(res.data);
        this.cargandoBusq.set(false);
      });
  }

  // ── Selección ─────────────────────────────────────────────────

  estaEnComparacion(uuid: string): boolean {
    return this.comparando().some(g => g.uuid === uuid);
  }

  colorDeComp(uuid: string): string {
    const idx = this.comparando().findIndex(g => g.uuid === uuid);
    return idx >= 0 ? COLORES[idx] : '';
  }

  toggleComponente(comp: Componente): void {
    if (this.estaEnComparacion(comp.uuid)) {
      this.quitarGabinete(comp.uuid);
    } else if (this.puedeAnadir()) {
      this.anadirGabinete(comp);
    }
  }

  private anadirGabinete(comp: Componente): void {
    const colorAsignado = COLORES[this.comparando().length] as string;

    // Placeholder inmediato mientras llega el detalle
    const placeholder: Gabinete = {
      uuid: comp.uuid, nombre: comp.nombre,
      tipo: '—', estructura: '—',
      ancho_mm: 200, alto_mm: 400, profundidad_mm: 400,
      longitud_gpu_max_mm: 0, altura_cooler_max_mm: 0,
      soporte_radiadores: [], color: colorAsignado,
    };
    this.comparando.update(arr => [...arr, placeholder]);
    if (this.sceneReady) this.construirEscena();

    // Cargar detalle completo
    this.svc.getDetalle(comp.uuid)
      .pipe(catchError(() => of(null)))
      .subscribe(detalle => {
        if (!detalle) return;
        const gab = this.mapDetalleToGabinete(detalle, colorAsignado);

        // ── FIX: NgZone.run() garantiza que el signal se actualiza dentro de la
        // zona de Angular, evitando la race condition con el loop de Three.js
        // que corre fuera de zona (runOutsideAngular). Sin esto, construirEscena()
        // puede ejecutarse antes de que el signal tenga los datos reales.
        this.ngZone.run(() => {
          this.comparando.update(arr =>
            arr.map(g => g.uuid === comp.uuid ? gab : g)
          );
          if (this.sceneReady) this.construirEscena();
        });
      });
  }

  private mapDetalleToGabinete(d: ComponenteDetalle, color: string): Gabinete {
    const g = (d as any).gabinete ?? {};

    const toInt = (v: any, fallback: number): number =>
      (v !== null && v !== undefined && !isNaN(Number(v))) ? Number(v) : fallback;

    return {
      uuid:                 d.uuid,
      nombre:               d.nombre,
      tipo:                 g.tipo_gabinete?.nombre ?? '—',
      estructura:           g.estructura_gabinete?.nombre ?? '—',
      ancho_mm:             toInt(g.ancho_mm,              200),
      alto_mm:              toInt(g.alto_mm,               400),
      profundidad_mm:       toInt(g.profundidad_mm,        400),
      longitud_gpu_max_mm:  toInt(g.longitud_gpu_max_mm,     0),
      altura_cooler_max_mm: toInt(g.altura_cooler_max_mm,    0),
      soporte_radiadores:   Array.isArray(g.soporte_radiadores) ? g.soporte_radiadores : [],
      color,
    };
  }

  quitarGabinete(uuid: string): void {
    this.comparando.update(arr =>
      arr.filter(g => g.uuid !== uuid)
         .map((g, i) => ({ ...g, color: COLORES[i] }))
    );
    if (this.sceneReady) this.construirEscena();
  }

  // ── Helpers ───────────────────────────────────────────────────

  numSeleccionados(): number { return this.comparando().length; }

  toggleDiferencias(): void { this.mostrarDiferencias.update(v => !v); }

  pct(valor: number, max: number): number {
    return Math.round((valor / max) * 100);
  }

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

    const esEsquina = this.vistaActual() === '3d-esquina';
    const mostrados = [...this.comparando()].sort((a, b) => b.profundidad_mm - a.profundidad_mm);

    // Modo normal: centrado en X=0. Modo esquina: todos arrancan desde X=0.
    const totalAncho = mostrados.reduce((s, g) => s + (g.ancho_mm || 200) / this.escala, 0);
    let offsetX = esEsquina ? 0 : -totalAncho / 2;

    // Centro de giro: en esquina los cubos van de X=0 a X=totalAncho y de Z=0 a Z=maxProfundidad
    if (esEsquina) {
      const maxProf = Math.max(...mostrados.map(g => (g.profundidad_mm || 400))) / this.escala;
      const midH    = Math.max(...mostrados.map(g => (g.alto_mm        || 400))) / this.escala / 2;
      this.orbitalTarget.set(totalAncho / 2, midH, maxProf / 2);
    } else {
      this.orbitalTarget.set(0, 30, 0);
    }

    mostrados.forEach(gab => {
      const w = (gab.ancho_mm       || 200) / this.escala;
      const h = (gab.alto_mm        || 400) / this.escala;
      const d = (gab.profundidad_mm || 400) / this.escala;

      const color = new THREE.Color(gab.color);
      const group = new THREE.Group();
      const geo   = new THREE.BoxGeometry(w, h, d);

      group.add(new THREE.Mesh(geo, new THREE.MeshLambertMaterial({ color })));

      const edgeColor = new THREE.Color(gab.color).lerp(new THREE.Color(0xffffff), 0.5);
      group.add(new THREE.LineSegments(
        new THREE.EdgesGeometry(geo),
        new THREE.LineBasicMaterial({ color: edgeColor })
      ));

      // En modo esquina la cara trasera queda en Z=0 (misma "pared"); en normal el centro en Z=0.
      const posZ = esEsquina ? d / 2 : 0;
      group.position.set(offsetX + w / 2, h / 2, posZ);
      offsetX += w;

      this.scene.add(group);
      this.meshes.set(gab.uuid, group);
    });
  }

  setVista(vista: Vista): void {
    this.vistaActual.set(vista);
    this.construirEscena();

    if (vista === '3d' || vista === '3d-esquina') {
      this.updateOrbitalCamera();
      return;
    }

    const d      = this.ORBIT_RADIUS;
    const target = new THREE.Vector3(0, 30, 0);
    const configs: Record<Exclude<Vista, '3d' | '3d-esquina'>, { pos: THREE.Vector3; up: THREE.Vector3 }> = {
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
    const r  = this.ORBIT_RADIUS;
    const t  = this.orbitalTarget;
    this.camera.position.set(
      t.x + r * Math.sin(phi) * Math.sin(theta),
      t.y + r * Math.cos(phi),
      t.z + r * Math.sin(phi) * Math.cos(theta)
    );
    this.camera.up.set(0, 1, 0);
    this.camera.lookAt(t);
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

  private bindMouse(): void {
    const canvas = this.canvasRef.nativeElement;

    canvas.addEventListener('mousedown', (e) => {
      if (this.vistaActual() !== '3d' && this.vistaActual() !== '3d-esquina') return;
      this.isDragging = true;
      this.prevMouse = { x: e.clientX, y: e.clientY };
    });
    window.addEventListener('mousemove', (e) => {
      if (!this.isDragging) return;
      const dx = e.clientX - this.prevMouse.x;
      const dy = e.clientY - this.prevMouse.y;
      this.prevMouse = { x: e.clientX, y: e.clientY };
      this.spherical.theta -= dx * 0.01;
      this.spherical.phi = Math.max(0.1, Math.min(Math.PI / 2 - 0.05, this.spherical.phi - dy * 0.01));
      this.updateOrbitalCamera();
    });
    window.addEventListener('mouseup', () => { this.isDragging = false; });

    canvas.addEventListener('touchstart', (e) => {
      if (this.vistaActual() !== '3d' && this.vistaActual() !== '3d-esquina') return;
      this.isDragging = true;
      this.prevMouse = { x: e.touches[0].clientX, y: e.touches[0].clientY };
    });
    window.addEventListener('touchmove', (e) => {
      if (!this.isDragging) return;
      const dx = e.touches[0].clientX - this.prevMouse.x;
      const dy = e.touches[0].clientY - this.prevMouse.y;
      this.prevMouse = { x: e.touches[0].clientX, y: e.touches[0].clientY };
      this.spherical.theta -= dx * 0.01;
      this.spherical.phi = Math.max(0.1, Math.min(Math.PI / 2 - 0.05, this.spherical.phi - dy * 0.01));
      this.updateOrbitalCamera();
    });
    window.addEventListener('touchend', () => { this.isDragging = false; });
  }
}