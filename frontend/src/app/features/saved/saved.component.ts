import { Component, OnInit, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterModule, ActivatedRoute } from '@angular/router';
import { GuardadoService, ComponenteGuardado, AlertaPrecio, ConfiguracionGuardada } from '../../core/services/guardado.service';

type Pestana = 'componentes' | 'configuraciones' | 'alertas';

@Component({
  selector: 'app-saved',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './saved.component.html',
  styleUrl: './saved.component.scss'
})
export class SavedComponent implements OnInit {

  pestanaActiva = signal<Pestana>('componentes');
  cargando      = signal(true);
  cargandoAlertas = signal(false);
  cargandoConfigs = signal(false);
  error         = signal('');

  // ── Componentes guardados ──────────────────────────────────
  guardados     = signal<ComponenteGuardado[]>([]);
  editandoNotas = signal<string | null>(null);
  notasTmp      = '';
  eliminando    = signal<string | null>(null);

  // ── Configuraciones (backend real) ─────────────────────────
  configuraciones    = signal<ConfiguracionGuardada[]>([]);
  editandoNotasCfg   = signal<string | null>(null);
  notasTmpCfg        = '';
  eliminandoCfg      = signal<string | null>(null);

  // ── Alertas (backend real) ─────────────────────────────────
  alertas           = signal<AlertaPrecio[]>([]);
  eliminandoAlerta  = signal<string | null>(null);

  numGuardados       = computed(() => this.guardados().length);
  numConfiguraciones = computed(() => this.configuraciones().length);
  numAlertas         = computed(() => this.alertas().filter(a => a.activa).length);

  constructor(
    private guardadoService: GuardadoService,
    private router: Router,
    private route: ActivatedRoute,
  ) {}

  ngOnInit(): void {
    // Leer queryParam ?tab= para abrir pestaña correcta al volver del configurador
    this.route.queryParams.subscribe(params => {
      if (params['tab']) this.pestanaActiva.set(params['tab'] as Pestana);
    });
    this.cargarGuardados();
    this.cargarAlertas();
    this.cargarConfiguraciones();
  }

  // ── Carga ──────────────────────────────────────────────────

  cargarGuardados(): void {
    this.cargando.set(true);
    this.guardadoService.listar().subscribe({
      next: (data) => { this.guardados.set(data); this.cargando.set(false); },
      error: () => { this.error.set('No se pudieron cargar los componentes guardados.'); this.cargando.set(false); }
    });
  }

  cargarAlertas(): void {
    this.cargandoAlertas.set(true);
    this.guardadoService.listarAlertas().subscribe({
      next: (data) => { this.alertas.set(data); this.cargandoAlertas.set(false); },
      error: () => this.cargandoAlertas.set(false)
    });
  }

  cargarConfiguraciones(): void {
    this.cargandoConfigs.set(true);
    this.guardadoService.listarConfiguraciones().subscribe({
      next: (data) => { this.configuraciones.set(data); this.cargandoConfigs.set(false); },
      error: () => this.cargandoConfigs.set(false)
    });
  }

  // ── Eliminar guardado ──────────────────────────────────────

  eliminar(uuid: string): void {
    this.eliminando.set(uuid);
    this.guardadoService.eliminar(uuid).subscribe({
      next: () => { this.guardados.update(gs => gs.filter(g => g.uuid !== uuid)); this.eliminando.set(null); },
      error: () => this.eliminando.set(null)
    });
  }

  // ── Notas componentes ──────────────────────────────────────

  empezarEditarNotas(g: ComponenteGuardado): void {
    this.editandoNotas.set(g.uuid);
    this.notasTmp = g.notas ?? '';
  }

  guardarNotas(uuid: string): void {
    this.guardadoService.actualizarNotas(uuid, this.notasTmp || null).subscribe({
      next: () => {
        this.guardados.update(gs => gs.map(g => g.uuid === uuid ? { ...g, notas: this.notasTmp || null } : g));
        this.editandoNotas.set(null);
      }
    });
  }

  cancelarNotas(): void { this.editandoNotas.set(null); }

  // ── Configuraciones ────────────────────────────────────────

  eliminarConfiguracion(uuid: string): void {
    this.eliminandoCfg.set(uuid);
    this.guardadoService.eliminarConfiguracion(uuid).subscribe({
      next: () => { this.configuraciones.update(cs => cs.filter(c => c.uuid !== uuid)); this.eliminandoCfg.set(null); },
      error: () => this.eliminandoCfg.set(null)
    });
  }

  empezarEditarNotasCfg(cfg: ConfiguracionGuardada): void {
    this.editandoNotasCfg.set(cfg.uuid);
    this.notasTmpCfg = cfg.notas ?? '';
  }

  guardarNotasCfg(uuid: string): void {
    this.guardadoService.actualizarNotasConfiguracion(uuid, this.notasTmpCfg || null).subscribe({
      next: () => {
        this.configuraciones.update(cs => cs.map(c => c.uuid === uuid ? { ...c, notas: this.notasTmpCfg || null } : c));
        this.editandoNotasCfg.set(null);
      }
    });
  }

  cancelarNotasCfg(): void { this.editandoNotasCfg.set(null); }

  abrirEnConfigurador(cfg: ConfiguracionGuardada): void {
    this.router.navigate(['/configurador'], { queryParams: { cfg: cfg.uuid } });
  }

  // ── Alertas ────────────────────────────────────────────────

  toggleAlerta(alerta: AlertaPrecio): void {
    this.guardadoService.toggleAlerta(alerta.uuid, !alerta.activa).subscribe({
      next: () => {
        this.alertas.update(as => as.map(a => a.uuid === alerta.uuid ? { ...a, activa: !a.activa } : a));
      }
    });
  }

  eliminarAlerta(uuid: string): void {
    this.eliminandoAlerta.set(uuid);
    this.guardadoService.eliminarAlerta(uuid).subscribe({
      next: () => { this.alertas.update(as => as.filter(a => a.uuid !== uuid)); this.eliminandoAlerta.set(null); },
      error: () => this.eliminandoAlerta.set(null)
    });
  }

  // ── Navegación ─────────────────────────────────────────────

  abrirEnBuscador(componenteUuid: string, categoria?: string): void {
    const queryParams: Record<string, string> = { uuid: componenteUuid };
    if (categoria) queryParams['categoria'] = categoria;
    this.router.navigate(['/buscar'], { queryParams });
  }

  // ── Helpers ────────────────────────────────────────────────

  setPestana(p: Pestana): void { this.pestanaActiva.set(p); }

  formatPrecio(precio: number | null | undefined): string {
    if (!precio) return 'Sin precio';
    return precio.toLocaleString('es-ES', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });
  }

  nombreCategoria(slug: string): string {
    const map: Record<string, string> = {
      cpu: 'CPU', gpu: 'GPU', ram: 'RAM', placa_base: 'Placa Base',
      almacenamiento: 'Almacenamiento', psu: 'PSU', gabinete: 'Gabinete',
      refrigeracion_aire: 'Refrig. Aire', refrigeracion_liquida: 'Refrig. Líquida',
      ventilador: 'Ventilador',
    };
    return map[slug] ?? slug;
  }

  pctAlerta(actual: number | null, objetivo: number): number {
    if (!actual || actual === 0) return 0;
    return Math.min(Math.round((objetivo / actual) * 100), 100);
  }
}