import {
  Component, OnInit, OnDestroy, signal, computed, inject,
  ViewChild, ElementRef, NgZone,
} from '@angular/core';
import { catchError, of } from 'rxjs';
import {
  ComponenteService,
  HistorialCategorias,
  PeriodoHistorial,
} from '../../../core/services/componente.service';
import { PERIODOS } from '../price-history/price-history.component';

// ─── Categorías: etiqueta y color fijos ───────────────────────────────────────
// El color va ligado a la categoría (no a la posición) para que no cambie al
// activar/desactivar otras.

interface CategoriaInfo { slug: string; label: string; color: string; }

const CATEGORIAS: CategoriaInfo[] = [
  { slug: 'cpu',                   label: 'CPU',             color: '#6366f1' },
  { slug: 'gpu',                   label: 'GPU',             color: '#22c55e' },
  { slug: 'ram',                   label: 'RAM',             color: '#f59e0b' },
  { slug: 'placa_base',            label: 'Placa base',      color: '#ef4444' },
  { slug: 'almacenamiento',        label: 'Almacenamiento',  color: '#06b6d4' },
  { slug: 'psu',                   label: 'PSU',             color: '#a855f7' },
  { slug: 'gabinete',              label: 'Gabinete',        color: '#ec4899' },
  { slug: 'refrigeracion_aire',    label: 'Refrig. aire',    color: '#84cc16' },
  { slug: 'refrigeracion_liquida', label: 'Refrig. líquida', color: '#f97316' },
  { slug: 'ventilador',            label: 'Ventilador',      color: '#64748b' },
];

type Modo = 'precio' | 'variacion';

interface FilaResumen {
  slug: string;
  label: string;
  color: string;
  actual: number | null;
  variacion: number | null;   // % entre el primer y el último punto del período
  componentes: number;        // componentes que forman el último punto
}

@Component({
  selector: 'app-price-history-categorias',
  standalone: true,
  templateUrl: './price-history-categorias.component.html',
  styleUrl: './price-history-categorias.component.scss',
})
export class PriceHistoryCategoriasComponent implements OnInit, OnDestroy {

  readonly PERIODOS   = PERIODOS;
  readonly CATEGORIAS = CATEGORIAS;

  @ViewChild('chartEl') chartEl?: ElementRef<HTMLElement>;

  // ── Estado ────────────────────────────────────────────────────────────────
  datos         = signal<HistorialCategorias | null>(null);
  cargando      = signal(true);
  error         = signal(false);
  periodoActivo = signal<PeriodoHistorial>('1y');
  modo          = signal<Modo>('precio');

  /** Categorías elegidas. Vacío = todas ("Todos"). Por defecto, las cuatro
   *  que más se miran y que tienen precios en un rango comparable. */
  seleccion = signal<string[]>(['cpu', 'gpu', 'ram', 'almacenamiento']);
  private primeraCarga = true;

  private svc    = inject(ComponenteService);
  private ngZone = inject(NgZone);
  private apexChart: any = null;
  private renderToken = 0;

  /** Series que se pintan: las elegidas (o todas) que además tienen datos. */
  seriesVisibles = computed(() => {
    const d = this.datos();
    if (!d) return [];
    const sel = this.seleccion();
    return d.series.filter(s => s.puntos.length > 0 && (sel.length === 0 || sel.includes(s.categoria)));
  });

  hayDatos = computed(() => this.seriesVisibles().length > 0);

  /** Categorías que tienen datos en el período (para desactivar chips vacíos). */
  categoriasConDatos = computed(() =>
    new Set((this.datos()?.series ?? []).filter(s => s.puntos.length > 0).map(s => s.categoria)),
  );

  resumen = computed<FilaResumen[]>(() =>
    this.seriesVisibles().map(s => {
      const info  = this.info(s.categoria);
      const first = s.puntos[0];
      const last  = s.puntos[s.puntos.length - 1];
      return {
        slug: s.categoria,
        label: info.label,
        color: info.color,
        actual: last.media,
        variacion: s.puntos.length > 1 && first.media > 0
          ? ((last.media - first.media) / first.media) * 100
          : null,
        componentes: last.componentes,
      };
    }),
  );

