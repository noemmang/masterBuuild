import {
  Component, OnInit, OnDestroy, AfterViewInit,
  signal, ViewChild, ElementRef, NgZone
} from '@angular/core';
import { RouterLink } from '@angular/router';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';                          // ← AÑADIDO
import { ComponenteService, Componente } from '../../core/services/componente.service';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [RouterLink, FormsModule],                                  // ← FormsModule AÑADIDO
  templateUrl: './home.component.html',
  styleUrl: './home.component.scss'
})
export class HomeComponent implements OnInit, AfterViewInit, OnDestroy {

  @ViewChild('carruselDestacados') carruselDestacadosRef!: ElementRef<HTMLElement>;
  @ViewChild('carruselOfertas')    carruselOfertasRef!: ElementRef<HTMLElement>;

  constructor(
    private componenteService: ComponenteService,
    private router: Router,
    private ngZone: NgZone,
  ) {}

  // ── Datos estáticos ──────────────────────────────────────

  stats = [
    { valor: '12.000+', label: 'Componentes' },
    { valor: '45+',     label: 'Tiendas' },
    { valor: '1M+',     label: 'Precios rastreados' },
    { valor: '24h',     label: 'Actualización' },
  ];

  categorias = [
    { nombre: 'CPU',            slug: 'cpu' },
    { nombre: 'GPU',            slug: 'gpu' },
    { nombre: 'RAM',            slug: 'ram' },
    { nombre: 'Placa Base',     slug: 'placa_base' },
    { nombre: 'Almacenamiento', slug: 'almacenamiento' },
    { nombre: 'PSU',            slug: 'psu' },
    { nombre: 'Gabinete',       slug: 'gabinete' },
    { nombre: 'Refrigeración',  slug: 'refrigeracion_aire' },
  ];

  features = [
    {
      titulo: 'Comparador de precios',
      desc: 'Encuentra el precio más bajo entre decenas de tiendas en tiempo real.',
      icono: 'M3 3h18v18H3zM9 3v18M15 3v18M3 9h18M3 15h18'
    },
    {
      titulo: 'Alertas de precio',
      desc: 'Recibe un email cuando un componente baje del precio que tú decides.',
      icono: 'M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0'
    },
    {
      titulo: 'Historial de precios',
      desc: 'Analiza la evolución del precio en los últimos meses antes de comprar.',
      icono: 'M3 3v18h18M7 16l4-4 4 4 4-8'
    },
    {
      titulo: 'Configurador PC',
      desc: 'Arma tu build con validación de compatibilidad entre componentes.',
      icono: 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zM12 8v4l3 3'
    },
    {
      titulo: 'Comparador 3D',
      desc: 'Compara dimensiones de gabinetes visualmente en escala real.',
      icono: 'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z'
    },
    {
      titulo: 'Componentes guardados',
      desc: 'Crea tu lista de deseos y accede a ella desde cualquier dispositivo.',
      icono: 'M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z'
    },
  ];

  ctaSlots = [
    { label: 'CPU',            filled: true  },
    { label: 'Placa Base',     filled: true  },
    { label: 'GPU',            filled: true  },
    { label: 'RAM',            filled: false },
    { label: 'Almacenamiento', filled: false },
    { label: 'PSU',            filled: false },
    { label: 'Gabinete',       filled: false },
    { label: 'Refrigeración',  filled: false },
  ];

