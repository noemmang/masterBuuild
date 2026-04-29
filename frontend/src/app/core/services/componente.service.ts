import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, map } from 'rxjs';

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
  bajada_precio: boolean;
}

export interface PaginatedResponse {
  data: Componente[];
  current_page: number;
  last_page: number;
  total: number;
}

export interface ComponenteDetalle extends Componente {
  descripcion: string | null;
  cpu?: {
    nucleos: number; hilos: number;
    frecuencia_base_mhz: number; frecuencia_boost_mhz: number;
    velocidad_memoria_max_mhz: number; canales_memoria: number;
    tdp_w: number; litografia_nm: number; graficos_integrados: boolean;
    socket: { nombre: string }; arquitectura: { nombre: string }; tipo_memoria: { nombre: string };
  };
  gpu?: {
    vram_gb: number; bus_bits: number;
    frecuencia_base_mhz: number; frecuencia_boost_mhz: number;
    tdp_w: number; longitud_mm: number; slots_pcie: number; conectores_alimentacion: string;
    arquitectura: { nombre: string }; tipo_vram: { nombre: string }; version_pcie: { nombre: string };
  };
  ram?: {
    capacidad_gb: number; frecuencia_mhz: number; latencia_cl: number;
    voltaje: number; kit_modulos: number; perfil_xmp: boolean; perfil_expo: boolean;
    tipo_memoria: { nombre: string };
  };
  placa_base?: {
    slots_ram: number; velocidad_ram_max_mhz: number; slots_m2: number;
    puertos_sata: number; slots_pcie_x16: number; tiene_wifi: boolean; tiene_bluetooth: boolean;
    socket: { nombre: string }; chipset: { nombre: string }; factor_forma: { nombre: string };
    tipo_memoria: { nombre: string }; version_pcie: { nombre: string };
  };
  almacenamiento?: {
    capacidad_gb: number; velocidad_lectura_mbs: number; velocidad_escritura_mbs: number;
    interfaz: { nombre: string }; factor_forma: { nombre: string }; tipo_nand: { nombre: string };
  };
  psu?: {
    potencia_w: number; modular: 'no' | 'semi' | 'full';
    conector_atx: boolean; tiene_conector_12vhpwr: boolean;
    certificacion: { nombre: string }; tipo_psu: { nombre: string };
  };
  gabinete?: {
    ancho_mm: number; alto_mm: number; profundidad_mm: number;
    longitud_gpu_max_mm: number; altura_cooler_max_mm: number;
    tipo_gabinete: { nombre: string }; estructura: { nombre: string };
  };
}

export interface EntradaPrecio {
  uuid: string;
  precio: number;
  url: string | null;
  disponible: boolean;
  tienda: { nombre: string; website: string | null };
  cupon: { codigo: string; descuento: number; tipo: string } | null;
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

// ─────────────────────────────────────────────────────────────────────────────

@Injectable({ providedIn: 'root' })
export class ComponenteService {
  private readonly API = '/api/v1';

  constructor(private http: HttpClient) {}

  buscar(params: {
    categoria?: string;
    q?: string;
    page?: number;
    marca?: string;
    orden?: string;
  }): Observable<PaginatedResponse> {
    let httpParams = new HttpParams();
    if (params.categoria) httpParams = httpParams.set('categoria', params.categoria);
    if (params.q)         httpParams = httpParams.set('buscar', params.q);
    if (params.page)      httpParams = httpParams.set('page', params.page.toString());
    if (params.marca)     httpParams = httpParams.set('marca', params.marca);
    if (params.orden)     httpParams = httpParams.set('ordenar', params.orden);

    return this.http.get<any>(`${this.API}/componentes`, { params: httpParams }).pipe(
      map(res => ({
        ...res,
        data: res.data.map((c: any) => this.mapearComponente(c))
      }))
    );
  }

  getPrecios(uuid: string): Observable<any> {
    return this.http.get(`${this.API}/componentes/${uuid}/precios`);
  }

  getDetalle(uuid: string): Observable<ComponenteDetalle> {
    return this.http.get<any>(`${this.API}/componentes/${uuid}`).pipe(
      map(c => ({
        ...this.mapearComponente(c),
        descripcion:    c.descripcion    ?? null,
        cpu:            c.cpu            ?? undefined,
        gpu:            c.gpu            ?? undefined,
        ram:            c.ram            ?? undefined,
        placa_base:     c.placa_base     ?? undefined,
        almacenamiento: c.almacenamiento ?? undefined,
        psu:            c.psu            ?? undefined,
        gabinete:       c.gabinete       ?? undefined,
      }) as ComponenteDetalle)
    );
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

  private mapearComponente(c: any): Componente {
    const precios: number[] = (c.precios_actuales ?? []).map((p: any) => Number(p.precio));
    return {
      uuid:          c.uuid,
      nombre:        c.nombre,
      categoria:     c.categoria,
      imagen_url:    c.imagen_url,
      marca:         c.marca ?? null,
      precio_min:    precios.length > 0 ? Math.min(...precios) : null,
      precio_max:    precios.length > 0 ? Math.max(...precios) : null,
      num_tiendas:   precios.length,
      tiene_cupon:   (c.cupones_activos ?? []).length > 0,
      bajada_precio: false,
    };
  }
}