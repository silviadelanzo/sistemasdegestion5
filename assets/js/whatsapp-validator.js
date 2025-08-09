/**
 * VALIDADOR DE WHATSAPP INTERNACIONAL
 * Sistema de Gestión - Componente JavaScript
 * Valida números WhatsApp en formato internacional
 */

class WhatsAppValidator {
    constructor() {
        this.countryCodes = {
            'AR': '+54',  // Argentina
            'BR': '+55',  // Brasil  
            'CL': '+56',  // Chile
            'UY': '+598', // Uruguay
            'PY': '+595', // Paraguay
            'US': '+1',   // Estados Unidos
            'MX': '+52',  // México
            'CO': '+57',  // Colombia
            'PE': '+51',  // Perú
            'EC': '+593', // Ecuador
            'VE': '+58',  // Venezuela
            'BO': '+591'  // Bolivia
        };
        
        this.countryFlags = {
            'AR': '🇦🇷',
            'BR': '🇧🇷', 
            'CL': '🇨🇱',
            'UY': '🇺🇾',
            'PY': '🇵🇾',
            'US': '🇺🇸',
            'MX': '🇲🇽',
            'CO': '🇨🇴',
            'PE': '🇵🇪',
            'EC': '🇪🇨',
            'VE': '🇻🇪',
            'BO': '🇧🇴'
        };
    }
    
    /**
     * Limpia el número removiendo caracteres no numéricos
     */
    cleanNumber(number) {
        if (!number) return '';
        return number.replace(/[^0-9+]/g, '');
    }
    
    /**
     * Valida formato de número WhatsApp
     */
    validateWhatsApp(number) {
        const cleaned = this.cleanNumber(number);
        
        // Debe tener entre 10 y 15 dígitos (con o sin +)
        const withoutPlus = cleaned.replace(/^\+/, '');
        
        if (withoutPlus.length < 10 || withoutPlus.length > 15) {
            return {
                valid: false,
                error: 'El número debe tener entre 10 y 15 dígitos'
            };
        }
        
        // Solo debe contener números después del +
        if (!/^[0-9]+$/.test(withoutPlus)) {
            return {
                valid: false,
                error: 'Solo se permiten números'
            };
        }
        
        return {
            valid: true,
            formatted: this.formatNumber(cleaned),
            country: this.detectCountry(cleaned)
        };
    }
    
    /**
     * Detecta el país basado en el código
     */
    detectCountry(number) {
        const cleaned = number.replace(/^\+/, '');
        
        for (const [country, code] of Object.entries(this.countryCodes)) {
            const codeWithoutPlus = code.replace(/^\+/, '');
            if (cleaned.startsWith(codeWithoutPlus)) {
                return {
                    code: country,
                    name: this.getCountryName(country),
                    flag: this.countryFlags[country],
                    phoneCode: code
                };
            }
        }
        
        return null;
    }
    
    /**
     * Formatea el número para mostrar
     */
    formatNumber(number) {
        let cleaned = this.cleanNumber(number);
        
        if (!cleaned.startsWith('+')) {
            cleaned = '+' + cleaned;
        }
        
        return cleaned;
    }
    
    /**
     * Obtiene el nombre del país
     */
    getCountryName(code) {
        const names = {
            'AR': 'Argentina',
            'BR': 'Brasil',
            'CL': 'Chile', 
            'UY': 'Uruguay',
            'PY': 'Paraguay',
            'US': 'Estados Unidos',
            'MX': 'México',
            'CO': 'Colombia',
            'PE': 'Perú',
            'EC': 'Ecuador',
            'VE': 'Venezuela',
            'BO': 'Bolivia'
        };
        return names[code] || 'Desconocido';
    }
    
    /**
     * Crea el HTML del validador para un input
     */
    createValidatorHTML(inputId, containerId) {
        return `
            <div id="${containerId}" class="whatsapp-validator">
                <div class="input-group">
                    <input type="text" id="${inputId}" placeholder="Ej: +5491123456789" maxlength="20" class="form-control">
                    <div class="input-group-append">
                        <span class="input-group-text country-flag" id="${inputId}_flag"></span>
                    </div>
                </div>
                <div id="${inputId}_feedback" class="feedback-message"></div>
                <small class="form-text text-muted">
                    Formato: +CódigoPaís + Número (solo números, 10-15 dígitos)
                </small>
            </div>
        `;
    }
    