  tiendas = [
    { nombre: 'PcComponentes',  logo: '/logo-tiendas/PcComponentes.png',           url: 'https://www.pccomponentes.com' },
    { nombre: 'Coolmod',        logo: '/logo-tiendas/logo_coolmod.png',            url: 'https://www.coolmod.com' },
    { nombre: 'Media Markt',    logo: '/logo-tiendas/Media_Markt_logo.svg.png',    url: 'https://www.mediamarkt.es' },
    { nombre: 'Neobyte',        logo: '/logo-tiendas/neobyte_computers.png',       url: 'https://www.neobyte.es' },
    { nombre: 'PC Box',         logo: '/logo-tiendas/PC-BOX-logo.webp',            url: 'https://www.pcbox.com' }, 
    { nombre: 'Aussar',         logo: '/logo-tiendas/aussar-LOGO_1.jpg',           url: 'https://www.aussar.es' },
    { nombre: 'WORTEN',         logo: '/logo-tiendas/Worten_logo.png',             url: 'https://www.worten.es' },
    { nombre: 'CaseKing',       logo: '/logo-tiendas/caseking_gmbh.png',           url: 'https://www.caseking.es' }, 
    { nombre: 'Red Computer',   logo: '/logo-tiendas/red_computer.png',            url: 'https://www.redcomputer.es' },
    { nombre: 'Alternate',      logo: '/logo-tiendas/alternate_de_color.png',      url: 'https://www.alternate.es' }, 
    { nombre: 'Info Computer',      logo: '/logo-tiendas/infocmputerlogo.png',     url: 'https://www.info-computer.com' }, 
    { nombre: 'Life Informática',   logo: '/logo-tiendas/life-logo-blue.jpg',      url: 'https://lifeinformatica.com' },
    { nombre: 'FNAC',               logo: '/logo-tiendas/fnaclogo1-2.png',         url: 'https://www.fnac.es' },
    { nombre: 'APP Informática',    logo: '/logo-tiendas/logo_app___48c5b83060a390d4c4dcda4cbcf53f36.webp',             url: 'https://www.appinformatica.com' },
  ];

  marcas = [
    'Intel', 'AMD', 'NVIDIA', 'ASUS', 'MSI', 'Gigabyte', 'Corsair',
    'Kingston', 'Samsung', 'Western Digital', 'Seagate', 'be quiet!',
    'Noctua', 'Seasonic', 'NZXT', 'Fractal Design', 'DeepCool',
  ];

  // ── Búsqueda desde el home ───────────────────────────────  ← AÑADIDO

  /** Texto ligado al input de búsqueda principal del home */
  busquedaHome = '';

  // ── Señales de datos dinámicos ───────────────────────────

  destacados         = signal<Componente[]>([]);
  cargandoDestacados = signal(true);

  ofertas           = signal<Componente[]>([]);
  cargandoOfertas   = signal(true);

  // ── Carrusel infinito ────────────────────────────────────

  readonly CARD_W        = 220;
  readonly HALF_CARD     = (220 + 16) / 2;
  readonly CARD_GAP      = 16;
  readonly STEP_MS       = 6000;
  readonly TRANSITION_MS = 700;

  paginaDestacados = signal(0);
  paginaOfertas    = signal(0);

  private idxDestacados = 0;
  private idxOfertas    = 0;

  private timerDestacados?: ReturnType<typeof setInterval>;
  private timerOfertas?:    ReturnType<typeof setInterval>;

  // ── Lifecycle ────────────────────────────────────────────

  ngOnInit(): void {
    this.cargarDestacados();
    this.cargarOfertas();
  }

  ngAfterViewInit(): void {}

  ngOnDestroy(): void {
    clearInterval(this.timerDestacados);
    clearInterval(this.timerOfertas);
  }

  // ── Carga ────────────────────────────────────────────────

  private cargarDestacados(): void {
    this.componenteService.buscar({ page: 1, orden: '' }).subscribe({
      next: (res) => {
        this.destacados.set(res.data.slice(0, 12));
        this.cargandoDestacados.set(false);
        setTimeout(() => this.iniciarAutoplay('destacados'), 200);
      },
      error: () => this.cargandoDestacados.set(false),
    });
  }

  private cargarOfertas(): void {
    this.componenteService.buscar({ page: 1, orden: 'precio_asc' }).subscribe({
      next: (res) => {
        const conBajada = res.data.filter(c => c.bajada_precio);
        this.ofertas.set(conBajada.length >= 4 ? conBajada.slice(0, 10) : res.data.slice(0, 10));
        this.cargandoOfertas.set(false);
        setTimeout(() => this.iniciarAutoplay('ofertas'), 200);
      },
      error: () => this.cargandoOfertas.set(false),
    });
  }

  // ── Autoplay infinito ─────────────────────────────────────

