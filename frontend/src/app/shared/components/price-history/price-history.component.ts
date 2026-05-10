import {
  Component, Input, OnChanges, SimpleChanges,
  signal, computed, HostListener, inject,
  AfterViewInit, OnDestroy, ViewChild, ElementRef, NgZone,
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
  x: number;
  yMin: number;
  yMax: number;
  yMedia: number;
  punto: PuntoHistorial;
}

const PERIODOS: { label: string; value: PeriodoHistorial }[] = [
  { label: '6M', value: '6m' },
  { label: '1A', value: '1y' },
  { label: '2A', value: '2y' },
  { label: '3A', value: '3y' },
];

@Component({
  selector: 'app-price-history',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './price-history.component.html',
  styleUrl: './price-history.component.scss',
})
export class PriceHistoryComponent implements OnChanges, OnDestroy {

  readonly Math = Math;

  @Input({ required: true }) componenteUuid!: string;
  @Input() componenteNombre = '';
  @Input() inline = false;

  @ViewChild('chartContainer') chartContainer!: ElementRef;

  readonly PERIODOS = PERIODOS;

  // ── Estado ────────────────────────────────────────────────────────────────
  historial     = signal<HistorialPrecios | null>(null);
  cargando      = signal(false);
  error         = signal(false);
  periodoActivo = signal<PeriodoHistorial>('1y');
  tiendaFiltro  = signal<string | undefined>(undefined);
  modalAbierto  = signal(false);

  tiendasDisponibles = signal<{ uuid: string; nombre: string }[]>([]);

  private svc   = inject(ComponenteService);
  private ngZone = inject(NgZone);
  private apexChart: any = null;

  // ── Computed (para preview inline — resumen stats) ────────────────────────

  puntosPreview = computed<PuntoGrafico[]>(() => {
    const h = this.historial();
    if (!h || h.puntos.length === 0) return [];
    return this.calcularPuntos(h.puntos).slice(-12);
  });

  // ── Lifecycle ─────────────────────────────────────────────────────────────

  ngOnChanges(changes: SimpleChanges) {
    if (changes['componenteUuid'] && this.componenteUuid) {
      this.cargar();
    }
  }

  ngOnDestroy() {
    this.destroyChart();
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
      this.error.set(res === null);
      this.cargando.set(false);

      if (res && !this.tiendaFiltro() && res.tiendas.length > 0) {
        this.tiendasDisponibles.set(res.tiendas);
      }

      // Si el modal está abierto, re-renderizar el gráfico
      if (this.modalAbierto() && res) {
        setTimeout(() => this.renderChart(), 50);
      }
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
    // Si ya tenemos datos, renderizar; si no, cargar
    if (this.historial()) {
      setTimeout(() => this.renderChart(), 80);
    } else {
      this.cargar();
    }
  }

  cerrarModal() {
    this.modalAbierto.set(false);
    document.body.style.overflow = '';
    this.destroyChart();
  }

  @HostListener('document:keydown.escape')
  onEsc() {
    if (this.modalAbierto()) this.cerrarModal();
  }

  // ── ApexCharts ────────────────────────────────────────────────────────────

  private destroyChart() {
    if (this.apexChart) {
      this.apexChart.destroy();
      this.apexChart = null;
    }
  }

  private getAccentColor(): string {
    return getComputedStyle(document.documentElement)
      .getPropertyValue('--accent').trim() || '#6366f1';
  }

