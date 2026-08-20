import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss'
})
export class LoginComponent implements OnInit {
  email = '';
  password = '';
  cargando = false;
  error = '';

  /** Ruta a la que volver tras iniciar sesión (p. ej. si veníamos de "Guardar" sin sesión) */
  returnUrl = '/home';

  constructor(private auth: AuthService, private router: Router, private route: ActivatedRoute) {}

  ngOnInit(): void {
    this.returnUrl = this.route.snapshot.queryParams['returnUrl'] || '/home';
  }

  submit() {
    this.error = '';
    this.cargando = true;
    this.auth.login(this.email, this.password).subscribe({
      next: () => this.router.navigateByUrl(this.returnUrl),
      error: (err) => {
        this.error = err.error?.message || 'Credenciales incorrectas';
        this.cargando = false;
      }
    });
  }
}