    /**
     * Inicializa el validador en un input específico
     */
    initializeValidator(inputId) {
        const input = document.getElementById(inputId);
        const feedback = document.getElementById(inputId + '_feedback');
        const flag = document.getElementById(inputId + '_flag');
        
        if (!input) return;
        
        // Evento de validación en tiempo real
        input.addEventListener('input', (e) => {
            const value = e.target.value;
            const result = this.validateWhatsApp(value);
            
            // Limpiar clases previas
            input.classList.remove('is-valid', 'is-invalid');
            
            if (value === '') {
                // Campo vacío
                feedback.innerHTML = '';
                flag.innerHTML = '';
                return;
            }
            
            if (result.valid) {
                input.classList.add('is-valid');
                input.value = result.formatted;
                
                if (result.country) {
                    feedback.innerHTML = `<span class="text-success">✅ ${result.country.flag} ${result.country.name} (${result.country.phoneCode})</span>`;
                    flag.innerHTML = result.country.flag;
                } else {
                    feedback.innerHTML = '<span class="text-success">✅ Formato válido</span>';
                    flag.innerHTML = '📱';
                }
            } else {
                input.classList.add('is-invalid');
                feedback.innerHTML = `<span class="text-danger">❌ ${result.error}</span>`;
                flag.innerHTML = '⚠️';
            }
        });
        
        // Evento para formatear al perder foco
        input.addEventListener('blur', (e) => {
            const value = e.target.value;
            if (value) {
                const cleaned = this.cleanNumber(value);
                if (cleaned && !cleaned.startsWith('+')) {
                    e.target.value = '+' + cleaned;
                    e.target.dispatchEvent(new Event('input'));
                }
            }
        });
        
        // Prevenir caracteres no válidos
        input.addEventListener('keypress', (e) => {
            const char = String.fromCharCode(e.which);
            const currentValue = e.target.value;
            
            // Permitir + solo al inicio
            if (char === '+' && currentValue.length > 0) {
                e.preventDefault();
                return;
            }
            
            // Solo permitir números y + al inicio
            if (!/[0-9+]/.test(char)) {
                e.preventDefault();
            }
        });
    }
    
    /**
     * Genera un enlace de WhatsApp
     */
    generateWhatsAppLink(number, message = '') {
        const cleaned = this.cleanNumber(number).replace(/^\+/, '');
        const encodedMessage = encodeURIComponent(message);
        return `https://wa.me/${cleaned}${message ? '?text=' + encodedMessage : ''}`;
    }
    
    /**
     * Crea un botón de WhatsApp
     */
    createWhatsAppButton(number, message = '', buttonText = 'Enviar WhatsApp') {
        const link = this.generateWhatsAppLink(number, message);
        return `<a href="${link}" target="_blank" class="btn btn-success btn-sm">
                    <i class="fab fa-whatsapp"></i> ${buttonText}
                </a>`;
    }
}

// CSS para el validador
const whatsappValidatorCSS = `
    <style>
    .whatsapp-validator .input-group {
        margin-bottom: 5px;
    }
    
    .whatsapp-validator .country-flag {
        font-size: 18px;
        min-width: 45px;
        text-align: center;
        background-color: #f8f9fa;
    }
    
    .whatsapp-validator .feedback-message {
        font-size: 0.875em;
        margin-bottom: 5px;
        min-height: 20px;
    }
    
    .whatsapp-validator input.is-valid {
        border-color: #28a745;
    }
    
    .whatsapp-validator input.is-invalid {
        border-color: #dc3545;
    }
    
    .whatsapp-validator input:focus {
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    </style>
`;

// Agregar CSS al head
if (typeof document !== 'undefined') {
    document.head.insertAdjacentHTML('beforeend', whatsappValidatorCSS);
}

// Instancia global del validador
const whatsappValidator = new WhatsAppValidator();

// Función de inicialización automática
document.addEventListener('DOMContentLoaded', function() {
    // Buscar todos los inputs con class="whatsapp-input" e inicializarlos
    const whatsappInputs = document.querySelectorAll('.whatsapp-input');
    whatsappInputs.forEach(input => {
        whatsappValidator.initializeValidator(input.id);
    });
});
