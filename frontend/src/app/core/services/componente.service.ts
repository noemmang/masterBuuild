import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface Componente {
  uuid: string;
  nombre: string;
  categoria: string;
  imagen_url: string | null;
  marca: { nombre: string } | null;
  precio_min: number | null;
  precio_max: number | null;
  num_tiendas: number;
  tiene_cupon: boolean;
  tiene_regalo: boolean;
  bajada_precio: boolean;
  en_stock: boolean;
  descripcion?: string | null;
  /** Specs resumidas de la categoría (ver ComponenteListadoResource en el
   *  backend). Antes el listado no traía ninguna especificación técnica:
   *  una tarjeta de gabinete no dejaba ver si era ITX o ATX sin abrir su
   *  ficha aparte, y esto es justo lo que hacía falta para poder mostrar
   *  ese dato directamente en la rejilla de resultados. */
  specs?: EspecsListado | null;
}

/** Una unión discriminada sería más precisa (una por "categoria"), pero
 *  aquí basta con lo que de verdad se lee en las tarjetas — todos los
 *  campos son opcionales porque cada categoría solo rellena los suyos. */
export interface EspecsListado {
  // cpu
  socket?: string | null;
  arquitectura?: string | null;
  tipo_memoria?: string | null;
  nucleos?: number;
  hilos?: number;
  frecuencia_base_ghz?: number;
  frecuencia_boost_ghz?: number | null;
  tdp_watts?: number;
  grafica_integrada?: boolean;
  // gpu
  vram_gb?: number;
  tipo_vram?: string | null;
  version_pcie?: string | null;
  longitud_mm?: number;
  psu_minima_watts?: number;
  ray_tracing?: boolean;
  // ram
  capacidad_total_gb?: number;
  modulos?: number;
  velocidad_mhz?: number;
  latencia_cas?: string;
  tiene_rgb?: boolean;
  // placa_base
  chipset?: string | null;
  factor_forma?: string | null;
  factor_forma_id?: number;
  slots_memoria?: number;
  slots_m2?: number;
  wifi?: boolean;
  // almacenamiento
  tipo?: string;
  interfaz?: string | null;
  capacidad_gb?: number;
  velocidad_lectura_mbs?: number | null;
  // psu
  vatios?: number;
  certificacion?: string | null;
  tipo_psu?: string | null;
  tipo_psu_id?: number;
  modular?: string;
  largo_mm?: number | null;
  // gabinete
  tipo_gabinete?: string | null;
  estructura?: string | null;
  factores_forma?: string[];
  tipos_psu?: string[];
  longitud_gpu_max_mm?: number | null;
  altura_cooler_max_mm?: number | null;
  largo_psu_max_mm?: number | null;
  soporte_radiadores?: number[] | null;
  ancho_mm?: number;
  alto_mm?: number;
  profundidad_mm?: number;
  // refrigeracion_aire / refrigeracion_liquida
  tipo_refrigeracion?: string | null;
  altura_mm?: number | null;
  tam_radiador_mm?: number;
  sockets_compatibles?: string[];
  // ventilador
  tam_mm?: number | null;
}

export interface PaginatedResponse {
  data: Componente[];
  current_page: number;
  last_page: number;
  total: number;
}

/**
 * Forma de la respuesta de GET /componentes/{uuid}, espejo exacto de
 * ComponenteDetalleResource en el backend. Los nombres de campo de aquí
 * abajo importan de verdad: antes esta interfaz tenía nombres inventados
 * (frecuencia_base_mhz, tdp_w, kit_modulos, perfil_xmp, tiene_wifi...)
 * que NUNCA coincidieron con lo que el backend mandaba en realidad
 * (frecuencia_base_ghz, tdp_watts, modulos, xmp, wifi...). TypeScript no
 * pilla ese desfase porque la respuesta HTTP llega como `any`: cada
 * lectura de esos campos devolvía `undefined` en silencio y ninguna
 * comprobación de tipos lo avisaba. spec-compare.component.ts es el que
 * más sufría esto — ver su cabecera para más detalle.
 */