  private renderChart() {
    const h = this.historial();
    if (!h || h.puntos.length === 0) return;

    const el = document.getElementById('ph-apex-chart');
    if (!el) return;

    this.destroyChart();

    const accent = this.getAccentColor();
    const puntos = h.puntos;

    // Convertir periodos 'YYYY-MM' a timestamps (primer día del mes, UTC)
    const toTimestamp = (periodo: string): number => {
      const [y, m] = periodo.split('-').map(Number);
      return Date.UTC(y, m - 1, 1);
    };

    // Serie de la línea media
    const serieMedia = puntos.map(p => ({
      x: toTimestamp(p.periodo),
      y: p.media,
    }));

    // Serie de rango min-max (rangeArea)
    const serieRango = puntos.map(p => ({
      x: toTimestamp(p.periodo),
      y: [p.min, p.max] as [number, number],
    }));

    // Precio mín y máx históricos para líneas de referencia
    const resumen = h.resumen;

    // Annotations para precio mínimo y máximo histórico (estilo Camelcamelcamel)
    const annotations: any = { yaxis: [] };

    if (resumen.min !== null) {
      annotations.yaxis.push({
        y: resumen.min,
        borderColor: '#22c55e',
        borderWidth: 1,
        strokeDashArray: 5,
        label: {
          text: this.formatPrecio(resumen.min),
          position: 'right',
          offsetX: -4,
          style: {
            background: 'transparent',
            color: '#22c55e',
            fontSize: '11px',
            fontWeight: 600,
            fontFamily: 'inherit',
            padding: { top: 2, bottom: 2, left: 4, right: 4 },
          },
        },
      });
    }

    if (resumen.max !== null) {
      annotations.yaxis.push({
        y: resumen.max,
        borderColor: '#ef4444',
        borderWidth: 1,
        strokeDashArray: 5,
        label: {
          text: this.formatPrecio(resumen.max),
          position: 'right',
          offsetX: -4,
          style: {
            background: 'transparent',
            color: '#ef4444',
            fontSize: '11px',
            fontWeight: 600,
            fontFamily: 'inherit',
            padding: { top: 2, bottom: 2, left: 4, right: 4 },
          },
        },
      });
    }

    // Mapa de periodo → tiendas para el tooltip
    const tiendasPorPeriodo = new Map<number, number>();
    puntos.forEach(p => tiendasPorPeriodo.set(toTimestamp(p.periodo), p.tiendas));

    // Detectar tema oscuro
    const isDark = document.documentElement.classList.contains('dark') ||
      getComputedStyle(document.documentElement).getPropertyValue('--surface').trim().startsWith('#1') ||
      getComputedStyle(document.documentElement).getPropertyValue('--surface').trim().startsWith('#0');

    const textMuted = getComputedStyle(document.documentElement)
      .getPropertyValue('--text-muted').trim() || '#94a3b8';
    const borderColor = getComputedStyle(document.documentElement)
      .getPropertyValue('--border').trim() || '#e2e8f0';
    const surfaceColor = getComputedStyle(document.documentElement)
      .getPropertyValue('--surface').trim() || '#ffffff';
    const textColor = getComputedStyle(document.documentElement)
      .getPropertyValue('--text').trim() || '#0f172a';

    const options: ApexCharts.ApexOptions = {
      series: [
        {
          name: 'Media',
          type: 'line' as const,
          data: serieMedia,
          color: accent,
        },
        {
          name: 'Rango',
          type: 'rangeArea' as const,
          data: serieRango,
          color: accent,
        },
      ],
      chart: {
        type: 'line' as const,
        height: '100%',
        background: 'transparent',
        toolbar: { show: false },
        zoom: { enabled: false },
        animations: {
          enabled: true,
          speed: 400,
          animateGradually: { enabled: false },
        },
        fontFamily: 'inherit',
      },
      stroke: {
        curve: 'stepline' as const,
        width: [1.5, 0],
        lineCap: 'square' as const,
      },
      fill: {
        type: ['solid', 'solid'] as ('solid' | 'gradient' | 'pattern' | 'image')[],
        opacity: [1, 0.12],
      },
      markers: {
        size: [3, 0],
        strokeWidth: 0,
        hover: { size: 5 },
        colors: [accent],
      },
      xaxis: {
        type: 'datetime' as const,
        labels: {
          style: { colors: textMuted, fontSize: '11px', fontFamily: 'inherit' },
          datetimeFormatter: {
            year: 'yyyy',
            month: "MMM 'yy",
            day: 'dd MMM',
          },
          datetimeUTC: true,
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
        crosshairs: {
          show: true,
          stroke: { color: borderColor, width: 1, dashArray: 3 },
        },
        tooltip: { enabled: false },
      },
      yaxis: {
        labels: {
          style: { colors: textMuted, fontSize: '11px', fontFamily: 'inherit' },
          formatter: (v: number) => this.formatPrecio(v),
          offsetX: -4,
        },
        tickAmount: 5,
        forceNiceScale: true,
      },
      grid: {
        borderColor: borderColor,
        strokeDashArray: 0,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } },
        padding: { top: 0, right: 40, bottom: 0, left: 0 },
      },
      legend: { show: false },
      annotations,
      tooltip: {
        shared: true,
        intersect: false,
        theme: isDark ? 'dark' : 'light',
        style: { fontSize: '12px', fontFamily: 'inherit' },
        custom: ({ series, seriesIndex, dataPointIndex, w }: any) => {
          const ts = w.globals.seriesX[0]?.[dataPointIndex];
          const media = series[0][dataPointIndex];
          const rangeData = w.config.series[1]?.data?.[dataPointIndex];
          const min = rangeData?.y?.[0];
          const max = rangeData?.y?.[1];
          const tiendas = tiendasPorPeriodo.get(ts) ?? 0;

          const fecha = ts ? this.formatPeriodoFromTs(ts) : '';

          return `
            <div class="ph-apex-tooltip">
              <p class="ph-apex-tooltip-fecha">${fecha}</p>
              <div class="ph-apex-tooltip-row">
                <span class="ph-apex-dot min"></span>
                <span>Mín</span>
                <span class="ph-apex-tooltip-val">${this.formatPrecio(min ?? null)}</span>
              </div>
              <div class="ph-apex-tooltip-row">
                <span class="ph-apex-dot media"></span>
                <span>Media</span>
                <span class="ph-apex-tooltip-val">${this.formatPrecio(media ?? null)}</span>
              </div>
              <div class="ph-apex-tooltip-row">
                <span class="ph-apex-dot max"></span>
                <span>Máx</span>
                <span class="ph-apex-tooltip-val">${this.formatPrecio(max ?? null)}</span>
              </div>
              <div class="ph-apex-tooltip-row ph-apex-tooltip-tiendas">
                <span>${tiendas} tienda${tiendas !== 1 ? 's' : ''}</span>
              </div>
            </div>
          `;
        },
      },
    };