  // ── Lifecycle ─────────────────────────────────────────────────────────────

  ngOnInit() { this.cargar(); }

  ngOnDestroy() { this.destroyChart(); }

  // ── Datos ─────────────────────────────────────────────────────────────────

  private cargar() {
    this.cargando.set(true);
    this.error.set(false);

    // Se piden siempre todas las categorías y se filtra en cliente: activar o
    // desactivar chips es instantáneo y solo hay una petición por período.
    this.svc.getHistorialCategorias(this.periodoActivo())
      .pipe(catchError(() => of(null)))
      .subscribe(res => {
        this.datos.set(res);
        this.error.set(res === null);

        // Si la selección por defecto no tiene datos todavía, mejor enseñar
        // todo que un gráfico vacío.
        if (this.primeraCarga && res) {
          const sel = this.seleccion();
          const hay = res.series.some(x => x.puntos.length > 0 && sel.includes(x.categoria));
          if (!hay) this.seleccion.set([]);
        }
        this.primeraCarga = false;
        this.cargando.set(false);
        setTimeout(() => this.renderChart(), 0);
      });
  }

  setPeriodo(p: PeriodoHistorial) {
    if (p === this.periodoActivo()) return;
    this.periodoActivo.set(p);
    this.cargar();
  }

  setModo(m: Modo) {
    if (m === this.modo()) return;
    this.modo.set(m);
    this.renderChart();
  }

  seleccionarTodos() {
    this.seleccion.set([]);
    this.renderChart();
  }

  toggleCategoria(slug: string) {
    const actual = this.seleccion();
    let nueva = actual.includes(slug) ? actual.filter(c => c !== slug) : [...actual, slug];
    // Si no queda ninguna, o están todas, equivale a "Todos".
    if (nueva.length === 0 || nueva.length === CATEGORIAS.length) nueva = [];
    this.seleccion.set(nueva);
    this.renderChart();
  }

  chipActivo(slug: string): boolean {
    const sel = this.seleccion();
    return sel.length > 0 && sel.includes(slug);
  }

  // ── ApexCharts ────────────────────────────────────────────────────────────

  private destroyChart() {
    if (this.apexChart) {
      this.apexChart.destroy();
      this.apexChart = null;
    }
  }

  private css(name: string, fallback: string): string {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim() || fallback;
  }

  private toTimestamp(periodo: string): number {
    const [y, m] = periodo.split('-').map(Number);
    return Date.UTC(y, m - 1, 1);
  }

