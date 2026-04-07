import { Component, OnInit, signal, computed, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { ComponenteService, Componente } from '../../core/services/componente.service';
import { GuardadoService } from '../../core/services/guardado.service';
import { AuthService } from '../../core/services/auth.service';
import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';

interface Slot {
  id: string;
  label: string;
  categoria: string;
  componente: Componente | null;
  precio: number | null;
}

interface Compatibilidad {
  compatible: boolean;
  advertencias: { tipo: string; mensaje: string }[];
  errores: { tipo: string; mensaje: string }[];
  notas: { tipo: string; mensaje: string }[];
  consumo_total_watts: number;
}

@Component({
  selector: 'app-configurator',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './configurator.component.html',
  styleUrl: './configurator.component.scss'
})
export class ConfiguratorComponent implements OnInit {

  private auth            = inject(AuthService);
  private guardadoService = inject(GuardadoService);

  slots: Slot[] = [
    { id: 'cpu',            label: 'CPU',            categoria: 'cpu',                componente: null, precio: null },
    { id: 'placa_base',     label: 'Placa Base',     categoria: 'placa_base',         componente: null, precio: null },
    { id: 'gpu',            label: 'GPU',            categoria: 'gpu',                componente: null, precio: null },
    { id: 'ram',            label: 'RAM',            categoria: 'ram',                componente: null, precio: null },
    { id: 'almacenamiento', label: 'Almacenamiento', categoria: 'almacenamiento',     componente: null, precio: null },
    { id: 'psu',            label: 'PSU',            categoria: 'psu',                componente: null, precio: null },
    { id: 'gabinete',       label: 'Gabinete',       categoria: 'gabinete',           componente: null, precio: null },
    { id: 'refrigeracion',  label: 'Refrigeración',  categoria: 'refrigeracion_aire', componente: null, precio: null },
    { id: 'ventilador',     label: 'Ventiladores',   categoria: 'ventilador',         componente: null, precio: null },
  ];

  ordenes = [
    { label: 'Relevancia',            value: '' },
    { label: 'Precio: menor a mayor', value: 'precio_asc' },
    { label: 'Precio: mayor a menor', value: 'precio_desc' },
    { label: 'Nombre A-Z',            value: 'nombre_asc' },
  ];

  slotActivo     = signal<Slot>(this.slots[0]);
  componentes    = signal<Componente[]>([]);
  cargando       = signal(false);
  busqueda       = '';
  ordenActivo    = '';
  precioMin: number | null = null;
  precioMax: number | null = null;
  private busqueda$ = new Subject<string>();

  compatibilidad = signal<Compatibilidad | null>(null);
  panelAbierto   = signal(false);
  cargandoCompat = signal(false);

  componenteDetalle = signal<Componente | null>(null);
  precios           = signal<any[]>([]);
  cargandoPrecios   = signal(false);

  Math = Math;

  // ── Guardar / Alertas ─────────────────────────────────────
  logueado = this.auth.estaAutenticado();

  guardadosMap = signal<Map<string, string>>(new Map());
  alertasMap   = signal<Map<string, string>>(new Map());

  guardando        = signal(false);
  eliminandoGuardado = signal(false);
  guardandoAlerta  = signal(false);
  mostrarAlerta    = signal(false);
  precioObjetivo   = signal<number | null>(null);

  // ── Computed ──────────────────────────────────────────────
  totalEstimado = computed(() =>
    this.slots.reduce((sum, s) => sum + (s.precio ?? 0), 0)
  );

  numErrores = computed(() => this.compatibilidad()?.errores?.length ?? 0);
  numAdvert  = computed(() => this.compatibilidad()?.advertencias?.length ?? 0);

  constructor(private componenteService: ComponenteService, private http: HttpClient) {}

  ngOnInit() {
    if (this.logueado) {
      this.cargarEstadoGuardados();
      this.cargarEstadoAlertas();
    }
    this.cargarSlot(this.slots[0]);
    this.busqueda$.pipe(debounceTime(350), distinctUntilChanged())
      .subscribe(() => this.cargarSlot(this.slotActivo()));
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

  cargarSlot(slot: Slot) {
    this.slotActivo.set(slot);
    this.cargando.set(true);
    this.componenteDetalle.set(null);
    this.mostrarAlerta.set(false);
    this.componenteService.buscar({
      categoria: slot.categoria,
      q: this.busqueda,
      page: 1,
      orden: this.ordenActivo,
    }).subscribe({
      next: (res) => { this.componentes.set(res.data); this.cargando.set(false); },
      error: () => this.cargando.set(false)
    });
  }

  onFiltroChange() { this.cargarSlot(this.slotActivo()); }
  onBusqueda()     { this.busqueda$.next(this.busqueda); }

  seleccionarComponente(comp: Componente) {
    const slot = this.slotActivo();
    slot.componente = comp;
    slot.precio = comp.precio_min;
    this.validarCompatibilidad();
    this.abrirPrecios(comp);
  }

  quitarComponente(slot: Slot, event: Event) {
    event.stopPropagation();
    slot.componente = null;
    slot.precio = null;
    this.validarCompatibilidad();
  }

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
      error: () => this.cargandoPrecios.set(false)
    });
  }

  cerrarDetalle() {
    this.componenteDetalle.set(null);
    this.mostrarAlerta.set(false);
  }

  validarCompatibilidad() {
    const uuids: Record<string, string> = {};
    this.slots.forEach(s => { if (s.componente) uuids[s.id] = s.componente.uuid; });
    if (Object.keys(uuids).length === 0) { this.compatibilidad.set(null); return; }
    this.cargandoCompat.set(true);
    this.http.post<Compatibilidad>('/api/v1/configurador/validar', { componentes: uuids }).subscribe({
      next: (res) => { this.compatibilidad.set(res); this.cargandoCompat.set(false); },
      error: () => this.cargandoCompat.set(false)
    });
  }

  togglePanel() { this.panelAbierto.update(v => !v); }

  // ── Guardar ───────────────────────────────────────────────

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
      }
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
      error: () => this.eliminandoGuardado.set(false)
    });
  }

  // ── Alertas ───────────────────────────────────────────────

  tieneAlerta(uuid: string): boolean { return this.alertasMap().has(uuid); }

  toggleFormAlerta(): void {
    this.mostrarAlerta.update(v => !v);
    if (this.mostrarAlerta() && this.precios().length > 0) {
      this.precioObjetivo.set(Math.round(this.precios()[0].precio * 0.9));
    }
  }

  guardarAlerta(): void {
    const comp = this.componenteDetalle();
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
    const comp = this.componenteDetalle();
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

  setPrecioObjetivo(v: string): void {
    this.precioObjetivo.set(v ? Number(v) : null);
  }

  // ── Helpers ───────────────────────────────────────────────

  formatPrecio(precio: number | null): string {
    if (!precio) return '';
    return precio.toLocaleString('es-ES', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });
  }

  nombreCategoria(slug: string): string {
    return this.slots.find(s => s.categoria === slug)?.label ?? slug;
  }

  slotIcono(categoria: string): string {
    const map: Record<string, string> = {
      cpu:             'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5',
      placa_base:      'M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18',
      gpu:             'M6 3h12l4 6-10 13L2 9z',
      ram:             'M4 6h16M4 10h16M4 14h16M4 18h16',
      almacenamiento:  'M22 12H2M5 12V6a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v6M5 12v6a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-6',
      psu:             'M13 2L3 14h9l-1 8 10-12h-9l1-8z',
      gabinete:        'M3 3h18v18H3zM9 3v18M3 9h6',
      refrigeracion_aire: 'M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4',
      ventilador:      'M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0M12 3c0 2.4 1.8 4.8 3 6M12 21c0-2.4-1.8-4.8-3-6',
    };
    return map[categoria] ?? 'M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z';
  }
}