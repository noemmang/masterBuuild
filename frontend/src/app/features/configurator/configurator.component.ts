import { Component, OnInit, signal, computed, inject, ChangeDetectionStrategy, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { Router, ActivatedRoute } from '@angular/router';
import { ComponenteService, Componente, ComponenteDetalle } from '../../core/services/componente.service';
import { GuardadoService, SlotGuardado, ConfiguracionGuardada } from '../../core/services/guardado.service';
import { AuthService } from '../../core/services/auth.service';
import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';
import { PriceHistoryComponent } from '../../shared/components/price-history/price-history.component';

// ── Tipos ────────────────────────────────────────────────────────────────────

/** Slots que admiten más de una unidad */
const SLOTS_APILABLES = new Set(['almacenamiento', 'ventilador']);

interface SlotEntrada {
  componente: Componente;
  cantidad: number;
}

interface Slot {
  id: string;
  label: string;
  categoria: string;
  entradas: SlotEntrada[];
  get componente(): Componente | null;
  get precio(): number | null;
}

function crearSlot(id: string, label: string, categoria: string): Slot {
  const entradas: SlotEntrada[] = [];
  return {
    id, label, categoria, entradas,
    get componente() { return entradas[0]?.componente ?? null; },
    get precio() {
      return entradas.reduce((s, e) => s + (e.componente.precio_min ?? 0) * e.cantidad, 0) || null;
    },
  };
}

interface Compatibilidad {
  compatible: boolean;
  advertencias: { tipo: string; mensaje: string }[];
  errores:      { tipo: string; mensaje: string }[];
  notas:        { tipo: string; mensaje: string }[];
  consumo_total_watts: number;
}

interface FiltrosCompat {
  socket_id?:               number;
  tipo_memoria_id?:         number;
  factor_forma_soportado_id?: number;
  longitud_max_mm?:         number;
  longitud_gpu_min_mm?:     number;
  potencia_min?:            number;
  tdp_min?:                 number;
  altura_max_mm?:           number;
  radiador_mm?:             string;
}

// ── Componente ───────────────────────────────────────────────────────────────

@Component({
  selector: 'app-configurator',
  standalone: true,
  imports: [CommonModule, FormsModule, PriceHistoryComponent],
  templateUrl: './configurator.component.html',
  styleUrl: './configurator.component.scss',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ConfiguratorComponent implements OnInit {

  private auth            = inject(AuthService);
  private guardadoService = inject(GuardadoService);
  private router          = inject(Router);
  private route           = inject(ActivatedRoute);
  private cdr             = inject(ChangeDetectorRef);

  // ── Slots ─────────────────────────────────────────────────────
  slots: Slot[] = [
    crearSlot('cpu',            'CPU',            'cpu'),
    crearSlot('placa_base',     'Placa Base',     'placa_base'),
    crearSlot('gpu',            'GPU',            'gpu'),
    crearSlot('ram',            'RAM',            'ram'),
    crearSlot('almacenamiento', 'Almacenamiento', 'almacenamiento'),
    crearSlot('psu',            'PSU',            'psu'),
    crearSlot('gabinete',       'Gabinete',       'gabinete'),
    crearSlot('refrigeracion',  'Refrigeración',  'refrigeracion_aire'),
    crearSlot('ventilador',     'Ventiladores',   'ventilador'),
  ];

  esApilable(slot: Slot): boolean { return SLOTS_APILABLES.has(slot.id); }

  ordenes = [
    { label: 'Relevancia',            value: '' },
    { label: 'Precio: menor a mayor', value: 'precio_asc' },
    { label: 'Precio: mayor a menor', value: 'precio_desc' },
    { label: 'Nombre A-Z',            value: 'nombre_asc' },
  ];

  // ── Estado UI ─────────────────────────────────────────────────
  slotActivo     = signal<Slot>(this.slots[0]);
  /** Incrementar para notificar a computed signals que las entradas del slot cambiaron */
  private _entriesVersion = signal(0);
  componentes    = signal<Componente[]>([]);
  cargando       = signal(false);
  busqueda       = '';
  ordenActivo    = '';
  precioMin: number | null = null;
  precioMax: number | null = null;
  private busqueda$ = new Subject<string>();

  filtrosCompat   = signal<FiltrosCompat>({});
  soloCompatibles = signal(true);

  compatibilidad = signal<Compatibilidad | null>(null);
  panelAbierto   = signal(false);
  cargandoCompat = signal(false);

  // ── Restauración de configuración guardada ────────────────────
  restaurando = signal(false);

  componenteDetalle = signal<Componente | null>(null);
  precios           = signal<any[]>([]);
  cargandoPrecios   = signal(false);

  Math = Math;

  // ── Guardar / Alertas ─────────────────────────────────────────
  logueado = this.auth.estaAutenticado();

  guardadosMap = signal<Map<string, string>>(new Map());
  alertasMap   = signal<Map<string, string>>(new Map());

  guardando          = signal(false);
  eliminandoGuardado = signal(false);
  guardandoAlerta    = signal(false);
  mostrarAlerta      = signal(false);
  precioObjetivo     = signal<number | null>(null);
  copiado            = signal<string | null>(null);

  // ── Guardar configuración ─────────────────────────────────────
  modalGuardarAbierto = signal(false);
  guardandoConfig     = signal(false);
  nombreConfig        = signal('');
  notasConfig         = signal('');

  // ── Cache de detalles completos ───────────────────────────────
  private detallesCache = new Map<string, ComponenteDetalle>();

  // ── Total / desglose ──────────────────────────────────────────
  totalEstimado  = signal<number>(0);
  desglosePrecio = signal<{ label: string; subtotal: number; entradas: SlotEntrada[] }[]>([]);

  private recalcularTotal(): void {
    let total = 0;
    const desglose: { label: string; subtotal: number; entradas: SlotEntrada[] }[] = [];
    for (const s of this.slots) {
      if (s.entradas.length === 0) continue;
      const subtotal = s.entradas.reduce(
        (sum, e) => sum + (e.componente.precio_min ?? 0) * e.cantidad, 0
      );
      total += subtotal;
      desglose.push({ label: s.label, subtotal, entradas: [...s.entradas] });
    }
    this.totalEstimado.set(total);
    this.desglosePrecio.set(desglose);
  }

  // ── Computed ──────────────────────────────────────────────────
  numErrores       = computed(() => this.compatibilidad()?.errores?.length ?? 0);
  numAdvert        = computed(() => this.compatibilidad()?.advertencias?.length ?? 0);

  /** Set de UUIDs seleccionados en el slot activo — se actualiza solo cuando cambia slotActivo */
  selectedUuids    = computed(() => {
    this._entriesVersion(); // dependencia reactiva
    return new Set(this.slotActivo().entradas.map(e => e.componente.uuid));
  });

  get numSeleccionados(): number {
    return this.slots.filter(s => s.entradas.length > 0).length;
  }

  constructor(private componenteService: ComponenteService, private http: HttpClient) {}

  ngOnInit() {
    if (this.logueado) {
      this.cargarEstadoGuardados();
      this.cargarEstadoAlertas();
    }

    this.busqueda$
      .pipe(debounceTime(350), distinctUntilChanged())
      .subscribe(() => this.cargarSlot(this.slotActivo()));

    // ── Leer ?cfg= para restaurar configuración guardada ─────────
    this.route.queryParams.subscribe(params => {
      const cfgUuid = params['cfg'] as string | undefined;
      if (cfgUuid) {
        this.restaurarConfiguracion(cfgUuid);
      } else {
        this.cargarSlot(this.slots[0]);
      }
    });
  }

  // ── Restaurar configuración ───────────────────────────────────

  /**
   * Carga la configuración guardada por uuid, recupera cada componente
   * de la API y rellena los slots correspondientes.
   */
  private restaurarConfiguracion(cfgUuid: string): void {
    this.restaurando.set(true);

    this.guardadoService.listarConfiguraciones().subscribe({
      next: (configs) => {
        const cfg = configs.find(c => c.uuid === cfgUuid);
        if (!cfg) {
          this.restaurando.set(false);
          this.cargarSlot(this.slots[0]);
          return;
        }
        this.cargarSlotsDesdeConfig(cfg);
      },
      error: () => {
        this.restaurando.set(false);
        this.cargarSlot(this.slots[0]);
      }
    });
  }

  /**
   * Dado un ConfiguracionGuardada, busca cada componente por UUID en la API
   * y lo pone en el slot correcto con su cantidad.
   */
  private cargarSlotsDesdeConfig(cfg: ConfiguracionGuardada): void {
    // Limpiar slots
    this.slots.forEach(s => s.entradas.splice(0));

    // Recopilar todos los UUIDs que hay que cargar
    const promesas: Promise<void>[] = [];

    for (const slotGuardado of cfg.slots) {
      const slot = this.slots.find(s => s.id === slotGuardado.categoria);
      if (!slot) continue;

      for (const compGuardado of slotGuardado.componentes) {
        const p = new Promise<void>((resolve) => {
          this.componenteService.getDetalle(compGuardado.uuid).subscribe({
            next: (detalle) => {
              // Construir un Componente básico a partir del detalle
              const comp: Componente = {
                uuid:        detalle.uuid,
                nombre:      detalle.nombre,
                categoria:   detalle.categoria,
                imagen_url:  detalle.imagen_url,
                marca:       detalle.marca,
                precio_min:  compGuardado.precio ?? detalle.precio_min,
                precio_max:  detalle.precio_max,
                num_tiendas: detalle.num_tiendas,
                tiene_cupon: detalle.tiene_cupon,
                tiene_regalo:  detalle.tiene_regalo,
                bajada_precio: detalle.bajada_precio,
              };
              slot.entradas.push({ componente: comp, cantidad: compGuardado.cantidad });
              this.detallesCache.set(comp.uuid, detalle);
              resolve();
            },
            error: () => resolve(), // Si falla un componente, continuamos con los demás
          });
        });
        promesas.push(p);
      }
    }

    // Cuando todos los componentes están cargados, recalcular y validar
    Promise.all(promesas).then(() => {
      this.recalcularFiltrosCompat();
      this.recalcularTotal();
      this.validarCompatibilidad();
      this.restaurando.set(false);

      // Activar el primer slot que tenga componentes, o el primero
      const primerSlotConComp = this.slots.find(s => s.entradas.length > 0) ?? this.slots[0];
      this.cargarSlot(primerSlotConComp);
    });
  }

  // ── Guardados / Alertas ───────────────────────────────────────

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

  // ── Carga de listado ──────────────────────────────────────────

  cargarSlot(slot: Slot) {
    this.slotActivo.set(slot);
    this.cargando.set(true);
    this.componenteDetalle.set(null);
    this.mostrarAlerta.set(false);

    const params: Record<string, any> = {
      categoria: slot.categoria,
      q:         this.busqueda,
      page:      1,
      orden:     this.ordenActivo,
    };
    if (this.precioMin) params['precio_min'] = this.precioMin;
    if (this.precioMax) params['precio_max'] = this.precioMax;

    if (this.soloCompatibles()) {
      this.aplicarFiltrosCompatParaSlot(slot.id, this.filtrosCompat(), params);
    }

    this.componenteService.buscarConFiltros(params).subscribe({
      next: (res) => { this.componentes.set(res.data); this.cargando.set(false); this.cdr.markForCheck(); },
      error: ()    => { this.cargando.set(false); this.cdr.markForCheck(); },
    });
  }

  private aplicarFiltrosCompatParaSlot(
    slotId: string,
    fc: FiltrosCompat,
    params: Record<string, any>,
  ): void {
    switch (slotId) {
      case 'cpu':
        if (fc.socket_id)       params['socket_id']       = fc.socket_id;
        if (fc.tipo_memoria_id) params['tipo_memoria_id'] = fc.tipo_memoria_id;
        break;
      case 'placa_base':
        if (fc.socket_id)       params['socket_id']       = fc.socket_id;
        if (fc.tipo_memoria_id) params['tipo_memoria_id'] = fc.tipo_memoria_id;
        break;
      case 'ram':
        if (fc.tipo_memoria_id) params['tipo_memoria_id'] = fc.tipo_memoria_id;
        break;
      case 'gpu':
        if (fc.longitud_max_mm) params['longitud_max_mm'] = fc.longitud_max_mm;
        break;
      case 'gabinete':
        if (fc.factor_forma_soportado_id) params['factor_forma_soportado_id'] = fc.factor_forma_soportado_id;
        if (fc.longitud_gpu_min_mm) params['longitud_gpu_min_mm'] = fc.longitud_gpu_min_mm;
        break;
      case 'psu':
        if (fc.potencia_min) params['potencia_min'] = fc.potencia_min;
        break;
      case 'refrigeracion':
        if (fc.socket_id)     params['socket_id']    = fc.socket_id;
        if (fc.tdp_min)       params['tdp_min']       = fc.tdp_min;
        if (fc.altura_max_mm) params['altura_max_mm'] = fc.altura_max_mm;
        if (fc.radiador_mm)   params['radiador_mm']   = fc.radiador_mm;
        break;
    }
  }

  onFiltroChange() { this.cargarSlot(this.slotActivo()); }
  onBusqueda()     { this.busqueda$.next(this.busqueda); }

  toggleSoloCompatibles() {
    this.soloCompatibles.update(v => !v);
    this.cargarSlot(this.slotActivo());
  }

  // ── Selección / eliminación ───────────────────────────────────

  seleccionarComponente(comp: Componente) {
    const slot = this.slotActivo();

    if (this.esApilable(slot)) {
      const existente = slot.entradas.find(e => e.componente.uuid === comp.uuid);
      if (existente) {
        existente.cantidad++;
      } else {
        slot.entradas.push({ componente: comp, cantidad: 1 });
      }
    } else {
      slot.entradas.splice(0, slot.entradas.length, { componente: comp, cantidad: 1 });
    }

    // Notificar cambio de entradas para que los computed signals reactivos se actualicen
    this._entriesVersion.update(v => v + 1);

    if (!this.detallesCache.has(comp.uuid)) {
      this.componenteService.getDetalle(comp.uuid).subscribe({
        next: (detalle) => {
          this.detallesCache.set(comp.uuid, detalle);
          this.recalcularFiltrosCompat();
          this.validarCompatibilidad();
          this.cdr.markForCheck();
        },
      });
    } else {
      this.recalcularFiltrosCompat();
      this.validarCompatibilidad();
    }

    this.recalcularTotal();
    this.abrirPrecios(comp);
  }

  quitarComponente(slot: Slot, event: Event, idx = 0) {
    event.stopPropagation();
    const uuid = slot.entradas[idx]?.componente.uuid;
    slot.entradas.splice(idx, 1);
    if (uuid) this.detallesCache.delete(uuid);
    this._entriesVersion.update(v => v + 1);
    this.recalcularFiltrosCompat();
    this.validarCompatibilidad();
    this.recalcularTotal();
    if (this.componenteDetalle() && slot.entradas.length === 0) {
      this.componenteDetalle.set(null);
    }
  }

  cambiarCantidad(slot: Slot, idx: number, delta: number) {
    const entrada = slot.entradas[idx];
    if (!entrada) return;
    const nueva = entrada.cantidad + delta;
    if (nueva <= 0) {
      this.detallesCache.delete(entrada.componente.uuid);
      slot.entradas.splice(idx, 1);
    } else {
      entrada.cantidad = nueva;
    }
    this._entriesVersion.update(v => v + 1);
    this.recalcularFiltrosCompat();
    this.validarCompatibilidad();
    this.recalcularTotal();
  }

  setCantidad(slot: Slot, idx: number, valor: string) {
    const n = parseInt(valor, 10);
    if (isNaN(n) || n < 1) return;
    const entrada = slot.entradas[idx];
    if (entrada) { entrada.cantidad = n; }
    this.recalcularFiltrosCompat();
    this.validarCompatibilidad();
    this.recalcularTotal();
  }

  // ── Filtros de compatibilidad dinámica ────────────────────────

  private recalcularFiltrosCompat(): void {
    const fc: FiltrosCompat = {};

    const cpuUuid  = this.slots.find(s => s.id === 'cpu')?.componente?.uuid;
    const pbUuid   = this.slots.find(s => s.id === 'placa_base')?.componente?.uuid;
    const gpuUuid  = this.slots.find(s => s.id === 'gpu')?.componente?.uuid;
    const gabUuid  = this.slots.find(s => s.id === 'gabinete')?.componente?.uuid;

    const cpu      = cpuUuid ? this.detallesCache.get(cpuUuid)  : undefined;
    const pb       = pbUuid  ? this.detallesCache.get(pbUuid)   : undefined;
    const gpu      = gpuUuid ? this.detallesCache.get(gpuUuid)  : undefined;
    const gabinete = gabUuid ? this.detallesCache.get(gabUuid)  : undefined;

    if (cpu?.cpu) {
      const c = cpu.cpu as any;
      if (c.socket_id)       fc.socket_id       = c.socket_id;
      if (c.tipo_memoria_id) fc.tipo_memoria_id = c.tipo_memoria_id;
      fc.tdp_min = c.tdp_max_watts ?? c.tdp_watts;
    }

    if (pb?.placa_base) {
      const p = pb.placa_base as any;
      if (!fc.socket_id       && p.socket_id)       fc.socket_id       = p.socket_id;
      if (!fc.tipo_memoria_id && p.tipo_memoria_id) fc.tipo_memoria_id = p.tipo_memoria_id;
      if (p.factor_forma_id) fc.factor_forma_soportado_id = p.factor_forma_id;
    }

    if (gpu?.gpu) {
      const g = gpu.gpu as any;
      if (g.longitud_mm)      fc.longitud_gpu_min_mm = g.longitud_mm;
      if (g.psu_minima_watts) fc.potencia_min        = g.psu_minima_watts;
    }

    if (gabinete?.gabinete) {
      const gab = gabinete.gabinete as any;
      if (gab.longitud_gpu_max_mm)  fc.longitud_max_mm = gab.longitud_gpu_max_mm;
      if (gab.altura_cooler_max_mm) fc.altura_max_mm   = gab.altura_cooler_max_mm;
      if (Array.isArray(gab.soporte_radiadores) && gab.soporte_radiadores.length) {
        fc.radiador_mm = gab.soporte_radiadores.join(',');
      }
    }

    this.filtrosCompat.set(fc);

    if (this.soloCompatibles()) {
      this.cargarSlot(this.slotActivo());
    }
  }

  // ── Compatibilidad ────────────────────────────────────────────

  validarCompatibilidad() {
    const slotMap: Record<string, string> = {};
    this.slots.forEach(s => {
      if (s.entradas.length > 0) slotMap[s.id] = s.entradas[0].componente.uuid;
    });

    if (Object.keys(slotMap).length === 0) { this.compatibilidad.set(null); return; }

    const categoriaRefrig = this.slots.find(s => s.id === 'refrigeracion')?.categoria;
    const tipoRefrig = categoriaRefrig === 'refrigeracion_liquida' ? 'liquida' : 'aire';

    const payload = {
      cpu_uuid:           slotMap['cpu']           ?? null,
      gpu_uuid:           slotMap['gpu']           ?? null,
      ram_uuid:           slotMap['ram']           ?? null,
      placa_base_uuid:    slotMap['placa_base']    ?? null,
      psu_uuid:           slotMap['psu']           ?? null,
      gabinete_uuid:      slotMap['gabinete']      ?? null,
      refrigeracion_uuid: slotMap['refrigeracion'] ?? null,
      tipo_refrigeracion: tipoRefrig,
    };

    this.cargandoCompat.set(true);
    this.http.post<Compatibilidad>('/api/v1/configurador/validar', payload).subscribe({
      next: (res) => { this.compatibilidad.set(res); this.cargandoCompat.set(false); },
      error: ()    => this.cargandoCompat.set(false),
    });
  }

  togglePanel() { this.panelAbierto.update(v => !v); }

  // ── Precios / detalle ─────────────────────────────────────────

  abrirPrecios(comp: Componente) {
    if (this.componenteDetalle()?.uuid === comp.uuid) {
      this.componenteDetalle.set(null);
      return;
    }
    this.componenteDetalle.set(comp);
    this.mostrarAlerta.set(false);
    this.precioObjetivo.set(null);
    this.cargandoPrecios.set(true);
    this.precios.set([]);
    this.componenteService.getPrecios(comp.uuid).subscribe({
      next: (res) => { this.precios.set(res.precios || res); this.cargandoPrecios.set(false); },
      error: ()   => this.cargandoPrecios.set(false),
    });
  }

  cerrarDetalle() {
    this.componenteDetalle.set(null);
    this.mostrarAlerta.set(false);
  }

  // ── Guardar componente ────────────────────────────────────────

  estaGuardado(uuid: string): boolean { return this.guardadosMap().has(uuid); }

  guardarComponente(): void {
    const comp = this.componenteDetalle();
    if (!comp || this.guardando()) return;
    this.guardando.set(true);
    this.guardadoService.guardar(comp.uuid).subscribe({
      next: (res) => {
        this.guardadosMap.update(m => new Map(m).set(comp.uuid, res.uuid));
        this.guardando.set(false);
      },
      error: (err) => {
        if (err.status === 422) this.cargarEstadoGuardados();
        this.guardando.set(false);
      },
    });
  }

  eliminarGuardado(): void {
    const comp = this.componenteDetalle();
    if (!comp || this.eliminandoGuardado()) return;
    const uuidGuardado = this.guardadosMap().get(comp.uuid);
    if (!uuidGuardado) return;
    this.eliminandoGuardado.set(true);
    this.guardadoService.eliminar(uuidGuardado).subscribe({
      next: () => {
        this.guardadosMap.update(m => { const n = new Map(m); n.delete(comp.uuid); return n; });
        this.eliminandoGuardado.set(false);
      },
      error: () => this.eliminandoGuardado.set(false),
    });
  }

  // ── Alertas ───────────────────────────────────────────────────

  tieneAlerta(uuid: string): boolean { return this.alertasMap().has(uuid); }

  toggleFormAlerta(): void {
    this.mostrarAlerta.update(v => !v);
    if (this.mostrarAlerta() && this.precios().length > 0) {
      this.precioObjetivo.set(Math.round(this.precios()[0].precio * 0.9));
    }
  }

  guardarAlerta(): void {
    this.guardandoAlerta.set(true);
    const comp = this.componenteDetalle();
    if (!comp || !this.precioObjetivo() || this.guardandoAlerta()) return;
    this.guardadoService.crearAlerta(comp.uuid, this.precioObjetivo()!).subscribe({
      next: (res) => {
        this.alertasMap.update(m => new Map(m).set(comp.uuid, res.uuid));
        this.guardandoAlerta.set(false);
        this.mostrarAlerta.set(false);
      },
      error: () => this.guardandoAlerta.set(false),
    });
  }

  eliminarAlerta(): void {
    const comp = this.componenteDetalle();
    if (!comp) return;
    const uuidAlerta = this.alertasMap().get(comp.uuid);
    if (!uuidAlerta) return;
    this.guardadoService.eliminarAlerta(uuidAlerta).subscribe({
      next: () => {
        this.alertasMap.update(m => { const n = new Map(m); n.delete(comp.uuid); return n; });
        this.mostrarAlerta.set(false);
      },
    });
  }

  setPrecioObjetivo(v: string): void {
    this.precioObjetivo.set(v ? Number(v) : null);
  }

  copiarCodigo(codigo: string): void {
    navigator.clipboard.writeText(codigo).then(() => {
      this.copiado.set(codigo);
      setTimeout(() => {
        if (this.copiado() === codigo) this.copiado.set(null);
      }, 2000);
    });
  }

  // ── Guardar configuración ─────────────────────────────────────

  abrirModalGuardar(): void {
    this.nombreConfig.set('');
    this.notasConfig.set('');
    this.modalGuardarAbierto.set(true);
  }

  cerrarModalGuardar(): void {
    this.modalGuardarAbierto.set(false);
  }

  confirmarGuardarConfig(): void {
    if (!this.nombreConfig().trim()) return;

    const slotsPayload: SlotGuardado[] = this.slots
      .filter(s => s.entradas.length > 0)
      .map(s => ({
        categoria: s.id,
        label:     s.label,
        componentes: s.entradas.map(e => ({
          uuid:     e.componente.uuid,
          nombre:   e.componente.nombre,
          cantidad: e.cantidad,
          precio:   e.componente.precio_min ?? null,
        })),
      }));

    this.guardandoConfig.set(true);
    this.guardadoService.guardarConfiguracion({
      nombre:     this.nombreConfig().trim(),
      notas:      this.notasConfig().trim() || null,
      total:      this.totalEstimado(),
      compatible: this.numErrores() === 0,
      slots:      slotsPayload,
    }).subscribe({
      next: () => {
        this.guardandoConfig.set(false);
        this.cerrarModalGuardar();
        this.router.navigate(['/guardados'], { queryParams: { tab: 'configuraciones' } });
      },
      error: () => this.guardandoConfig.set(false),
    });
  }

  // ── Helpers ───────────────────────────────────────────────────

  formatPrecio(precio: number | null | undefined): string {
    if (!precio) return '';
    return precio.toLocaleString('es-ES', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });
  }

  nombreCategoria(slug: string): string {
    return this.slots.find(s => s.categoria === slug)?.label ?? slug;
  }

  labelSlotActivo(): string { return this.slotActivo().label; }

  yaSeleccionado(uuid: string): boolean {
    return this.selectedUuids().has(uuid);
  }

  cantidadEnSlot(uuid: string): number {
    return this.slotActivo().entradas.find(e => e.componente.uuid === uuid)?.cantidad ?? 0;
  }
}