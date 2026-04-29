import {
    Component, Input, OnChanges, SimpleChanges,
    signal, computed, HostListener, inject,
  } from '@angular/core';
  import { CommonModule } from '@angular/common';
  import {
    ComponenteService,
    HistorialPrecios,
    PuntoHistorial,
    PeriodoHistorial,
  } from '../../../core/services/componente.service';
  import { catchError, of } from 'rxjs';
  
  // ─── Tipos internos ───────────────────────────────────────────────────────────
  
  interface PuntoGrafico {
    x: number;       // 0–100 (%)
    yMin: number;    // 0–100 (%)
    yMax: number;
    yMedia: number;
    punto: PuntoHistorial;
  }
  
  const PERIODOS: { label: string; value: PeriodoHistorial }[] = [
    { label: '6M',  value: '6m' },
    { label: '1A',  value: '1y' },
    { label: '2A',  value: '2y' },
    { label: '3A',  value: '3y' },
  ];
  
  @Component({
    selector: 'app-price-history',
    standalone: true,
    imports: [CommonModule],
    templateUrl: './price-history.component.html',
    styleUrl: './price-history.component.scss',
  })
  export class PriceHistoryComponent implements OnChanges {

    readonly Math = Math;
  
    /** UUID del componente a mostrar */
    @Input({ required: true }) componenteUuid!: string;
  
    /** Nombre del componente (para el modal) */
    @Input() componenteNombre = '';
  
    /** Modo inline: si true, no muestra el header ni el botón de expandir */
    @Input() inline = false;
  
    readonly PERIODOS = PERIODOS;
  
    // ── Estado ────────────────────────────────────────────────────────────────
    historial    = signal<HistorialPrecios | null>(null);
    cargando     = signal(false);
    error        = signal(false);
    periodoActivo = signal<PeriodoHistorial>('1y');
    tiendaFiltro  = signal<string | undefined>(undefined);
    modalAbierto  = signal(false);
  
    // Tooltip
    tooltipPunto  = signal<PuntoHistorial | null>(null);
    tooltipX      = signal(0);
    tooltipY      = signal(0);
  
    // ── Computed ──────────────────────────────────────────────────────────────
  
    puntosGrafico = computed<PuntoGrafico[]>(() => {
      const h = this.historial();
      if (!h || h.puntos.length === 0) return [];
      return this.calcularPuntos(h.puntos);
    });
  
    // Para el preview inline: último año, máx 12 puntos
    puntosPreview = computed<PuntoGrafico[]>(() => {
      const pts = this.puntosGrafico();
      return pts.slice(-12);
    });
  
    polylineMedia = computed(() => this.toPolyline(this.puntosGrafico(), 'yMedia'));
    polylineMin   = computed(() => this.toPolyline(this.puntosGrafico(), 'yMin'));
    polylineMax   = computed(() => this.toPolyline(this.puntosGrafico(), 'yMax'));
  
    polylineMediaPreview = computed(() => this.toPolyline(this.puntosPreview(), 'yMedia'));
  
    // ── Lifecycle ─────────────────────────────────────────────────────────────
  
    private svc = inject(ComponenteService);
  
    ngOnChanges(changes: SimpleChanges) {
      if (changes['componenteUuid'] && this.componenteUuid) {
        this.cargar();
      }
    }
  
    // ── Datos ─────────────────────────────────────────────────────────────────
  
    private cargar() {
      this.cargando.set(true);
      this.error.set(false);
      this.historial.set(null);
  
      this.svc.getHistorial(
        this.componenteUuid,
        this.periodoActivo(),
        this.tiendaFiltro(),
      ).pipe(catchError(() => of(null))).subscribe(res => {
        this.historial.set(res);
        this.cargando.set(res === null);
        this.error.set(res === null);
        this.cargando.set(false);
      });
    }
  
    setPeriodo(p: PeriodoHistorial) {
      this.periodoActivo.set(p);
      this.cargar();
    }
  
    setTienda(uuid: string | undefined) {
      this.tiendaFiltro.set(uuid);
      this.cargar();
    }
  
    // ── Modal ─────────────────────────────────────────────────────────────────
  
    abrirModal() {
      this.modalAbierto.set(true);
      document.body.style.overflow = 'hidden';
      // Al abrir siempre cargamos el período activo
      this.cargar();
    }
  
    cerrarModal() {
      this.modalAbierto.set(false);
      document.body.style.overflow = '';
    }
  
    @HostListener('document:keydown.escape')
    onEsc() {
      if (this.modalAbierto()) this.cerrarModal();
    }
  
    // ── Gráfico ───────────────────────────────────────────────────────────────
  
    private calcularPuntos(puntos: PuntoHistorial[]): PuntoGrafico[] {
      if (puntos.length === 0) return [];
  
      const precios = puntos.flatMap(p => [p.min, p.max]);
      const globalMin = Math.min(...precios);
      const globalMax = Math.max(...precios);
      const rango = globalMax - globalMin || 1;
  
      return puntos.map((p, i) => ({
        x:      (i / Math.max(puntos.length - 1, 1)) * 100,
        yMin:   100 - ((p.min   - globalMin) / rango * 88 + 6),
        yMax:   100 - ((p.max   - globalMin) / rango * 88 + 6),
        yMedia: 100 - ((p.media - globalMin) / rango * 88 + 6),
        punto: p,
      }));
    }
  
    private toPolyline(pts: PuntoGrafico[], campo: 'yMedia' | 'yMin' | 'yMax'): string {
      return pts.map(p => `${p.x},${p[campo]}`).join(' ');
    }
  
    // ── Tooltip ───────────────────────────────────────────────────────────────
  
    onMouseEnterPunto(evento: MouseEvent, punto: PuntoHistorial) {
      this.tooltipPunto.set(punto);
      this.posicionarTooltip(evento);
    }
  
    onMouseMovePunto(evento: MouseEvent) {
      this.posicionarTooltip(evento);
    }
  
    onMouseLeavePunto() {
      this.tooltipPunto.set(null);
    }
  
    private posicionarTooltip(e: MouseEvent) {
      const rect = (e.currentTarget as HTMLElement)
        .closest('.grafico-svg-wrap')?.getBoundingClientRect();
      if (!rect) return;
      this.tooltipX.set(e.clientX - rect.left);
      this.tooltipY.set(e.clientY - rect.top - 12);
    }
  
    // ── Helpers ───────────────────────────────────────────────────────────────
  
    formatPrecio(v: number | null): string {
      if (v === null || v === undefined) return '—';
      return v.toLocaleString('es-ES', {
        style: 'currency', currency: 'EUR', maximumFractionDigits: 0,
      });
    }
  
    formatPeriodo(p: string): string {
      const [y, m] = p.split('-');
      const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
      return `${meses[parseInt(m, 10) - 1]} ${y}`;
    }
  
    tendencia(): 'baja' | 'sube' | 'estable' {
      const pts = this.puntosGrafico();
      if (pts.length < 2) return 'estable';
      const diff = pts[pts.length - 1].punto.media - pts[0].punto.media;
      if (diff < -2) return 'baja';
      if (diff >  2) return 'sube';
      return 'estable';
    }
  
    porcentajeDesdeMax(): number | null {
      const h = this.historial();
      if (!h?.resumen.max || !h?.resumen.actual) return null;
      return Math.round(((h.resumen.actual - h.resumen.max) / h.resumen.max) * 100);
    }
  }