export interface ComponenteDetalle {
  uuid: string;
  nombre: string;
  categoria: string;
  modelo: string | null;
  imagen_url: string | null;
  descripcion: string | null;
  marca: { nombre: string } | null;
  fabricante: { nombre: string } | null;
  precios: EntradaPrecioDetalle[];
  precio_min: number | null;
  specs: SpecsCpu | SpecsGpu | SpecsRam | SpecsPlacaBase | SpecsAlmacenamiento
       | SpecsPsu | SpecsGabinete | SpecsRefrigeracionAire | SpecsRefrigeracionLiquida
       | SpecsVentilador | null;
}

export interface EntradaPrecioDetalle {
  tienda: string | null;
  precio: number;
  moneda: string;
  url: string | null;
  en_stock: boolean;
  vigente_desde: string | null;
}

export interface SpecsCpu {
  socket: string | null; arquitectura: string | null; tipo_memoria: string | null;
  nucleos: number; hilos: number;
  frecuencia_base_ghz: number; frecuencia_boost_ghz: number | null;
  tdp_watts: number; tdp_max_watts: number | null;
  frecuencia_memoria_max_mhz: number; memoria_max_gb: number;
  grafica_integrada: boolean; nombre_grafica_integrada: string | null;
  proceso_nm: number | null; incluye_cooler: boolean; overclock: boolean;
}

export interface SpecsGpu {
  arquitectura: string | null; tipo_vram: string | null; version_pcie: string | null;
  vram_gb: number; bus_bits: number;
  frecuencia_base_mhz: number; frecuencia_boost_mhz: number | null;
  tdp_watts: number; slots_pcie: number; longitud_mm: number;
  conectores_alimentacion: string[] | null; psu_minima_watts: number;
  salidas_video: string[] | null; ray_tracing: boolean; dlss: boolean; fsr: boolean;
}

export interface SpecsRam {
  tipo_memoria: string | null; capacidad_gb: number; modulos: number; capacidad_total_gb: number;
  velocidad_mhz: number; latencia_cas: string; voltaje: number; factor_forma: string;
  altura_mm: number | null; tiene_rgb: boolean; ecc: boolean; xmp: boolean; expo: boolean;
}

export interface SpecsPlacaBase {
  socket: string | null; chipset: string | null; factor_forma: string | null; factor_forma_id: number;
  tipo_memoria: string | null; version_pcie: string | null;
  slots_memoria: number; memoria_max_gb: number; frecuencia_memoria_max_mhz: number;
  slots_pcie_x16: number; slots_pcie_x4: number; slots_pcie_x1: number; slots_m2: number;
  puertos_sata: number; puertos_usb_traseros: string[] | null;
  conector_atx: string; conector_cpu: string;
  wifi: boolean; bluetooth: boolean; thunderbolt: boolean;
  audio_chipset: string | null; lan_chipset: string | null; lan_velocidad_gbps: number;
}

export interface SpecsAlmacenamiento {
  tipo: string; interfaz: string | null; factor_forma: string | null; tipo_nand: string | null;
  capacidad_gb: number; velocidad_lectura_mbs: number | null; velocidad_escritura_mbs: number | null;
  rpm: number | null; cache_mb: number | null; tbw: number | null; cifrado: boolean; dram: boolean;
}

export interface SpecsPsu {
  certificacion: string | null; tipo_psu: string | null; tipo_psu_id: number; vatios: number;
  modular: string; version_atx: string | null;
  conectores_pcie_16pin: number; conectores_pcie_8pin: number;
  conectores_sata: number; conectores_molex: number;
  largo_mm: number | null; ventilador_mm: number | null; ventilador_zero_rpm: boolean;
}

export interface SpecsGabinete {
  tipo_gabinete: string | null; estructura: string | null;
  factores_forma: string[]; tipos_psu: string[];
  longitud_gpu_max_mm: number | null; altura_cooler_max_mm: number | null; largo_psu_max_mm: number | null;
  bahias_35: number | null; bahias_25: number | null;
  ventiladores_frontales: number | null; ventiladores_traseros: number | null; ventiladores_superiores: number | null;
  ventiladores_incluidos: number | null;
  tam_ventilador_frontal_mm: number | null; tam_ventilador_superior_mm: number | null; tam_ventilador_trasero_mm: number | null;
  soporte_radiadores: number[] | null; puertos_usb_frontales: string[] | null;
  montaje_vertical_pcie: boolean; panel_frontal: string | null;
  ancho_mm: number; alto_mm: number; profundidad_mm: number;
}

