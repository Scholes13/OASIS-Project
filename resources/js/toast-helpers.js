/**
 * Enhanced Toast Notification Helpers
 * Global functions to show different types of notifications
 */

// Wait for DOM and toast manager to be ready
function waitForToastManager(callback) {
    if (window.toastManager && window.toastManager.container) {
        callback();
    } else {
        setTimeout(() => waitForToastManager(callback), 50);
    }
}

// Core toast functions using the global toast manager
window.showSuccess = function(message, duration = 4000) {
    waitForToastManager(() => {
        window.toastManager.show(message, 'success', duration);
    });
}

window.showError = function(message, duration = 6000) {
    waitForToastManager(() => {
        window.toastManager.show(message, 'error', duration);
    });
}

window.showWarning = function(message, duration = 5000) {
    waitForToastManager(() => {
        window.toastManager.show(message, 'warning', duration);
    });
}

window.showInfo = function(message, duration = 4000) {
    waitForToastManager(() => {
        window.toastManager.show(message, 'info', duration);
    });
}

// Advanced helpers
window.showValidationErrors = function(errors) {
    waitForToastManager(() => {
        if (typeof errors === 'object' && errors !== null) {
            // Laravel validation errors format
            const errorMessages = [];
            for (const field in errors) {
                if (errors[field] && Array.isArray(errors[field])) {
                    errorMessages.push(...errors[field]);
                } else if (errors[field]) {
                    errorMessages.push(errors[field]);
                }
            }
            if (errorMessages.length > 0) {
                window.toastManager.show(errorMessages.join('. '), 'error', 8000);
            }
        } else if (typeof errors === 'string') {
            window.toastManager.show(errors, 'error', 6000);
        }
    });
}

window.showRequiredFields = function(fields) {
    const fieldList = Array.isArray(fields) ? fields.join(', ') : fields;
    waitForToastManager(() => {
        window.toastManager.show(`Please fill in required fields: ${fieldList}`, 'warning', 6000);
    });
}

// Quick notification helpers
window.notifyRequired = function(fieldName) {
    waitForToastManager(() => {
        window.toastManager.show(`${fieldName} is required`, 'warning', 3000);
    });
}

window.notifyCreated = function(itemName = 'Item') {
    waitForToastManager(() => {
        window.toastManager.show(`${itemName} created successfully`, 'success', 4000);
    });
}

window.notifyUpdated = function(itemName = 'Item') {
    waitForToastManager(() => {
        window.toastManager.show(`${itemName} updated successfully`, 'success', 4000);
    });
}

window.notifyDeleted = function(itemName = 'Item') {
    waitForToastManager(() => {
        window.toastManager.show(`${itemName} deleted successfully`, 'success', 4000);
    });
}

window.notifySaved = function(itemName = 'Changes') {
    waitForToastManager(() => {
        window.toastManager.show(`${itemName} saved successfully`, 'success', 4000);
    });
}

// Form validation helpers
window.notifyFormErrors = function(form) {
    const requiredFields = form.querySelectorAll('[required]');
    const emptyFields = [];
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            const label = field.previousElementSibling?.textContent || 
                         field.getAttribute('placeholder') || 
                         field.getAttribute('name');
            if (label) {
                emptyFields.push(label.replace('*', '').trim());
            }
        }
    });
    
    if (emptyFields.length > 0) {
        waitForToastManager(() => {
            window.toastManager.show(`Please fill in required fields: ${emptyFields.join(', ')}`, 'warning', 6000);
        });
        return false;
    }
    return true;
}

// Network/API helpers
window.notifyNetworkError = function() {
    waitForToastManager(() => {
        window.toastManager.show('Network error. Please check your connection and try again.', 'error', 8000);
    });
}

window.notifyServerError = function() {
    waitForToastManager(() => {
        window.toastManager.show('Server error. Please try again later.', 'error', 8000);
    });
}

window.notifyTimeout = function() {
    waitForToastManager(() => {
        window.toastManager.show('Request timed out. Please try again.', 'warning', 6000);
    });
}

// File upload helpers
window.notifyUploadSuccess = function(fileName) {
    waitForToastManager(() => {
        window.toastManager.show(`${fileName} uploaded successfully`, 'success', 4000);
    });
}

window.notifyUploadError = function(fileName) {
    waitForToastManager(() => {
        window.toastManager.show(`Failed to upload ${fileName}`, 'error', 6000);
    });
}

window.notifyFileSizeError = function(maxSize) {
    waitForToastManager(() => {
        window.toastManager.show(`File size exceeds ${maxSize} limit`, 'warning', 6000);
    });
}

window.notifyFileTypeError = function(allowedTypes) {
    waitForToastManager(() => {
        window.toastManager.show(`Only ${allowedTypes} files are allowed`, 'warning', 6000);
    });
}

// Permission helpers
window.notifyUnauthorized = function() {
    waitForToastManager(() => {
        window.toastManager.show('You do not have permission to perform this action', 'error', 6000);
    });
}

window.notifySessionExpired = function() {
    waitForToastManager(() => {
        window.toastManager.show('Your session has expired. Please login again.', 'warning', 8000);
    });
}

console.log('Toast helpers loaded successfully');