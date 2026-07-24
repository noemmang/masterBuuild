import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { HeaderComponent } from './header.component';
import { AuthService } from '../../../core/services/auth.service';

describe('HeaderComponent', () => {
  beforeEach(async () => {
    // Empezamos cada test con el DOM y el localStorage limpios,
    // porque estos dos son "reales" y compartidos entre tests
    // (a diferencia de TestBed, que sí se resetea solo).
    localStorage.removeItem('tema');
    document.documentElement.removeAttribute('data-theme');

    await TestBed.configureTestingModule({
      imports: [HeaderComponent],
      providers: [
        provideRouter([]),
        // No le damos el AuthService real (haría peticiones HTTP de verdad).
        // Le damos un objeto falso con solo lo que el HeaderComponent
        // necesita para poder renderizarse: que exista "usuario()"
        // y que, para este test, devuelva "no hay nadie logueado".
        { provide: AuthService, useValue: { usuario: () => null } },
      ],
    }).compileComponents();
  });

  afterEach(() => {
    document.documentElement.removeAttribute('data-theme');
    localStorage.removeItem('tema');
  });

  it('empieza en modo claro', () => {
    const fixture = TestBed.createComponent(HeaderComponent);
    const header = fixture.componentInstance;

    expect(header.modoOscuro).toBe(false);
    expect(document.documentElement.getAttribute('data-theme')).toBeNull();
  });

  it('activa el modo oscuro al hacer toggle', () => {
    const fixture = TestBed.createComponent(HeaderComponent);
    const header = fixture.componentInstance;

    header.toggleTema();

    expect(header.modoOscuro).toBe(true);
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark');
    expect(localStorage.getItem('tema')).toBe('dark');
  });

  it('vuelve a modo claro si se hace toggle dos veces', () => {
    const fixture = TestBed.createComponent(HeaderComponent);
    const header = fixture.componentInstance;

    header.toggleTema();
    header.toggleTema();

    expect(header.modoOscuro).toBe(false);
    expect(document.documentElement.getAttribute('data-theme')).toBeNull();
    expect(localStorage.getItem('tema')).toBe('light');
  });
});