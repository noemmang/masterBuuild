import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss'
})
export class LoginComponent {
  email = '';
  password = '';
  cargando = false;
  error = '';

  constructor(private auth: AuthService, private router: Router) {}

  submit() {
    this.error = '';
    this.cargando = true;
    this.auth.login(this.email, this.password).subscribe({
      next: () => this.router.navigate(['/home']),
      error: (err) => {
        this.error = err.error?.message || 'Credenciales incorrectas';
        this.cargando = false;
      }
    });
  }
}