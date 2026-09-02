import { Component, OnInit, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { debounceTime, distinctUntilChanged, Subject, forkJoin, of, take } from 'rxjs';
import { PriceHistoryComponent } from '../../shared/components/price-history/price-history.component';
import { catchError } from 'rxjs/operators';
import {
  ComponenteService,
  ComponenteDetalle,
  EntradaPrecio,
} from '../../core/services/componente.service';

// ─── Tipos internos ────────────────────────────────────────────────────────────

export interface CategoriaConfig {
  slug: string;
  label: string;
  secciones: SeccionSpec[];
}

export interface SeccionSpec {
  titulo: string;
  filas: FilaSpec[];
}

export interface FilaSpec {
  label: string;
  prop: string;
  unit?: string;
  bool?: { si: string; no: string };
  max?: number;
  higherBetter?: boolean;
  sinBarra?: boolean;
  /** El valor es un array (p.ej. factores de forma o tipos de PSU que
   *  admite un gabinete) y se muestra unido por comas en vez de como
   *  texto/número suelto. */
  lista?: boolean;
}

const COLORES = ['#00AADD', '#9b59b6', '#e67e22'] as const;

function getPath(obj: any, path: string): any {
  return path.split('.').reduce((acc, key) => acc?.[key], obj);
}

// ─── Definición de categorías ─────────────────────────────────────────────────

export const CATEGORIAS: CategoriaConfig[] = [
  {
    slug: 'cpu', label: 'CPU',
    secciones: [
      {
        titulo: 'Rendimiento',
        filas: [
          { label: 'Núcleos',      prop: 'specs.nucleos',              max: 32,  higherBetter: true },
          { label: 'Hilos',        prop: 'specs.hilos',                max: 64,  higherBetter: true },
          { label: 'Frec. base',   prop: 'specs.frecuencia_base_ghz',  max: 6,   higherBetter: true, unit: 'GHz' },
          { label: 'Frec. boost',  prop: 'specs.frecuencia_boost_ghz', max: 6.5, higherBetter: true, unit: 'GHz' },
          { label: 'Proceso',      prop: 'specs.proceso_nm',           max: 12,  higherBetter: false, unit: 'nm' },
        ],
      },
      {
        titulo: 'Memoria',
        filas: [
          { label: 'Tipo RAM',  prop: 'specs.tipo_memoria',               sinBarra: true },
          { label: 'Vel. máx.', prop: 'specs.frecuencia_memoria_max_mhz', max: 8000, higherBetter: true, unit: 'MHz' },
          { label: 'RAM máx.',  prop: 'specs.memoria_max_gb',             max: 256,  higherBetter: true, unit: 'GB' },
          { label: 'iGPU',      prop: 'specs.grafica_integrada',          sinBarra: true, bool: { si: 'Sí', no: 'No' } },
        ],
      },
      {
        titulo: 'Plataforma',
        filas: [
          { label: 'Socket',       prop: 'specs.socket',       sinBarra: true },
          { label: 'Arquitectura', prop: 'specs.arquitectura', sinBarra: true },
        ],
      },
      {
        titulo: 'Consumo',
        filas: [
          { label: 'TDP',      prop: 'specs.tdp_watts',     max: 300, higherBetter: false, unit: 'W' },
          { label: 'TDP máx.', prop: 'specs.tdp_max_watts', max: 350, higherBetter: false, unit: 'W' },
        ],
      },
    ],
  },
  {
    slug: 'gpu', label: 'GPU',
    secciones: [
      {
        titulo: 'Rendimiento',
        filas: [
          { label: 'VRAM',        prop: 'specs.vram_gb',              max: 32,   higherBetter: true,  unit: 'GB' },
          { label: 'Bus',         prop: 'specs.bus_bits',             max: 512,  higherBetter: true,  unit: 'bits' },
          { label: 'Frec. base',  prop: 'specs.frecuencia_base_mhz',  max: 3000, higherBetter: true,  unit: 'MHz' },
          { label: 'Frec. boost', prop: 'specs.frecuencia_boost_mhz', max: 3500, higherBetter: true,  unit: 'MHz' },
        ],
      },
      {
        titulo: 'Tipo',
        filas: [
          { label: 'Tipo VRAM',    prop: 'specs.tipo_vram',    sinBarra: true },
          { label: 'Arquitectura', prop: 'specs.arquitectura', sinBarra: true },
          { label: 'PCIe',         prop: 'specs.version_pcie', sinBarra: true },
          { label: 'Ray tracing',  prop: 'specs.ray_tracing',  sinBarra: true, bool: { si: 'Sí', no: 'No' } },
        ],
      },
      {
        titulo: 'Dimensiones y alimentación',
        filas: [
          { label: 'Longitud',      prop: 'specs.longitud_mm',         max: 400, higherBetter: false, unit: 'mm' },
          { label: 'Slots',         prop: 'specs.slots_pcie',          max: 4,   higherBetter: false },
          { label: 'PSU mínima',    prop: 'specs.psu_minima_watts',    max: 1000, higherBetter: false, unit: 'W' },
        ],
      },
      {
        titulo: 'Consumo',
        filas: [
          { label: 'TDP', prop: 'specs.tdp_watts', max: 600, higherBetter: false, unit: 'W' },
        ],
      },
    ],
  },
  {
    slug: 'ram', label: 'RAM',
    secciones: [
      {
        titulo: 'Especificaciones',
        filas: [
          { label: 'Capacidad total', prop: 'specs.capacidad_total_gb', max: 128,  higherBetter: true,  unit: 'GB' },
          { label: 'Velocidad',       prop: 'specs.velocidad_mhz',      max: 8000, higherBetter: true,  unit: 'MHz' },
          { label: 'Latencia CAS',    prop: 'specs.latencia_cas',       sinBarra: true },
          { label: 'Voltaje',         prop: 'specs.voltaje',            max: 2,    higherBetter: false, unit: 'V' },
          { label: 'Módulos',         prop: 'specs.modulos',            max: 4,    higherBetter: true },
        ],
      },
      {
        titulo: 'Compatibilidad',
        filas: [
          { label: 'Tipo', prop: 'specs.tipo_memoria', sinBarra: true },
          { label: 'XMP',  prop: 'specs.xmp',          sinBarra: true, bool: { si: 'Sí', no: 'No' } },
          { label: 'EXPO', prop: 'specs.expo',         sinBarra: true, bool: { si: 'Sí', no: 'No' } },
          { label: 'ECC',  prop: 'specs.ecc',          sinBarra: true, bool: { si: 'Sí', no: 'No' } },
        ],
      },
    ],
  },
  {
    slug: 'placa_base', label: 'Placa Base',
    secciones: [
      {
        titulo: 'Especificaciones',
        filas: [
          { label: 'Slots RAM',      prop: 'specs.slots_memoria',              max: 8,    higherBetter: true },
          { label: 'RAM máx.',       prop: 'specs.frecuencia_memoria_max_mhz', max: 8000, higherBetter: true, unit: 'MHz' },
          { label: 'Slots M.2',      prop: 'specs.slots_m2',                   max: 6,    higherBetter: true },
          { label: 'Puertos SATA',   prop: 'specs.puertos_sata',               max: 8,    higherBetter: true },
          { label: 'Slots PCIe x16', prop: 'specs.slots_pcie_x16',             max: 4,    higherBetter: true },
        ],
      },
      {
        titulo: 'Conectividad',
        filas: [
          { label: 'WiFi',        prop: 'specs.wifi',        sinBarra: true, bool: { si: 'Sí', no: 'No' } },
          { label: 'Bluetooth',   prop: 'specs.bluetooth',   sinBarra: true, bool: { si: 'Sí', no: 'No' } },
          { label: 'Thunderbolt', prop: 'specs.thunderbolt', sinBarra: true, bool: { si: 'Sí', no: 'No' } },
        ],
      },
      {
        titulo: 'Plataforma',
        filas: [
          { label: 'Socket',       prop: 'specs.socket',        sinBarra: true },
          { label: 'Chipset',      prop: 'specs.chipset',       sinBarra: true },
          { label: 'Factor forma', prop: 'specs.factor_forma',  sinBarra: true },
          { label: 'Tipo RAM',     prop: 'specs.tipo_memoria',  sinBarra: true },
          { label: 'PCIe',         prop: 'specs.version_pcie',  sinBarra: true },
        ],
      },
    ],
  },
  {
    slug: 'almacenamiento', label: 'SSD / HDD',
    secciones: [
      {
        titulo: 'Rendimiento',
        filas: [
          { label: 'Capacidad',    prop: 'specs.capacidad_gb',            max: 8000,  higherBetter: true, unit: 'GB' },
          { label: 'Lect. sec.',   prop: 'specs.velocidad_lectura_mbs',   max: 15000, higherBetter: true, unit: 'MB/s' },
          { label: 'Escrit. sec.', prop: 'specs.velocidad_escritura_mbs', max: 15000, higherBetter: true, unit: 'MB/s' },
          { label: 'TBW',          prop: 'specs.tbw',                     max: 3600,  higherBetter: true, unit: 'TBW' },
        ],
      },
      {
        titulo: 'Tipo',
        filas: [
          { label: 'Interfaz',     prop: 'specs.interfaz',     sinBarra: true },
          { label: 'Factor forma', prop: 'specs.factor_forma', sinBarra: true },
          { label: 'NAND',         prop: 'specs.tipo_nand',    sinBarra: true },
        ],
      },
    ],
  },
  {
    slug: 'psu', label: 'PSU',
    secciones: [
      {
        titulo: 'Especificaciones',
        filas: [
          { label: 'Potencia', prop: 'specs.vatios', max: 1600, higherBetter: true, unit: 'W' },
          { label: 'Longitud', prop: 'specs.largo_mm', max: 220, higherBetter: false, unit: 'mm' },
        ],
      },
      {
        titulo: 'Tipo',
        filas: [
          { label: 'Certificación',        prop: 'specs.certificacion',           sinBarra: true },
          { label: 'Tipo (factor forma)',  prop: 'specs.tipo_psu',                sinBarra: true },
          { label: 'Modular',              prop: 'specs.modular',                 sinBarra: true },
          { label: 'Norma ATX',            prop: 'specs.version_atx',             sinBarra: true },
          { label: 'Conectores PCIe 16p.', prop: 'specs.conectores_pcie_16pin',   sinBarra: true },
        ],
      },
    ],
  },
  {
    slug: 'gabinete', label: 'Gabinete',
    secciones: [
      {
        titulo: 'Dimensiones',
        filas: [
          { label: 'Alto',        prop: 'specs.alto_mm',         max: 650, higherBetter: false, unit: 'mm' },
          { label: 'Ancho',       prop: 'specs.ancho_mm',        max: 350, higherBetter: false, unit: 'mm' },
          { label: 'Profundidad', prop: 'specs.profundidad_mm',  max: 600, higherBetter: false, unit: 'mm' },
        ],
      },
      {
        titulo: 'Compatibilidad',
        filas: [
          { label: 'GPU máx.',    prop: 'specs.longitud_gpu_max_mm',  max: 500, higherBetter: true, unit: 'mm' },
          { label: 'Cooler máx.', prop: 'specs.altura_cooler_max_mm', max: 200, higherBetter: true, unit: 'mm' },
          { label: 'PSU máx.',    prop: 'specs.largo_psu_max_mm',     max: 250, higherBetter: true, unit: 'mm' },
        ],
      },
      {
        titulo: 'Tipo',
        filas: [
          { label: 'Tipo',            prop: 'specs.tipo_gabinete',  sinBarra: true },
          { label: 'Estructura',      prop: 'specs.estructura',     sinBarra: true },
          { label: 'Placas admitidas',prop: 'specs.factores_forma', sinBarra: true, lista: true },
          { label: 'Fuentes admitidas', prop: 'specs.tipos_psu',    sinBarra: true, lista: true },
        ],
      },
    ],
  },
];

// ─── Interfaces ───────────────────────────────────────────────────────────────

export interface ComponenteEnComparacion {
  detalle: ComponenteDetalle;
  precios: EntradaPrecio[];
  color: string;
  cargandoPrecios: boolean;
}

// ─── Componente ───────────────────────────────────────────────────────────────

@Component({
  selector: 'app-spec-compare',
  standalone: true,
  imports: [CommonModule, FormsModule, PriceHistoryComponent],
  templateUrl: './spec-compare.component.html',
  styleUrl: './spec-compare.component.scss',
})
export class SpecCompareComponent implements OnInit {

  readonly MAX_COMPONENTES = 3;
  readonly COLORES = COLORES;
  readonly CATEGORIAS = CATEGORIAS;

  categoriaActiva = signal<CategoriaConfig>(CATEGORIAS[0]);
  comparando      = signal<ComponenteEnComparacion[]>([]);

  busqueda     = '';
  resultados   = signal<ComponenteDetalle[]>([]);
  cargandoBusq = signal(false);
  private busq$ = new Subject<string>();

  puedeAnadir = computed(() => this.comparando().length < this.MAX_COMPONENTES);
  secciones   = computed(() => this.categoriaActiva().secciones);

  constructor(
    private svc: ComponenteService,
    private route: ActivatedRoute,
    private router: Router,
  ) {}

  ngOnInit() {
    this.busq$.pipe(debounceTime(350), distinctUntilChanged())
      .subscribe(q => this.ejecutarBusqueda(q));

    this.route.queryParams.pipe(take(1)).subscribe(params => {
      if (params['cat']) {
        const cat = CATEGORIAS.find(c => c.slug === params['cat']);
        if (cat) this.categoriaActiva.set(cat);
      }
      if (params['uuids']) {
        const uuids: string[] = params['uuids'].split(',').slice(0, 3);
        this.cargarPorUuids(uuids);
      }
    });

    // No cargamos nada por defecto: el usuario tiene que buscar el
    // componente que quiere comparar. Antes aquí se llamaba a
    // ejecutarBusqueda('') y se traía una página entera de componentes
    // sin que el usuario hubiera pedido nada.
  }

  // ── Categoría ────────────────────────────────────────────────────────────────

  setCategoriaActiva(cat: CategoriaConfig) {
    this.categoriaActiva.set(cat);
    this.comparando.set([]);
    this.ejecutarBusqueda(this.busqueda);
    this.actualizarQueryParams();
  }

  // ── Búsqueda ─────────────────────────────────────────────────────────────────

  onBusqueda() {
    this.busq$.next(this.busqueda);
  }

  private ejecutarBusqueda(q: string) {
    this.cargandoBusq.set(true);
    this.svc.buscar({ categoria: this.categoriaActiva().slug, q, page: 1 })
      .subscribe({
        next: res => {
          this.resultados.set(res.data as any);
          this.cargandoBusq.set(false);
        },
        error: () => this.cargandoBusq.set(false),
      });
  }

  // ── Comparación ──────────────────────────────────────────────────────────────

  estaEnComparacion(uuid: string): boolean {
    return this.comparando().some(c => c.detalle.uuid === uuid);
  }

  colorDeComp(uuid: string): string {
    const idx = this.comparando().findIndex(c => c.detalle.uuid === uuid);
    return idx >= 0 ? COLORES[idx] : '';
  }

  toggleComponente(comp: ComponenteDetalle) {
    if (this.estaEnComparacion(comp.uuid)) {
      this.quitarComponente(comp.uuid);
    } else if (this.puedeAnadir()) {
      this.anadirComponente(comp);
    }
  }

  private anadirComponente(comp: ComponenteDetalle) {
    // FIX: capturar el color en el momento exacto de añadir, antes de cualquier
    // operación asíncrona. Así el closure del forkJoin siempre tiene el color correcto
    // aunque mientras tanto se quiten componentes y se reasignen índices.
    const colorAsignado = COLORES[this.comparando().length] as string;

    const entrada: ComponenteEnComparacion = {
      detalle: comp,
      precios: [],
      color: colorAsignado,
      cargandoPrecios: true,
    };
    this.comparando.update(arr => [...arr, entrada]);

    forkJoin({
      detalle: this.svc.getDetalle(comp.uuid).pipe(catchError(() => of(comp as ComponenteDetalle))),
      precios: this.svc.getPrecios(comp.uuid).pipe(catchError(() => of({ precios: [] }))),
    }).subscribe(({ detalle, precios }) => {
      this.comparando.update(arr =>
        arr.map(c =>
          c.detalle.uuid === comp.uuid
            // FIX: forzar colorAsignado explícitamente para que el spread
            // no lo sobreescriba con un valor desactualizado
            ? { ...c, detalle, precios: precios.precios, cargandoPrecios: false, color: colorAsignado }
            : c
        )
      );
    });

    this.actualizarQueryParams();
  }

  quitarComponente(uuid: string) {
    this.comparando.update(arr =>
      arr.filter(c => c.detalle.uuid !== uuid)
         .map((c, i) => ({ ...c, color: COLORES[i] }))
    );
    this.actualizarQueryParams();
  }

  private cargarPorUuids(uuids: string[]) {
    // FIX: secuencial con reduce+Promise para que cada anadirComponente
    // capture el índice correcto y no colisionen al llegar en paralelo
    uuids.reduce((cadena, uuid) => {
      return cadena.then(() =>
        new Promise<void>(resolve => {
          this.svc.getDetalle(uuid).pipe(catchError(() => of(null))).subscribe(detalle => {
            if (detalle) this.anadirComponente(detalle);
            resolve();
          });
        })
      );
    }, Promise.resolve());
  }

  private actualizarQueryParams() {
    const uuids = this.comparando().map(c => c.detalle.uuid).join(',');
    this.router.navigate([], {
      relativeTo: this.route,
      queryParams: {
        cat: this.categoriaActiva().slug,
        uuids: uuids || null,
      },
      queryParamsHandling: 'merge',
      replaceUrl: true,
    });
  }

  // ── Helpers de specs ─────────────────────────────────────────────────────────

  getValor(comp: ComponenteDetalle, fila: FilaSpec): string {
    const raw = getPath(comp, fila.prop);
    if (raw === null || raw === undefined) return '—';
    if (fila.lista && Array.isArray(raw)) return raw.length ? raw.join(', ') : '—';
    if (fila.bool) return raw ? fila.bool.si : fila.bool.no;
    if (typeof raw === 'number' && fila.unit) return `${raw.toLocaleString('es-ES')} ${fila.unit}`;
    return String(raw);
  }

  getValorNumerico(comp: ComponenteDetalle, fila: FilaSpec): number | null {
    const raw = getPath(comp, fila.prop);
    return typeof raw === 'number' ? raw : null;
  }

  esMejor(fila: FilaSpec, idx: number): boolean {
    if (fila.sinBarra || fila.max === undefined) return false;
    const vals = this.comparando()
      .map(c => this.getValorNumerico(c.detalle, fila))
      .filter((v): v is number => v !== null);
    if (vals.length < 2) return false;
    const best = fila.higherBetter !== false ? Math.max(...vals) : Math.min(...vals);
    const val  = this.getValorNumerico(this.comparando()[idx].detalle, fila);
    return val === best;
  }

  getPorcentajeBarra(fila: FilaSpec, idx: number): number {
    const val = this.getValorNumerico(this.comparando()[idx].detalle, fila);
    if (val === null || !fila.max) return 0;
    if (fila.higherBetter !== false) {
      return Math.min(Math.round((val / fila.max) * 100), 100);
    } else {
      return Math.min(Math.round(((fila.max - val) / fila.max) * 100) + 10, 100);
    }
  }

  // ── Precio ───────────────────────────────────────────────────────────────────

  formatPrecio(precio: number | null): string {
    if (!precio) return 'Sin precio';
    return precio.toLocaleString('es-ES', { style: 'currency', currency: 'EUR', minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  esMejorPrecio(idx: number): boolean {
    const precios = this.comparando()
      .map(c => c.detalle.precio_min)
      .filter((p): p is number => p !== null);
    if (precios.length < 2) return false;
    return this.comparando()[idx].detalle.precio_min === Math.min(...precios);
  }

  precioBarraPct(idx: number): number {
    const precios = this.comparando()
      .map(c => c.detalle.precio_min)
      .filter((p): p is number => p !== null);
    if (precios.length === 0) return 0;
    const val = this.comparando()[idx].detalle.precio_min;
    if (!val) return 0;
    const min = Math.min(...precios);
    const max = Math.max(...precios);
    if (min === max) return 100;
    return Math.round(((max - val) / (max - min)) * 90 + 10);
  }

  // ── Sidebar ──────────────────────────────────────────────────────────────────

  indiceColor(uuid: string): number {
    return this.comparando().findIndex(c => c.detalle.uuid === uuid);
  }

  trackByUuid(_: number, item: ComponenteEnComparacion) {
    return item.detalle.uuid;
  }

  trackByCat(_: number, cat: CategoriaConfig) {
    return cat.slug;
  }

  // ── Índice flotante ──────────────────────────────────────────────────────────

  seccionActiva = '';

  slugSeccion(titulo: string): string {
    return titulo.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
  }

  scrollASeccion(event: Event, titulo: string) {
    event.preventDefault();
    const slug = titulo === 'tiendas' ? 'tiendas' : this.slugSeccion(titulo);
    const el = document.getElementById('sec-' + slug);
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      this.seccionActiva = slug;
    }
  }
}