  private renderChart() {
    const series = this.seriesVisibles();
    if (series.length === 0) { this.destroyChart(); return; }

    const el = this.chartEl?.nativeElement;
    if (!el) return;

    const token = ++this.renderToken;
    const porcentaje = this.modo() === 'variacion';

    // Eje X común: todos los meses presentes en alguna serie. Así cada serie
    // tiene un valor (o null) por mes y el tooltip compartido cuadra.
    const meses = Array.from(new Set(series.flatMap(s => s.puntos.map(p => p.periodo)))).sort();
    const xs    = meses.map(m => this.toTimestamp(m));

    const apexSeries = series.map(s => {
      const porMes = new Map(s.puntos.map(p => [p.periodo, p]));
      const base   = s.puntos[0].media;
      return {
        name:  this.info(s.categoria).label,
        color: this.info(s.categoria).color,
        data:  meses.map((m, i) => {
          const p = porMes.get(m);
          if (!p) return { x: xs[i], y: null as number | null };
          return { x: xs[i], y: porcentaje ? ((p.media - base) / base) * 100 : p.media };
        }),
      };
    });

    // Para el tooltip: euros reales y nº de componentes por serie y mes.
    const detalle = series.map(s => new Map(s.puntos.map(p => [p.periodo, p])));

    const textMuted   = this.css('--text-muted', '#94a3b8');
    const borderColor = this.css('--border', '#e2e8f0');
    const surface     = this.css('--surface', '#ffffff');
    const isDark = document.documentElement.classList.contains('dark') ||
      surface.startsWith('#1') || surface.startsWith('#0');

    const fmtY = (v: number) => porcentaje ? this.formatPct(v, 0) : this.formatPrecio(v, 0);

    const options: ApexCharts.ApexOptions = {
      series: apexSeries,
      chart: {
        type: 'line',
        height: '100%',
        background: 'transparent',
        toolbar: { show: false },
        zoom: { enabled: false },
        animations: { enabled: true, speed: 350, animateGradually: { enabled: false } },
        fontFamily: 'inherit',
      },
      stroke: { curve: 'straight', width: 2, lineCap: 'round' },
      markers: { size: series.length > 4 ? 0 : 3, strokeWidth: 0, hover: { size: 5 } },
      xaxis: {
        type: 'datetime',
        labels: {
          style: { colors: textMuted, fontSize: '11px', fontFamily: 'inherit' },
          datetimeFormatter: { year: 'yyyy', month: "MMM 'yy", day: 'dd MMM' },
          datetimeUTC: true,
        },
        axisBorder: { show: false },
        axisTicks: { show: false },
        crosshairs: { show: true, stroke: { color: borderColor, width: 1, dashArray: 3 } },
        tooltip: { enabled: false },
      },
      yaxis: {
        labels: {
          style: { colors: textMuted, fontSize: '11px', fontFamily: 'inherit' },
          formatter: fmtY,
          offsetX: -4,
        },
        tickAmount: 5,
        forceNiceScale: true,
      },
      grid: {
        borderColor,
        strokeDashArray: 0,
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } },
        padding: { top: 0, right: 16, bottom: 0, left: 0 },
      },
      annotations: porcentaje
        ? { yaxis: [{ y: 0, borderColor, strokeDashArray: 4, borderWidth: 1 }] }
        : {},
      legend: { show: false },
      tooltip: {
        shared: true,
        intersect: false,
        theme: isDark ? 'dark' : 'light',
        custom: ({ dataPointIndex }: any) => {
          const ts   = xs[dataPointIndex];
          const mes  = meses[dataPointIndex];
          const rows = series
            .map((s, i) => ({ s, p: detalle[i].get(mes), color: this.info(s.categoria).color, label: this.info(s.categoria).label }))
            .filter(r => r.p)
            .sort((a, b) => b.p!.media - a.p!.media)
            .map(r => `
              <div class="phc-tt-row">
                <span class="phc-tt-dot" style="background:${r.color}"></span>
                <span>${r.label}</span>
                <span class="phc-tt-val">${this.formatPrecio(r.p!.media)}</span>
                <span class="phc-tt-n">${r.p!.componentes}</span>
              </div>`)
            .join('');
          return `
            <div class="phc-tt">
              <p class="phc-tt-fecha">${this.formatPeriodoFromTs(ts)}</p>
              ${rows}
              <p class="phc-tt-foot">Media por componente · nº de componentes</p>
            </div>`;
        },
      },
    };

    import('apexcharts').then(({ default: ApexCharts }) => {
      // Si mientras se importaba hubo otro render más reciente, este sobra.
      if (token !== this.renderToken) return;
      this.ngZone.run(() => {
        const target = this.chartEl?.nativeElement;
        if (!target) return;
        this.destroyChart();
        this.apexChart = new ApexCharts(target, options);
        this.apexChart.render();
      });
    }).catch(err => console.error('ApexCharts no disponible:', err));
  }

  // ── Helpers ───────────────────────────────────────────────────────────────

  info(slug: string): CategoriaInfo {
    return CATEGORIAS.find(c => c.slug === slug)
      ?? { slug, label: slug, color: '#64748b' };
  }

  formatPrecio(v: number | null, decimales = 2): string {
    if (v === null || v === undefined) return '—';
    return v.toLocaleString('es-ES', {
      style: 'currency', currency: 'EUR',
      minimumFractionDigits: decimales, maximumFractionDigits: decimales,
    });
  }

  formatPct(v: number | null, decimales = 1): string {
    if (v === null || v === undefined) return '—';
    const signo = v > 0 ? '+' : '';
    return `${signo}${v.toLocaleString('es-ES', { minimumFractionDigits: decimales, maximumFractionDigits: decimales })}%`;
  }

  private formatPeriodoFromTs(ts: number): string {
    const d = new Date(ts);
    const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    return `${meses[d.getUTCMonth()]} ${d.getUTCFullYear()}`;
  }
}
