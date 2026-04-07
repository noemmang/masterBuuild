import { Component, OnInit, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { ComponenteService, Componente } from '../../core/services/componente.service';
import { GuardadoService } from '../../core/services/guardado.service';
import { AuthService } from '../../core/services/auth.service';
import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';

// ── Definición de filtros por categoría ─────────────────────────────────────

interface OpcionFiltro {
  label: string;
  valor: number | string;
}

interface GrupoFiltro {
  param: string;   // nombre del parámetro que se enviará al backend
  label: string;
  opciones: OpcionFiltro[];
  tipo: 'multi' | 'min';  // multi = selección múltiple, min = mínimo
}

const FILTROS_POR_CATEGORIA: Record<string, GrupoFiltro[]> = {
  ram: [
    {
      param: 'capacidad_gb', label: 'Capacidad', tipo: 'multi',
      opciones: [
        { label: '8 GB',  valor: 8  },
        { label: '16 GB', valor: 16 },
        { label: '32 GB', valor: 32 },
        { label: '64 GB', valor: 64 },
      ]
    }
  ],
  gpu: [
    {
      param: 'vram_gb', label: 'VRAM', tipo: 'multi',
      opciones: [
        { label: '4 GB',  valor: 4  },
        { label: '8 GB',  valor: 8  },
        { label: '12 GB', valor: 12 },
        { label: '16 GB', valor: 16 },
        { label: '20 GB', valor: 20 },
        { label: '24 GB', valor: 24 },
      ]
    }
  ],
  cpu: [
    {
      param: 'serie_cpu', label: 'Serie', tipo: 'multi',
      opciones: [
        { label: 'i3 / Ryzen 3', valor: 3 },
        { label: 'i5 / Ryzen 5', valor: 5 },
        { label: 'i7 / Ryzen 7', valor: 7 },
        { label: 'i9 / Ryzen 9', valor: 9 },
      ]
    }
  ],
  almacenamiento: [
    {
      param: 'capacidad_ssd', label: 'Capacidad', tipo: 'multi',
      opciones: [
        { label: '256 GB', valor: 256  },
        { label: '512 GB', valor: 512  },
        { label: '1 TB',   valor: 1000 },
        { label: '2 TB',   valor: 2000 },
        { label: '4 TB',   valor: 4000 },
      ]
    }
  ],
  psu: [
    {
      param: 'potencia_min', label: 'Potencia mínima', tipo: 'min',
      opciones: [
        { label: '500 W+', valor: 500 },
        { label: '600 W+', valor: 600 },
        { label: '700 W+', valor: 700 },
        { label: '800 W+', valor: 800 },
        { label: '1000 W+', valor: 1000 },
      ]
    }
  ],
  gabinete: [
    {
      param: 'factor_forma_soportado', label: 'Factor forma', tipo: 'multi',
      opciones: [
        { label: 'ATX',   valor: 'Mid Tower' },
        { label: 'mATX',  valor: 'Micro Tower' },
        { label: 'ITX',   valor: 'Mini-ITX' },
      ]
    }
  ],
  refrigeracion_liquida: [
    {
      param: 'mm_radiador', label: 'Radiador', tipo: 'multi',
      opciones: [
        { label: '120 mm', valor: 120 },
        { label: '240 mm', valor: 240 },
        { label: '280 mm', valor: 280 },
        { label: '360 mm', valor: 360 },
        { label: '420 mm', valor: 420 },
      ]
    }
  ],
};

@Component({
  selector: 'app-search',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './search.component.html',
  styleUrl: './search.component.scss'
})
export class SearchComponent implements OnInit {

  private auth            = inject(AuthService);
  private guardadoService = inject(GuardadoService);

  categorias = [
    { label: 'Todo',             slug: '' },
    { label: 'CPU',              slug: 'cpu' },
    { label: 'GPU',              slug: 'gpu' },
    { label: 'RAM',              slug: 'ram' },
    { label: 'Placa Base',       slug: 'placa_base' },
    { label: 'Almacenamiento',   slug: 'almacenamiento' },
    { label: 'PSU',              slug: 'psu' },
    { label: 'Gabinete',         slug: 'gabinete' },
    { label: 'Refrig. Aire',     slug: 'refrigeracion_aire' },
    { label: 'Refrig. Líquida',  slug: 'refrigeracion_liquida' },
    { label: 'Ventiladores',     slug: 'ventilador' },
  ];

  ordenes = [
    { label: 'Relevancia',            value: '' },
    { label: 'Precio: menor a mayor', value: 'precio_asc' },
    { label: 'Precio: mayor a menor', value: 'precio_desc' },
    { label: 'Nombre A-Z',            value: 'nombre_asc' },
  ];

  componentes     = signal<Componente[]>([]);
  cargando        = signal(true);
  cargandoMas     = signal(false);
  totalResultados = signal(0);
  paginaActual    = signal(1);
  ultimaPagina    = signal(1);
  hayMas          = signal(false);

  categoriaActiva = signal('');
  busqueda        = '';
  ordenActivo     = '';
  precioMin: number | null = null;
  precioMax: number | null = null;

  // ── Filtros específicos ────────────────────────────────────────────────────
  // Mapa: param → Set de valores seleccionados
  filtrosActivos = signal<Map<string, Set<number | string>>>(new Map());
  filtrosExpandidos = signal<Set<string>>(new Set());  // qué grupos están abiertos

  get filtrosCategoria(): GrupoFiltro[] {
    return FILTROS_POR_CATEGORIA[this.categoriaActiva()] ?? [];
  }

  // ──────────────────────────────────────────────────────────────────────────

  componenteSeleccionado = signal<Componente | null>(null);
  precios                = signal<any[]>([]);
  cargandoPrecios        = signal(false);

  logueado = this.auth.estaAutenticado();

  guardadosMap = signal<Map<string, string>>(new Map());
  alertasMap   = signal<Map<string, string>>(new Map());

  guardando        = signal(false);
  eliminando       = signal(false);
  guardandoAlerta  = signal(false);
  mostrarAlerta    = signal(false);
  precioObjetivo   = signal<number | null>(null);

  private busqueda$ = new Subject<string>();

  constructor(private componenteService: ComponenteService, private route: ActivatedRoute) {}

  ngOnInit() {
    if (this.logueado) {
      this.cargarEstadoGuardados();
      this.cargarEstadoAlertas();
    }
    this.route.queryParams.subscribe(params => {
      if (params['categoria']) this.categoriaActiva.set(params['categoria']);
      if (params['q'])         this.busqueda = params['q'];
      this.resetYCargar();
    });
    this.busqueda$.pipe(debounceTime(400), distinctUntilChanged())
      .subscribe(() => this.resetYCargar());
  }

  // ── Filtros específicos ────────────────────────────────────────────────────

  toggleFiltroExpandido(param: string): void {
    this.filtrosExpandidos.update(s => {
      const n = new Set(s);
      n.has(param) ? n.delete(param) : n.add(param);
      return n;
    });
  }

  esFiltroExpandido(param: string): boolean {
    return this.filtrosExpandidos().has(param);
  }

  toggleValorFiltro(param: string, valor: number | string, tipo: 'multi' | 'min'): void {
    this.filtrosActivos.update(m => {
      const n = new Map(m);
      if (tipo === 'min') {
        // Para "mínimo" solo hay una selección a la vez (radio)
        const set = new Set<number | string>();
        const actual = n.get(param);
        if (!actual?.has(valor)) set.add(valor);  // toggle: si ya estaba, lo quita
        n.set(param, set);
      } else {
        const set = new Set(n.get(param) ?? []);
        set.has(valor) ? set.delete(valor) : set.add(valor);
        n.set(param, set);
      }
      return n;
    });
    this.resetYCargar();
  }

  esFiltroActivo(param: string, valor: number | string): boolean {
    return this.filtrosActivos().get(param)?.has(valor) ?? false;
  }

  contarFiltrosActivos(param: string): number {
    return this.filtrosActivos().get(param)?.size ?? 0;
  }

  limpiarFiltrosCategoria(): void {
    this.filtrosActivos.set(new Map());
    this.resetYCargar();
  }

  get hayFiltrosEspecificosActivos(): boolean {
    for (const set of this.filtrosActivos().values()) {
      if (set.size > 0) return true;
    }
    return false;
  }

  // ──────────────────────────────────────────────────────────────────────────

  private cargarEstadoGuardados(): void {
    this.guardadoService.listar().subscribe({
      next: (gs) => {
        const map = new Map<string, string>();
        gs.forEach(g => map.set(g.componente.uuid, g.uuid));
        this.guardadosMap.set(map);
      }
    });
  }

  private cargarEstadoAlertas(): void {
    this.guardadoService.listarAlertas().subscribe({
      next: (as) => {
        const map = new Map<string, string>();
        as.forEach(a => map.set(a.componente.uuid, a.uuid));
        this.alertasMap.set(map);
      }
    });
  }

  resetYCargar() {
    this.paginaActual.set(1);
    this.componentes.set([]);
    this.cargar(false);
  }

  private buildFiltrosEspecificos(): Record<string, any> {
    const extra: Record<string, any> = {};
    const filtros = this.filtrosActivos();
    for (const [param, valores] of filtros.entries()) {
      if (valores.size === 0) continue;
      const arr = Array.from(valores);
      if (param === 'capacidad_gb')           extra['capacidad_gb']           = arr as number[];
      if (param === 'vram_gb')                extra['vram_gb']                = arr as number[];
      if (param === 'serie_cpu')              extra['serie_cpu']              = arr as number[];
      if (param === 'capacidad_ssd')          extra['capacidad_ssd']          = arr as number[];
      if (param === 'potencia_min')           extra['potencia_min']           = arr[0] as number;
      if (param === 'factor_forma_soportado') extra['factor_forma_soportado'] = arr as string[];
      if (param === 'mm_radiador')            extra['mm_radiador']            = arr as number[];
    }
    return extra;
  }

  cargar(acumular = false) {
    if (acumular) this.cargandoMas.set(true);
    else          this.cargando.set(true);
    if (!acumular) this.componenteSeleccionado.set(null);

    this.componenteService.buscar({
      categoria: this.categoriaActiva(),
      q:         this.busqueda,
      page:      this.paginaActual(),
      orden:     this.ordenActivo,
      ...this.buildFiltrosEspecificos(),
    }).subscribe({
      next: (res) => {
        if (acumular) this.componentes.update(prev => [...prev, ...res.data]);
        else          this.componentes.set(res.data);
        this.totalResultados.set(res.total);
        this.ultimaPagina.set(res.last_page);
        this.hayMas.set(res.current_page < res.last_page);
        this.cargando.set(false);
        this.cargandoMas.set(false);
      },
      error: () => { this.cargando.set(false); this.cargandoMas.set(false); }
    });
  }

  cargarMas() { this.paginaActual.update(p => p + 1); this.cargar(true); }

  seleccionarCategoria(slug: string) {
    // Limpiar filtros específicos al cambiar de categoría
    this.filtrosActivos.set(new Map());
    this.filtrosExpandidos.set(new Set());
    this.categoriaActiva.set(slug);
    this.resetYCargar();
  }

  onBusqueda()     { this.busqueda$.next(this.busqueda); }
  onFiltroChange() { this.resetYCargar(); }

  cerrarPanel() {
    this.componenteSeleccionado.set(null);
    this.mostrarAlerta.set(false);
    this.precioObjetivo.set(null);
  }

  seleccionarComponente(comp: Componente) {
    if (this.componenteSeleccionado()?.uuid === comp.uuid) { this.cerrarPanel(); return; }
    this.componenteSeleccionado.set(comp);
    this.mostrarAlerta.set(false);
    this.precioObjetivo.set(null);
    this.cargandoPrecios.set(true);
    this.precios.set([]);
    this.componenteService.getPrecios(comp.uuid).subscribe({
      next: (res) => { this.precios.set(res.precios || res); this.cargandoPrecios.set(false); },
      error: () => this.cargandoPrecios.set(false)
    });
  }

  estaGuardado(uuid: string): boolean { return this.guardadosMap().has(uuid); }

  guardarComponente(): void {
    const comp = this.componenteSeleccionado();
    if (!comp || this.guardando()) return;
    this.guardando.set(true);
    this.guardadoService.guardar(comp.uuid).subscribe({
      next: (res) => { this.guardadosMap.update(m => new Map(m).set(comp.uuid, res.uuid)); this.guardando.set(false); },
      error: (err) => { if (err.status === 422) this.cargarEstadoGuardados(); this.guardando.set(false); }
    });
  }

  eliminarGuardado(): void {
    const comp = this.componenteSeleccionado();
    if (!comp || this.eliminando()) return;
    const uuidGuardado = this.guardadosMap().get(comp.uuid);
    if (!uuidGuardado) return;
    this.eliminando.set(true);
    this.guardadoService.eliminar(uuidGuardado).subscribe({
      next: () => { this.guardadosMap.update(m => { const n = new Map(m); n.delete(comp.uuid); return n; }); this.eliminando.set(false); },
      error: () => this.eliminando.set(false)
    });
  }

  tieneAlerta(uuid: string): boolean { return this.alertasMap().has(uuid); }

  toggleFormAlerta(): void {
    this.mostrarAlerta.update(v => !v);
    if (this.mostrarAlerta() && this.precios().length > 0) {
      this.precioObjetivo.set(Math.round(this.precios()[0].precio * 0.9));
    }
  }

  guardarAlerta(): void {
    const comp = this.componenteSeleccionado();
    if (!comp || !this.precioObjetivo() || this.guardandoAlerta()) return;
    this.guardandoAlerta.set(true);
    this.guardadoService.crearAlerta(comp.uuid, this.precioObjetivo()!).subscribe({
      next: (res) => {
        this.alertasMap.update(m => new Map(m).set(comp.uuid, res.uuid));
        this.guardandoAlerta.set(false);
        this.mostrarAlerta.set(false);
      },
      error: () => this.guardandoAlerta.set(false)
    });
  }

  eliminarAlerta(): void {
    const comp = this.componenteSeleccionado();
    if (!comp) return;
    const uuidAlerta = this.alertasMap().get(comp.uuid);
    if (!uuidAlerta) return;
    this.guardadoService.eliminarAlerta(uuidAlerta).subscribe({
      next: () => {
        this.alertasMap.update(m => { const n = new Map(m); n.delete(comp.uuid); return n; });
        this.mostrarAlerta.set(false);
      }
    });
  }

  setPrecioObjetivo(v: string): void { this.precioObjetivo.set(v ? Number(v) : null); }

  formatPrecio(precio: number | null): string {
    if (!precio) return 'Sin precio';
    return precio.toLocaleString('es-ES', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });
  }

  nombreCategoria(slug: string): string {
    return this.categorias.find(c => c.slug === slug)?.label ?? slug;
  }
}