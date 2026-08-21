import { Component, OnInit, OnDestroy, signal, inject, ElementRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { ComponenteService, Componente } from '../../core/services/componente.service';
import { GuardadoService } from '../../core/services/guardado.service';
import { AuthService } from '../../core/services/auth.service';
import { PriceHistoryComponent } from '../../shared/components/price-history/price-history.component';
import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';
import { TooltipDirective } from '../../shared/directives/tooltip.directive';

interface OpcionFiltro {
  label: string;
  valor: number | string;
}

interface GrupoFiltro {
  param: string;
  label: string;
  opciones: OpcionFiltro[];
  tipo: 'multi' | 'min';
}

/** Snapshot de qué se estaba viendo, para restaurar el mismo listado (no solo el componente) tras una navegación */
interface BorradorSeleccion {
  uuid: string;
  categoria: string;
  busqueda: string;
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
        { label: '500 W+',  valor: 500  },
        { label: '600 W+',  valor: 600  },
        { label: '700 W+',  valor: 700  },
        { label: '800 W+',  valor: 800  },
        { label: '1000 W+', valor: 1000 },
      ]
    }
  ],
  gabinete: [
    {
      param: 'factor_forma_soportado', label: 'Factor forma', tipo: 'multi',
      opciones: [
        { label: 'ATX',  valor: 'Mid Tower'   },
        { label: 'mATX', valor: 'Micro Tower' },
        { label: 'ITX',  valor: 'Mini-ITX'    },
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
  imports: [CommonModule, FormsModule, TooltipDirective, PriceHistoryComponent],
  templateUrl: './search.component.html',
  styleUrl: './search.component.scss'
})
export class SearchComponent implements OnInit, OnDestroy {

  private auth            = inject(AuthService);
  private guardadoService = inject(GuardadoService);
  private el              = inject(ElementRef);
  private router          = inject(Router);

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
  /** Filtro "ver agotados": por defecto se incluyen (con su último precio conocido) */
  mostrarAgotados = true;

  filtrosActivos    = signal<Map<string, Set<number | string>>>(new Map());
  filtrosExpandidos = signal<Set<string>>(new Set());

  get filtrosCategoria(): GrupoFiltro[] {
    return FILTROS_POR_CATEGORIA[this.categoriaActiva()] ?? [];
  }

  /** Clave de sessionStorage donde se recuerda el componente abierto para sobrevivir a la navegación (p. ej. ir a login y volver) */
  private static readonly BORRADOR_KEY = 'mb:buscar:seleccion';

  componenteSeleccionado = signal<Componente | null>(null);
  precios                = signal<any[]>([]);
  cargandoPrecios        = signal(false);

  /** Precio (tienda) seleccionado en el panel */
  precioSeleccionado = signal<any | null>(null);

  logueado = this.auth.estaAutenticado();

  guardadosMap = signal<Map<string, string>>(new Map());
  alertasMap   = signal<Map<string, string>>(new Map());

  guardando       = signal(false);
  eliminando      = signal(false);
  guardandoAlerta = signal(false);
  mostrarAlerta   = signal(false);
  precioObjetivo  = signal<number | null>(null);

  private busqueda$ = new Subject<string>();

  constructor(private componenteService: ComponenteService, private route: ActivatedRoute) {}

  ngOnInit() {
    if (this.logueado) {
      this.cargarEstadoGuardados();
      this.cargarEstadoAlertas();
    }

    this.route.queryParams.subscribe(params => {
      const uuidParam = params['uuid'] as string | undefined;
      // El borrador local solo se usa si no venimos de un enlace explícito con ?uuid=
      const borrador  = uuidParam ? null : this.leerBorrador();

      if (params['categoria'])       this.categoriaActiva.set(params['categoria']);
      else if (borrador?.categoria)  this.categoriaActiva.set(borrador.categoria);

      if (params['q'])          this.busqueda = params['q'];
      else if (borrador?.busqueda) this.busqueda = borrador.busqueda;

      const uuid = uuidParam || borrador?.uuid || undefined;
      if (uuid) {
        this.resetYCargar(uuid);
      } else {
        this.resetYCargar();
      }
    });

    this.busqueda$.pipe(debounceTime(400), distinctUntilChanged())
      .subscribe(() => this.resetYCargar());
  }

  /** Justo antes de que Angular destruya el componente (navegación a login, atrás, etc.) guardamos qué había seleccionado */
  ngOnDestroy(): void {
    this.guardarBorrador();
  }

  private guardarBorrador(): void {
    try {
      const comp = this.componenteSeleccionado();
      if (comp) {
        const data: BorradorSeleccion = {
          uuid:      comp.uuid,
          categoria: this.categoriaActiva(),
          busqueda:  this.busqueda,
        };
        sessionStorage.setItem(SearchComponent.BORRADOR_KEY, JSON.stringify(data));
      } else {
        sessionStorage.removeItem(SearchComponent.BORRADOR_KEY);
      }
    } catch {
      // sessionStorage no disponible (modo privado, etc.) — no es crítico
    }
  }

  private leerBorrador(): BorradorSeleccion | null {
    try {
      const raw = sessionStorage.getItem(SearchComponent.BORRADOR_KEY);
      return raw ? (JSON.parse(raw) as BorradorSeleccion) : null;
    } catch {
      return null;
    }
  }

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
        const set    = new Set<number | string>();
        const actual = n.get(param);
        if (!actual?.has(valor)) set.add(valor);
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

  resetYCargar(autoSelectUuid?: string) {
    this.paginaActual.set(1);
    this.componentes.set([]);
    this.cargar(false, autoSelectUuid);
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

  cargar(acumular = false, autoSelectUuid?: string) {
    if (acumular) this.cargandoMas.set(true);
    else          this.cargando.set(true);
    if (!acumular) this.componenteSeleccionado.set(null);

    const params = {
      categoria:        this.categoriaActiva(),
      q:                this.busqueda,
      page:             this.paginaActual(),
      orden:            this.ordenActivo,
      precio_min:       this.precioMin ?? undefined,
      precio_max:       this.precioMax ?? undefined,
      mostrar_agotados: this.mostrarAgotados,
      ...this.buildFiltrosEspecificos(),
    };

    this.componenteService.buscar(params).subscribe({
      next: (res) => {
        if (acumular) this.componentes.update(prev => [...prev, ...res.data]);
        else          this.componentes.set(res.data);
        this.totalResultados.set(res.total);
        this.ultimaPagina.set(res.last_page);
        this.hayMas.set(res.current_page < res.last_page);
        this.cargando.set(false);
        this.cargandoMas.set(false);

        if (autoSelectUuid) {
          const encontrado = res.data.find((c: Componente) => c.uuid === autoSelectUuid);
          if (encontrado) {
            this.seleccionarComponente(encontrado);
          } else {
            this.buscarYSeleccionarPorUuid(autoSelectUuid);
          }
        }
      },
      error: () => { this.cargando.set(false); this.cargandoMas.set(false); }
    });
  }

  private buscarYSeleccionarPorUuid(uuid: string): void {
    this.componenteService.getDetalle(uuid).subscribe({
      next: (comp) => {
        if (!this.categoriaActiva() && comp.categoria) {
          this.categoriaActiva.set(comp.categoria);
        }
        this.busqueda = comp.nombre;
        this.paginaActual.set(1);
        // Forzamos mostrar_agotados=true aquí: venimos de un enlace directo
        // (p. ej. desde Guardados) y el componente destino puede estar
        // agotado aunque el filtro de la vista esté puesto en "solo disponibles".
        this.componenteService.buscar({
          categoria:        comp.categoria,
          q:                comp.nombre,
          page:             1,
          mostrar_agotados: true,
        }).subscribe({
          next: (res) => {
            this.componentes.set(res.data);
            this.totalResultados.set(res.total);
            this.ultimaPagina.set(res.last_page);
            this.hayMas.set(res.current_page < res.last_page);
            const target = res.data.find((c: Componente) => c.uuid === uuid) ?? res.data[0];
            if (target) this.seleccionarComponente(target);
          }
        });
      }
    });
  }

  cargarMas() { this.paginaActual.update(p => p + 1); this.cargar(true); }

  seleccionarCategoria(slug: string) {
    if (this.categoriaActiva() !== slug) {
      this.busqueda = '';
    }
    this.filtrosActivos.set(new Map());
    this.filtrosExpandidos.set(new Set());
    this.categoriaActiva.set(slug);
    this.resetYCargar();
  }

  onBusqueda()     { this.busqueda$.next(this.busqueda); }
  onFiltroChange() { this.resetYCargar(); }

  toggleMostrarAgotados(): void {
    this.mostrarAgotados = !this.mostrarAgotados;
    this.onFiltroChange();
  }

  seleccionarPrecio(precio: any): void {
    if (this.precioSeleccionado()?.uuid === precio.uuid) {
      this.precioSeleccionado.set(null);
      // Volver al precio del primero si el form está abierto
      if (this.mostrarAlerta() && this.precios().length > 0) {
        this.precioObjetivo.set(Math.round(this.precios()[0].precio * 0.9));
      }
    } else {
      this.precioSeleccionado.set(precio);
      // Actualizar el precio objetivo si el form de alerta está abierto
      if (this.mostrarAlerta()) {
        this.precioObjetivo.set(Math.round(precio.precio * 0.9));
      }
    }
  }

  cerrarPanel() {
    this.componenteSeleccionado.set(null);
    this.mostrarAlerta.set(false);
    this.precioObjetivo.set(null);
    this.precioSeleccionado.set(null);
  }

  seleccionarComponente(comp: Componente) {
    if (this.componenteSeleccionado()?.uuid === comp.uuid) { this.cerrarPanel(); return; }
    this.componenteSeleccionado.set(comp);
    this.mostrarAlerta.set(false);
    this.precioObjetivo.set(null);
    this.precioSeleccionado.set(null);
    this.cargandoPrecios.set(true);
    this.precios.set([]);

    // Scroll hasta la card seleccionada
    setTimeout(() => this.scrollToCard(comp.uuid), 50);

    this.componenteService.getPrecios(comp.uuid).subscribe({
      next: (res) => {
        const rawPrecios: any[] = res?.precios ?? [];
        const ordenados = [...rawPrecios].sort((a, b) => a.precio - b.precio);
        this.precios.set(ordenados);
        this.cargandoPrecios.set(false);
      },
      error: () => this.cargandoPrecios.set(false),
    });
  }

  private scrollToCard(uuid: string): void {
    const card = this.el.nativeElement.querySelector(`[data-uuid="${uuid}"]`);
    if (!card) return;
    // El scroll vive en el body (styles.scss: body overflow-y:auto)
    const scrollContainer = document.body;
    const cardTop    = card.getBoundingClientRect().top + scrollContainer.scrollTop;
    const headerH    = 56; // altura del header fijo
    const topbarH    = 52; // altura del top-bar sticky
    const offset     = headerH + topbarH + 16; // 16px de margen visual
    scrollContainer.scrollTo({ top: cardTop - offset, behavior: 'smooth' });
  }

  // ── Guardados / alertas ────────────────────────────────────────────────────

  /** Si no hay sesión iniciada, redirige a login guardando la página actual para volver luego */
  private requiereLogin(): boolean {
    if (this.auth.estaAutenticado()) return false;
    this.router.navigate(['/auth/login'], { queryParams: { returnUrl: this.router.url } });
    return true;
  }

  estaGuardado(uuid: string): boolean { return this.guardadosMap().has(uuid); }

  guardarComponente(): void {
    if (this.requiereLogin()) return;
    const comp = this.componenteSeleccionado();
    if (!comp || this.guardando()) return;
    this.guardando.set(true);
    const precioRef = this.precioSeleccionado() ?? this.precios()[0] ?? null;
    const tiendaUuid = precioRef?.tienda?.uuid ?? null;
    this.guardadoService.guardar(comp.uuid, tiendaUuid).subscribe({
      next: (res) => { this.guardadosMap.update(m => new Map(m).set(comp.uuid, res.uuid)); this.guardando.set(false); },
      error: (err) => { if (err.status === 422) this.cargarEstadoGuardados(); this.guardando.set(false); }
    });
  }

  eliminarGuardado(): void {
    if (this.requiereLogin()) return;
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
    if (this.requiereLogin()) return;
    this.mostrarAlerta.update(v => !v);
    if (this.mostrarAlerta() && this.precios().length > 0) {
      const precioRef = this.precioSeleccionado() ?? this.precios()[0];
      this.precioObjetivo.set(Math.round(precioRef.precio * 0.9));
    }
  }

  guardarAlerta(): void {
    if (this.requiereLogin()) return;
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
    if (this.requiereLogin()) return;
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
    return precio.toLocaleString('es-ES', { style: 'currency', currency: 'EUR', minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  nombreCategoria(slug: string): string {
    return this.categorias.find(c => c.slug === slug)?.label ?? slug;
  }
}