/**
 * ORDIVO - Universal SweetAlert2 Configuration
 * Beautiful alert system for all pages
 */

// Configure SweetAlert2 defaults
const SwalConfig = {
    // Brand colors
    colors: {
        primary: '#e21b70',
        secondary: '#d91a65',
        success: '#28a745',
        danger: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    },
    
    // Default configuration
    defaults: {
        confirmButtonColor: '#e21b70',
        cancelButtonColor: '#6c757d',
        showClass: {
            popup: 'animate__animated animate__fadeInDown'
        },
        hideClass: {
            popup: 'animate__animated animate__fadeOutUp'
        }
    }
};

// Set global defaults
Swal.mixin(SwalConfig.defaults);

/**
 * Success Alert
 * @param {string} title - Alert title
 * @param {string} text - Alert message
 * @param {object} options - Additional options
 */
function showSuccess(title, text = '', options = {}) {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'success',
        confirmButtonColor: SwalConfig.colors.success,
        timer: 3000,
        timerProgressBar: true,
        ...options
    });
}

/**
 * Error Alert
 * @param {string} title - Alert title
 * @param {string} text - Alert message
 * @param {object} options - Additional options
 */
function showError(title, text = '', options = {}) {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'error',
        confirmButtonColor: SwalConfig.colors.danger,
        ...options
    });
}

/**
 * Warning Alert
 * @param {string} title - Alert title
 * @param {string} text - Alert message
 * @param {object} options - Additional options
 */
function showWarning(title, text = '', options = {}) {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        confirmButtonColor: SwalConfig.colors.warning,
        ...options
    });
}

/**
 * Info Alert
 * @param {string} title - Alert title
 * @param {string} text - Alert message
 * @param {object} options - Additional options
 */
function showInfo(title, text = '', options = {}) {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'info',
        confirmButtonColor: SwalConfig.colors.info,
        ...options
    });
}

/**
 * Confirmation Dialog
 * @param {string} title - Dialog title
 * @param {string} text - Dialog message
 * @param {object} options - Additional options
 */
function showConfirm(title, text = '', options = {}) {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        confirmButtonColor: SwalConfig.colors.primary,
        cancelButtonColor: SwalConfig.colors.secondary,
        ...options
    });
}

/**
 * Delete Confirmation
 * @param {string} itemName - Name of item to delete
 * @param {object} options - Additional options
 */
function showDeleteConfirm(itemName = 'this item', options = {}) {
    return Swal.fire({
        title: 'Are you sure?',
        text: `You won't be able to revert this! This will permanently delete ${itemName}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        confirmButtonColor: SwalConfig.colors.danger,
        cancelButtonColor: SwalConfig.colors.secondary,
        ...options
    });
}

/**
 * Loading Alert
 * @param {string} title - Loading title
 * @param {string} text - Loading message
 */
function showLoading(title = 'Loading...', text = 'Please wait') {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'info',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Toast Notification
 * @param {string} message - Toast message
 * @param {string} type - Toast type (success, error, warning, info)
 * @param {object} options - Additional options
 */
function showToast(message, type = 'success', options = {}) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    return Toast.fire({
        icon: type,
        title: message,
        ...options
    });
}

/**
 * Input Dialog
 * @param {string} title - Dialog title
 * @param {string} inputPlaceholder - Input placeholder
 * @param {object} options - Additional options
 */
function showInput(title, inputPlaceholder = '', options = {}) {
    return Swal.fire({
        title: title,
        input: 'text',
        inputPlaceholder: inputPlaceholder,
        showCancelButton: true,
        confirmButtonText: 'Submit',
        cancelButtonText: 'Cancel',
        confirmButtonColor: SwalConfig.colors.primary,
        cancelButtonColor: SwalConfig.colors.secondary,
        inputValidator: (value) => {
            if (!value) {
                return 'You need to write something!';
            }
        },
        ...options
    });
}

/**
 * Custom HTML Alert
 * @param {string} title - Alert title
 * @param {string} html - HTML content
 * @param {object} options - Additional options
 */
function showCustom(title, html, options = {}) {
    return Swal.fire({
        title: title,
        html: html,
        confirmButtonColor: SwalConfig.colors.primary,
        ...options
    });
}

/**
 * Progress Alert
 * @param {string} title - Progress title
 * @param {number} progress - Progress percentage (0-100)
 */
function showProgress(title, progress = 0) {
    return Swal.fire({
        title: title,
        html: `
            <div class="progress mb-3">
                <div class="progress-bar bg-primary" role="progressbar" style="width: ${progress}%" aria-valuenow="${progress}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <p>${progress}% Complete</p>
        `,
        showConfirmButton: false,
        allowOutsideClick: false,
        allowEscapeKey: false
    });
}

/**
 * Network Error Alert
 * @param {string} action - Action that failed
 */
function showNetworkError(action = 'perform this action') {
    return showError(
        'Network Error',
        `Unable to ${action}. Please check your internet connection and try again.`
    );
}

/**
 * Permission Denied Alert
 */
function showPermissionDenied() {
    return showError(
        'Permission Denied',
        'You do not have permission to perform this action.'
    );
}

/**
 * Session Expired Alert
 */
function showSessionExpired() {
    return Swal.fire({
        title: 'Session Expired',
        text: 'Your session has expired. Please log in again.',
        icon: 'warning',
        confirmButtonText: 'Login',
        confirmButtonColor: SwalConfig.colors.primary,
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '/auth/login.php';
        }
    });
}

// Export functions for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        showSuccess,
        showError,
        showWarning,
        showInfo,
        showConfirm,
        showDeleteConfirm,
        showLoading,
        showToast,
        showInput,
        showCustom,
        showProgress,
        showNetworkError,
        showPermissionDenied,
        showSessionExpired,
        SwalConfig
    };
}