import {
    Directive, ElementRef, HostListener, Input,
    OnDestroy, inject,
  } from '@angular/core';
  
  /**
   * Tooltip que se posiciona arriba de la card, y si no cabe
   * (viewport o header fijo), aparece debajo.
   *
   * Uso: [appTooltip]="comp.descripcion || ''"
   */
  @Directive({
    selector: '[appTooltip]',
    standalone: true,
  })
  export class TooltipDirective implements OnDestroy {
  
    @Input('appTooltip') text = '';
  
    private el = inject(ElementRef);
  
    private box:   HTMLElement | null = null;
    private arrow: HTMLElement | null = null;
  
    /** Altura del header fijo — ajusta si cambia */
    private readonly HEADER_H = 56;
    private readonly GAP      = 8;
  
    @HostListener('mouseenter')
    onEnter() {
      const content = this.text || this.el.nativeElement.getAttribute('data-tooltip');
      if (!content?.trim()) return;
      this.show(content);
    }
  
    @HostListener('mouseleave')
    onLeave() { this.hide(); }
  
    // ── Leer variables CSS del tema en tiempo de ejecución ───────────────────
  
    private cssVar(name: string, fallback: string): string {
      const val = getComputedStyle(document.documentElement)
        .getPropertyValue(name).trim();
      return val || fallback;
    }
  
    private get bg():     string { return this.cssVar('--surface', '#1e1e2e'); }
    private get border(): string { return this.cssVar('--border',  '#3a3a4a'); }
    private get color():  string { return this.cssVar('--text',    '#e2e2e2'); }
  
    // ── Mostrar ───────────────────────────────────────────────────────────────
  
    private show(content: string) {
      this.hide();
  
      // Box
      const box = document.createElement('div');
      Object.assign(box.style, {
        position:     'fixed',
        zIndex:       '99999',
        background:   this.bg,
        color:        this.color,
        border:       `1px solid ${this.border}`,
        borderRadius: '8px',
        boxShadow:    '0 4px 20px rgba(0,0,0,0.35)',
        fontSize:     '0.75rem',
        lineHeight:   '1.45',
        padding:      '0.55rem 0.75rem',
        width:        '220px',
        whiteSpace:   'normal',
        textAlign:    'left',
        pointerEvents:'none',
        opacity:      '0',
        transition:   'opacity 0.12s ease',
      });
      box.textContent = content;
      document.body.appendChild(box);
      this.box = box;
  
      // Arrow
      const arrow = document.createElement('div');
      Object.assign(arrow.style, {
        position:     'fixed',
        zIndex:       '99999',
        width:        '0',
        height:       '0',
        border:       '6px solid transparent',
        pointerEvents:'none',
      });
      document.body.appendChild(arrow);
      this.arrow = arrow;
  
      // Posicionar tras render
      requestAnimationFrame(() => {
        this.position();
        box.style.opacity = '1';
      });
    }
  
    // ── Posicionamiento ───────────────────────────────────────────────────────
  
    private position() {
      const box   = this.box;
      const arrow = this.arrow;
      if (!box || !arrow) return;
  
      const host    = (this.el.nativeElement as HTMLElement).getBoundingClientRect();
      const boxRect = box.getBoundingClientRect();
      const margin  = 8;
  
      // Centrado horizontal, sin salirse del viewport
      let left = host.left + host.width / 2 - boxRect.width / 2;
      left = Math.max(margin, Math.min(left, window.innerWidth - boxRect.width - margin));
  
      const spaceAbove = host.top - this.HEADER_H;
      const fitsAbove  = spaceAbove >= boxRect.height + this.GAP;
  
      let boxTop: number;
      let arrowTop: number;
      let arrowBorderTop: string;
      let arrowBorderBottom: string;
  
      if (fitsAbove) {
        boxTop            = host.top    - boxRect.height - this.GAP;
        arrowTop          = host.top    - this.GAP;
        arrowBorderTop    = 'transparent';
        arrowBorderBottom = this.border;   // punta hacia abajo ▼
      } else {
        boxTop            = host.bottom + this.GAP;
        arrowTop          = host.bottom - 1;
        arrowBorderTop    = this.border;   // punta hacia arriba ▲
        arrowBorderBottom = 'transparent';
      }
  
      const arrowLeft = host.left + host.width / 2 - 6;
  
      Object.assign(box.style, {
        top:  `${boxTop}px`,
        left: `${left}px`,
      });
  
      Object.assign(arrow.style, {
        top:               `${arrowTop}px`,
        left:              `${arrowLeft}px`,
        borderTopColor:    arrowBorderTop,
        borderBottomColor: arrowBorderBottom,
      });
    }
  
    // ── Ocultar ───────────────────────────────────────────────────────────────
  
    private hide() {
      this.box?.remove();   this.box   = null;
      this.arrow?.remove(); this.arrow = null;
    }
  
    ngOnDestroy() { this.hide(); }
  }