    // Importar ApexCharts dinámicamente
    import('apexcharts').then(({ default: ApexCharts }) => {
      this.ngZone.run(() => {
        const elFresh = document.getElementById('ph-apex-chart');
        if (!elFresh) return;
        this.apexChart = new ApexCharts(elFresh, options);
        this.apexChart.render();
      });
    }).catch(err => {
      console.error('ApexCharts no disponible:', err);
    });
  }

  // ── Gráfico ───────────────────────────────────────────────────────────────

  private calcularPuntos(puntos: PuntoHistorial[]): PuntoGrafico[] {
    if (puntos.length === 0) return [];
    const precios   = puntos.flatMap(p => [p.min, p.max]);
    const globalMin = Math.min(...precios);
    const globalMax = Math.max(...precios);
    const rango     = globalMax - globalMin || 1;
    return puntos.map((p, i) => ({
      x:      (i / Math.max(puntos.length - 1, 1)) * 100,
      yMin:   100 - ((p.min   - globalMin) / rango * 80 + 10),
      yMax:   100 - ((p.max   - globalMin) / rango * 80 + 10),
      yMedia: 100 - ((p.media - globalMin) / rango * 80 + 10),
      punto:  p,
    }));
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

  private formatPeriodoFromTs(ts: number): string {
    const d = new Date(ts);
    const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    return `${meses[d.getUTCMonth()]} ${d.getUTCFullYear()}`;
  }

  tendencia(): 'baja' | 'sube' | 'estable' {
    const pts = this.puntosPreview();
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