export interface SpecsRefrigeracionAire {
  tipo_refrigeracion: string | null; sockets_compatibles: string[];
  tdp_max_watts: number; altura_mm: number | null;
  rpm_min: number | null; rpm_max: number | null; ruido_db_min: number | null; ruido_db_max: number | null;
  num_ventiladores: number | null; tam_ventilador_mm: number | null; tiene_rgb: boolean;
}

export interface SpecsRefrigeracionLiquida {
  tipo_refrigeracion: string | null; sockets_compatibles: string[];
  tdp_max_watts: number; tam_radiador_mm: number;
  ancho_radiador_mm: number | null; alto_radiador_mm: number | null; grosor_radiador_mm: number | null;
  num_ventiladores: number; tam_ventilador_mm: number;
  pantalla_cabezal: boolean; flujo_personalizable: boolean; incluye_pasta_termica: boolean; tiene_rgb: boolean;
}

export interface SpecsVentilador {
  tipo: string | null; rpm_min: number | null; rpm_max: number | null;
  ruido_db_min: number | null; ruido_db_max: number | null;
  flujo_aire_cfm: number | null; static_pressure_mmh2o: number | null;
  num_ventiladores: number; tiene_rgb: boolean; pwm: boolean; tam_mm: number | null;
}

export interface EntradaPrecio {
  uuid: string;
  precio: number;
  url: string | null;
  en_stock: boolean;
  tienda: { nombre: string; website: string | null };
  cupon: { codigo: string; descuento: number; tipo: string } | null;
  regalo: Regalo | null;
}

// ── Regalos ───────────────────────────────────────────────────────────────────

export interface Regalo {
  uuid: string;
  nombre: string;
  tipo: string;
  imagen_url: string | null;
  descripcion: string | null;
  valor_estimado: number;
}

// ── Historial de precios ──────────────────────────────────────────────────────

export type PeriodoHistorial = '6m' | '1y' | '2y' | '3y';

export interface PuntoHistorial {
  periodo: string;  // 'YYYY-MM'
  min: number;
  max: number;
  media: number;
  tiendas: number;
}

export interface HistorialPrecios {
  resumen: {
    min: number | null;
    max: number | null;
    media: number | null;
    actual: number | null;
  };
  puntos: PuntoHistorial[];
  tiendas: { uuid: string; nombre: string }[];
}

// ── Visor de gabinete (endpoint ligero para el comparador 3D) ─────────────────

export interface GabineteVisor {
  uuid:                 string;
  nombre:               string;
  ancho_mm:             number | null;
  alto_mm:              number | null;
  profundidad_mm:       number | null;
  longitud_gpu_max_mm:  number | null;
  altura_cooler_max_mm: number | null;
  soporte_radiadores:   number[];
}

// ── Parámetros de búsqueda extendidos ────────────────────────────────────────

export interface BuscarParams {
  categoria?: string;
  q?: string;
  page?: number;
  marca?: string;
  orden?: string;
  precio_min?: number | null;
  precio_max?: number | null;

  /** Si es false, se ocultan los componentes agotados (solo tiendas con stock). Por defecto se incluyen. */
  mostrar_agotados?: boolean;

  // ── Selección actual del configurador ─────────────────────────────
  //
  // Uuid de lo que el usuario ya tiene elegido en cada categoría (los
  // slots vacíos simplemente no se mandan). El backend calcula toda la
  // compatibilidad a partir de esto (ver CompatibilidadService): no hace
  // falta calcular aquí ningún socket_id/factor_forma/potencia_min a
  // mano. Antes existían ~10 campos de filtro sueltos que había que
  // rellenar bien desde el frontend para cada categoría, y ninguno cubría
  // fuente↔gabinete — de ahí que un gabinete Mini-ITX siguiera enseñando
  // fuentes ATX.
  cpu_uuid?: string;
  placa_base_uuid?: string;
  ram_uuid?: string;
  gpu_uuid?: string;
  psu_uuid?: string;
  gabinete_uuid?: string;
  refrigeracion_uuid?: string;
  almacenamiento_uuid?: string;
  ventilador_uuid?: string;
}

// ─────────────────────────────────────────────────────────────────────────────

@Injectable({ providedIn: 'root' })
export class ComponenteService {
  private readonly API = environment.apiUrl;

  constructor(private http: HttpClient) {}