  private iniciarAutoplay(which: 'destacados' | 'ofertas'): void {
    const isD    = which === 'destacados';
    const getEl  = () => isD
      ? this.carruselDestacadosRef?.nativeElement
      : this.carruselOfertasRef?.nativeElement;
    const total  = () => isD ? this.destacados().length : this.ofertas().length;
    const getIdx = () => isD ? this.idxDestacados : this.idxOfertas;
    const setIdx = (v: number) => { if (isD) this.idxDestacados = v; else this.idxOfertas = v; };
    const setPag = (v: number) => isD
      ? this.paginaDestacados.set(v)
      : this.paginaOfertas.set(v);

    if (isD) clearInterval(this.timerDestacados);
    else     clearInterval(this.timerOfertas);

    this.ngZone.runOutsideAngular(() => {
      const timer = setInterval(() => {
        const el = getEl();
        if (!el) return;

        const next    = getIdx() + 1;
        const cardPx  = this.CARD_W + this.CARD_GAP;
        const origLen = total();

        el.style.transition = `transform ${this.TRANSITION_MS}ms cubic-bezier(0.4, 0, 0.2, 1)`;
        el.style.transform  = `translateX(-${this.HALF_CARD + next * cardPx}px)`;
        setIdx(next);

        this.ngZone.run(() => setPag(next % origLen));

        if (next >= origLen) {
          setTimeout(() => {
            const el2 = getEl();
            if (!el2) return;
            el2.style.transition = 'none';
            el2.style.transform  = `translateX(-${this.HALF_CARD}px)`;
            setIdx(0);
            this.ngZone.run(() => setPag(0));
          }, this.TRANSITION_MS + 20);
        }
      }, this.STEP_MS);

      if (isD) this.timerDestacados = timer;
      else     this.timerOfertas    = timer;
    });
  }

  // ── Dots / navegación manual ─────────────────────────────

  getDestacadosDots(): number[] { return Array.from({ length: this.destacados().length }); }
  getOfertasDots():    number[] { return Array.from({ length: this.ofertas().length }); }

  irAPaginaDestacados(pagina: number): void { this.saltarA('destacados', pagina); }
  irAPaginaOfertas(pagina: number):    void { this.saltarA('ofertas', pagina); }

  private saltarA(which: 'destacados' | 'ofertas', pagina: number): void {
    const isD = which === 'destacados';
    const el  = isD
      ? this.carruselDestacadosRef?.nativeElement
      : this.carruselOfertasRef?.nativeElement;
    if (!el) return;

    const cardPx = this.CARD_W + this.CARD_GAP;
    el.style.transition = `transform ${this.TRANSITION_MS}ms cubic-bezier(0.4, 0, 0.2, 1)`;
    el.style.transform  = `translateX(-${this.HALF_CARD + pagina * cardPx}px)`;

    if (isD) { this.idxDestacados = pagina; this.paginaDestacados.set(pagina); }
    else     { this.idxOfertas    = pagina; this.paginaOfertas.set(pagina);    }

    clearInterval(isD ? this.timerDestacados : this.timerOfertas);
    this.iniciarAutoplay(which);
  }

  // ── Navegación ───────────────────────────────────────────

  /** Navega al buscador filtrando por categoría (botones de categoría) */
  irABuscar(categoria: string): void {
    this.router.navigate(['/buscar'], { queryParams: { categoria } });
  }

  /** NUEVO — Input de búsqueda del home → pasa ?q= al buscador */
  irABuscarTexto(): void {
    const q = this.busquedaHome.trim();
    if (!q) return;
    this.router.navigate(['/buscar'], { queryParams: { q } });
  }

  /** NUEVO — Cards de los carruseles → selecciona el componente directamente */
  irAComponente(comp: Componente): void {
    this.router.navigate(['/buscar'], {
      queryParams: { uuid: comp.uuid, categoria: comp.categoria }
    });
  }

  // ── Helpers ──────────────────────────────────────────────

  formatPrecio(precio: number | null): string {
    if (!precio) return 'Sin precio';
    return precio.toLocaleString('es-ES', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });
  }
}