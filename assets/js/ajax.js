// AJAX setup to include CSRF token
$.ajaxSetup({
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    }
});

// Global AJAX error handler
$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    if (jqxhr.status === 401) {
        // Unauthorized - redirect to login
        window.location.href = '../login.php';
    } else if (jqxhr.status === 403) {
        // Forbidden - show error
        alert('You don\'t have permission to perform this action.');
    } else if (jqxhr.status === 500) {
        // Server error
        alert('Server error occurred. Please try again later.');
    }
});

// Function to handle form submissions via AJAX
function submitFormViaAjax(form, successCallback, errorCallback) {
    const formData = new FormData(form);
    
    $.ajax({
        url: form.action,
        type: form.method,
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (typeof successCallback === 'function') {
                    successCallback(response);
                } else {
                    // Default success behavior
                    alert(response.message);
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    }
                }
            } else {
                if (typeof errorCallback === 'function') {
                    errorCallback(response);
                } else {
                    // Default error behavior
                    alert(response.message);
                }
            }
        },
        error: function(xhr, status, error) {
            if (typeof errorCallback === 'function') {
                errorCallback({success: false, message: 'An error occurred: ' + error});
            } else {
                alert('An error occurred. Please try again.');
            }
        }
    });
}

// Bind AJAX form submission
$(document).on('submit', '.ajax-form', function(e) {
    e.preventDefault();
    submitFormViaAjax(this);
});