  buscar(params: BuscarParams): Observable<PaginatedResponse> {
    return this.buscarConFiltros(params);
  }

  buscarConFiltros(params: BuscarParams): Observable<PaginatedResponse> {
    let httpParams = new HttpParams();

    const set = (key: string, val: any) => {
      if (val !== undefined && val !== null && val !== '') {
        httpParams = httpParams.set(key, String(val));
      }
    };

    set('categoria',                 params.categoria);
    set('buscar',                    params.q);
    set('page',                      params.page ?? 1);
    set('marca',                     params.marca);
    set('ordenar',                   params.orden);
    set('precio_min',                params.precio_min);
    set('precio_max',                params.precio_max);
    set('mostrar_agotados',          params.mostrar_agotados);

    // Selección actual, para que el backend calcule la compatibilidad
    set('cpu_uuid',                  params.cpu_uuid);
    set('placa_base_uuid',           params.placa_base_uuid);
    set('ram_uuid',                  params.ram_uuid);
    set('gpu_uuid',                  params.gpu_uuid);
    set('psu_uuid',                  params.psu_uuid);
    set('gabinete_uuid',             params.gabinete_uuid);
    set('refrigeracion_uuid',        params.refrigeracion_uuid);
    set('almacenamiento_uuid',       params.almacenamiento_uuid);
    set('ventilador_uuid',           params.ventilador_uuid);

    return this.http.get<any>(`${this.API}/componentes`, { params: httpParams }).pipe(
      map(res => ({
        ...res,
        data: res.data.map((c: any) => this.mapearComponente(c)),
      }))
    );
  }

  getPrecios(uuid: string): Observable<any> {
    return this.http.get(`${this.API}/componentes/${uuid}/precios`);
  }

  /**
   * GET /componentes/{uuid}. La respuesta ya viene en el shape exacto de
   * ComponenteDetalleResource (ver backend) — a diferencia del listado, no
   * hace falta pasar por mapearComponente() porque el backend ya no manda
   * el modelo Eloquent en crudo (con relaciones sin transformar y decimales
   * como string); cada campo llega tal cual se define en ComponenteDetalle.
   */
  getDetalle(uuid: string): Observable<ComponenteDetalle> {
    return this.http.get<ComponenteDetalle>(`${this.API}/componentes/${uuid}`);
  }

  getGabineteVisor(uuid: string): Observable<GabineteVisor> {
    return this.http.get<GabineteVisor>(`${this.API}/componentes/${uuid}/gabinete/visor`);
  }

  getHistorial(
    uuid: string,
    periodo: PeriodoHistorial = '1y',
    tiendaUuid?: string,
  ): Observable<HistorialPrecios> {
    let p = new HttpParams().set('periodo', periodo);
    if (tiendaUuid) p = p.set('tienda', tiendaUuid);
    return this.http.get<HistorialPrecios>(
      `${this.API}/componentes/${uuid}/precios/historial`,
      { params: p },
    );
  }

  /**
   * El listado (GET /componentes) siempre viene con los agregados ya
   * calculados en el backend (precio_min, precio_max, num_tiendas,
   * en_stock) vía withMin/withMax/withCount/withExists — es el único sitio
   * que llama a este método. GET /componentes/{uuid} (getDetalle) ya no
   * pasa por aquí: desde que devuelve ComponenteDetalleResource tiene su
   * propio shape plano (precios[], precio_min) que se usa tal cual, así
   * que la rama que antes recalculaba estos mismos campos a mano a partir
   * de precios_actuales/cupones_activos/regalos_activos (el shape antiguo,
   * sin pasar por un Resource) ya no hacía falta y se ha quitado.
   */
  private mapearComponente(c: any): Componente {
    return {
      uuid:          c.uuid,
      nombre:        c.nombre,
      categoria:     c.categoria,
      imagen_url:    c.imagen_url,
      marca:         c.marca ?? null,
      precio_min:    c.precio_min ?? null,
      precio_max:    c.precio_max ?? null,
      num_tiendas:   c.num_tiendas ?? 0,
      tiene_cupon:   !!c.tiene_cupon,
      tiene_regalo:  !!c.tiene_regalo,
      en_stock:      !!c.en_stock,
      bajada_precio: false,
      descripcion:   c.descripcion ?? null,
      specs:         c.specs ?? null,
    };
  }
}