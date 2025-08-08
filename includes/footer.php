<!-- Footer -->
<footer style="background: linear-gradient(135deg, var(--bg-dark), #0f172a); color: var(--text-white); text-align: center; padding: 2rem 0; margin-top: 3rem; border-top: 3px solid var(--primary-color);">
    <div class="container">
        <div style="display: flex; justify-content: center; align-items: center; gap: 1rem; margin-bottom: 1rem;">
            <div style="width: 40px; height: 40px; background: var(--primary-color); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-droplet-fill" style="color: var(--text-white); font-size: 1.2rem;"></i>
            </div>
            <div>
                <h4 style="margin: 0; font-size: 1.1rem; font-weight: 600;">Sistema de Gestión Hídrica Comunitaria</h4>
                <p style="margin: 0; font-size: 0.9rem; opacity: 0.8;">Cañada de Flores, Hidalgo</p>
            </div>
        </div>
        
        <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem; font-size: 0.85rem; opacity: 0.7;">
            <p style="margin: 0;">&copy; <?php echo date('Y'); ?> Sistema GHC - Todos los derechos reservados</p>
            <p style="margin: 0.5rem 0 0 0;">Desarrollado para la comunidad ejidal de Cañada de Flores</p>
        </div>
    </div>
</footer>

<?php if (isset($js_adicional)): ?>
    <?php foreach ($js_adicional as $js): ?>
        <script src="<?php echo BASE_URL . $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Scripts base -->
<script>
// Funciones globales del sistema
window.SistemaGHC = {
    
    // Mostrar mensajes de notificación
    mostrarMensaje: function(tipo, mensaje, duracion = 5000) {
        // Crear elemento de notificación
        const notification = document.createElement('div');
        notification.className = `alert alert-${tipo} fade-in`;
        notification.style.cssText = `
            position: fixed;
            top: 90px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            margin: 0;
            box-shadow: var(--shadow-lg);
            border: none;
        `;
        
        // Icono según el tipo
        let icono = '';
        switch (tipo) {
            case 'success': icono = 'bi-check-circle-fill'; break;
            case 'error':
            case 'danger': icono = 'bi-exclamation-triangle-fill'; break;
            case 'warning': icono = 'bi-exclamation-circle-fill'; break;
            case 'info': icono = 'bi-info-circle-fill'; break;
            default: icono = 'bi-info-circle-fill'; break;
        }
        
        notification.innerHTML = `
            <i class="${icono}"></i>
            <div>
                <strong>${tipo === 'error' || tipo === 'danger' ? 'Error' : tipo === 'warning' ? 'Advertencia' : tipo === 'success' ? 'Éxito' : 'Información'}:</strong>
                ${mensaje}
            </div>
            <button type="button" onclick="this.parentElement.remove()" style="
                background: none;
                border: none;
                color: inherit;
                font-size: 1.2rem;
                cursor: pointer;
                padding: 0;
                margin-left: auto;
                opacity: 0.7;
            ">
                <i class="bi bi-x"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remover después de la duración especificada
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }
        }, duracion);
    },
    
    // Confirmar acciones destructivas
    confirmarAccion: function(mensaje = '¿Estás seguro de que deseas realizar esta acción?', titulo = 'Confirmar acción') {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: fadeIn 0.2s ease-out;
            `;
            
            modal.innerHTML = `
                <div class="card" style="max-width: 400px; width: 90%; margin: 0;">
                    <div class="card-header">
                        <i class="bi bi-question-circle-fill" style="color: var(--warning-color);"></i>
                        ${titulo}
                    </div>
                    <div class="card-body">
                        <p style="margin-bottom: 1.5rem;">${mensaje}</p>
                        <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                            <button type="button" class="btn btn-secondary" onclick="window.SistemaGHC.cerrarModal(this, false)">
                                <i class="bi bi-x"></i> Cancelar
                            </button>
                            <button type="button" class="btn btn-danger" onclick="window.SistemaGHC.cerrarModal(this, true)">
                                <i class="bi bi-check"></i> Confirmar
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            modal._resolver = resolve;
            document.body.appendChild(modal);
            
            // Cerrar con Escape
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    this.cerrarModal(modal.querySelector('button'), false);
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);
        });
    },
    
    // Cerrar modal de confirmación
    cerrarModal: function(element, resultado) {
        const modal = element.closest('[style*="position: fixed"]');
        if (modal && modal._resolver) {
            modal._resolver(resultado);
            modal.remove();
        }
    },
    
    // Validar formularios
    validarFormulario: function(formId) {
        const form = document.getElementById(formId);
        if (!form) return true;
        
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.style.borderColor = 'var(--danger-color)';
                field.style.boxShadow = '0 0 0 3px rgba(220, 38, 38, 0.1)';
                isValid = false;
            } else {
                field.style.borderColor = 'var(--border-color)';
                field.style.boxShadow = 'none';
            }
        });
        
        if (!isValid) {
            this.mostrarMensaje('error', 'Por favor completa todos los campos obligatorios.');
        }
        
        return isValid;
    },
    
    // Formatear números
    formatearNumero: function(numero, decimales = 0) {
        return new Intl.NumberFormat('es-MX', {
            minimumFractionDigits: decimales,
            maximumFractionDigits: decimales
        }).format(numero);
    },
    
    // Formatear moneda
    formatearMoneda: function(cantidad) {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        }).format(cantidad);
    },
    
    // Loading state para botones
    setLoading: function(button, loading = true) {
        if (loading) {
            button.disabled = true;
            button.classList.add('loading');
            button._originalText = button.innerHTML;
            button.innerHTML = '<i class="bi bi-arrow-clockwise" style="animation: spin 1s linear infinite;"></i> Procesando...';
        } else {
            button.disabled = false;
            button.classList.remove('loading');
            if (button._originalText) {
                button.innerHTML = button._originalText;
            }
        }
    }
};

// Funciones de utilidad globales
function mostrarMensaje(tipo, mensaje) {
    window.SistemaGHC.mostrarMensaje(tipo, mensaje);
}

function confirmarEliminacion(mensaje = '¿Estás seguro de que deseas eliminar este elemento?') {
    return window.SistemaGHC.confirmarAccion(mensaje, 'Confirmar eliminación');
}

function validarFormulario(formId) {
    return window.SistemaGHC.validarFormulario(formId);
}

function formatearNumero(numero, decimales = 0) {
    return window.SistemaGHC.formatearNumero(numero, decimales);
}

// Auto-remover alertas existentes después de 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (!alert.querySelector('button')) {
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.remove();
                        }
                    }, 300);
                }
            }, 5000);
        }
    });
});

// Mejoras de accesibilidad
document.addEventListener('DOMContentLoaded', function() {
    // Focus trap para modales
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            const modal = document.querySelector('[style*="position: fixed"][style*="z-index: 10000"]');
            if (modal) {
                const focusableElements = modal.querySelectorAll('button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];
                
                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                } else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        }
    });
});

// Añadir animación de spin para iconos de loading
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>

</body>
</html>