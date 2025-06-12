// Document ready function
$(document).ready(function() {
    // Enable tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Enable popovers
    $('[data-toggle="popover"]').popover();
    
    // Handle file input change
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeTo(500, 0).slideUp(500, function() {
            $(this).remove(); 
        });
    }, 5000);
    
    // Add animation to cards on page load
    $('.card').each(function(index) {
        $(this).delay(100 * index).queue(function(next) {
            $(this).addClass('animate__animated animate__fadeIn');
            next();
        });
    });
    
    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }

    // Handle click on logout links
    $('.logout-link').on('click', function(e) {
        e.preventDefault(); // Prevent the default link behavior
        $('#logoutConfirmModal').modal('show'); // Show the modal
    });
});

// Time ago function
function timeAgo(date) {
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    let interval = Math.floor(seconds / 31536000);
    
    if (interval >= 1) return interval + " year" + (interval === 1 ? "" : "s") + " ago";
    interval = Math.floor(seconds / 2592000);
    if (interval >= 1) return interval + " month" + (interval === 1 ? "" : "s") + " ago";
    interval = Math.floor(seconds / 86400);
    if (interval >= 1) return interval + " day" + (interval === 1 ? "" : "s") + " ago";
    interval = Math.floor(seconds / 3600);
    if (interval >= 1) return interval + " hour" + (interval === 1 ? "" : "s") + " ago";
    interval = Math.floor(seconds / 60);
    if (interval >= 1) return interval + " minute" + (interval === 1 ? "" : "s") + " ago";
    return Math.floor(seconds) + " second" + (seconds === 1 ? "" : "s") + " ago";
}

// Update all time-ago elements
$('.time-ago').each(function() {
    const timestamp = $(this).data('time');
    $(this).text(timeAgo(timestamp));
});