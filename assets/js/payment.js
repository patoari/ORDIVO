/**
 * ORDIVO Payment Handler
 * Frontend payment processing logic
 */

class PaymentHandler {
    constructor() {
        this.selectedMethod = null;
        this.orderId = null;
        this.amount = 0;
        this.init();
    }
    
    init() {
        this.attachEventListeners();
        this.loadPaymentMethods();
    }
    
    attachEventListeners() {
        // Payment method selection
        document.querySelectorAll('.payment-method-card').forEach(card => {
            card.addEventListener('click', (e) => {
                this.selectPaymentMethod(card.dataset.method);
            });
        });
        
        // Proceed to payment button
        const proceedBtn = document.getElementById('proceedPaymentBtn');
        if (proceedBtn) {
            proceedBtn.addEventListener('click', () => {
                this.processPayment();
            });
        }
    }
    
    selectPaymentMethod(method) {
        // Remove previous selection
        document.querySelectorAll('.payment-method-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Select new method
        const selectedCard = document.querySelector(`[data-method="${method}"]`);
        if (selectedCard) {
            selectedCard.classList.add('selected');
            this.selectedMethod = method;
            
            // Enable proceed button
            const proceedBtn = document.getElementById('proceedPaymentBtn');
            if (proceedBtn) {
                proceedBtn.disabled = false;
            }
            
            // Show method-specific instructions
            this.showMethodInstructions(method);
        }
    }
    
    showMethodInstructions(method) {
        // Hide all instructions
        document.querySelectorAll('.payment-instructions').forEach(inst => {
            inst.classList.add('d-none');
        });
        
        // Show selected method instructions
        const instructions = document.getElementById(`instructions-${method}`);
        if (instructions) {
            instructions.classList.remove('d-none');
        }
    }
    
    async processPayment() {
        if (!this.selectedMethod) {
            this.showAlert('Please select a payment method', 'warning');
            return;
        }
        
        // Get order details
        this.orderId = document.getElementById('orderId')?.value;
        this.amount = parseFloat(document.getElementById('orderAmount')?.value || 0);
        
        if (!this.orderId || !this.amount) {
            this.showAlert('Invalid order details', 'danger');
            return;
        }
        
        // Show loading
        this.showLoading(true);
        
        try {
            const response = await fetch('process_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'initiate_payment',
                    order_id: this.orderId,
                    payment_method: this.selectedMethod,
                    amount: this.amount
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.handlePaymentSuccess(result.data);
            } else {
                this.showAlert(result.message || 'Payment initiation failed', 'danger');
            }
            
        } catch (error) {
            console.error('Payment error:', error);
            this.showAlert('An error occurred. Please try again.', 'danger');
        } finally {
            this.showLoading(false);
        }
    }
    
    handlePaymentSuccess(data) {
        switch (this.selectedMethod) {
            case 'bkash':
            case 'nagad':
            case 'rocket':
                // Redirect to payment gateway
                if (data.payment_url) {
                    window.location.href = data.payment_url;
                }
                break;
                
            case 'cash':
                // Show success message and redirect
                this.showAlert(data.message, 'success');
                setTimeout(() => {
                    window.location.href = `order_success.php?order_id=${this.orderId}`;
                }, 2000);
                break;
                
            case 'wallet':
                // Wallet payment completed immediately
                this.showAlert(data.message, 'success');
                setTimeout(() => {
                    window.location.href = `payment_success.php?transaction_id=${data.transaction_id}`;
                }, 2000);
                break;
                
            case 'card':
                // Redirect to card payment page
                if (data.payment_url) {
                    window.location.href = data.payment_url;
                }
                break;
                
            default:
                this.showAlert('Payment method not fully implemented', 'info');
        }
    }
    
    async loadPaymentMethods() {
        // This can be enhanced to dynamically load available payment methods
        // For now, payment methods are rendered server-side
    }
    
    showLoading(show) {
        const proceedBtn = document.getElementById('proceedPaymentBtn');
        const spinner = document.getElementById('paymentSpinner');
        
        if (proceedBtn) {
            proceedBtn.disabled = show;
            if (show) {
                proceedBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            } else {
                proceedBtn.innerHTML = '<i class="fas fa-lock me-2"></i>Proceed to Payment';
            }
        }
        
        if (spinner) {
            spinner.classList.toggle('d-none', !show);
        }
    }
    
    showAlert(message, type = 'info') {
        // Check if SweetAlert2 is available
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type === 'danger' ? 'error' : type,
                title: type === 'success' ? 'Success!' : type === 'danger' ? 'Error!' : 'Notice',
                text: message,
                confirmButtonColor: '#e91e63'
            });
        } else {
            // Fallback to Bootstrap alert
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    }
    
    // Verify payment status (for callback handling)
    async verifyPayment(paymentId, method) {
        try {
            const response = await fetch(`process_payment.php?verify=1&payment_id=${paymentId}&method=${method}`);
            const result = await response.json();
            
            if (result.success) {
                window.location.href = `payment_success.php?payment_id=${paymentId}`;
            } else {
                window.location.href = `payment_fail.php?reason=${encodeURIComponent(result.message)}`;
            }
            
        } catch (error) {
            console.error('Verification error:', error);
            window.location.href = 'payment_fail.php?reason=Verification failed';
        }
    }
}

// Initialize payment handler when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.paymentHandler = new PaymentHandler();
    
    // Check if we're on a payment callback page
    const urlParams = new URLSearchParams(window.location.search);
    const paymentId = urlParams.get('paymentID') || urlParams.get('payment_id');
    const method = urlParams.get('method');
    
    if (paymentId && method) {
        // Verify payment
        window.paymentHandler.verifyPayment(paymentId, method);
    }
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PaymentHandler;
}
