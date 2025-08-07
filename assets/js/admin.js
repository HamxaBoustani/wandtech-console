/**
 * WandTech Console Admin JavaScript
 *
 * Handles interactivity for the admin settings page, including
 * tab navigation and module activation via AJAX.
 *
 * @version 1.0.0
 */
jQuery(document).ready(function ($) {

    /**
     * Tab Navigation Handler
     *
     * Manages the display of content sections with a smooth fade-in animation.
     * It ensures the opacity transition works reliably on every click.
     */
    $('.nav-tab-wrapper a.nav-tab').on('click', function (e) {
        e.preventDefault();

        const $clickedTab = $(this);
        const targetTabSelector = $clickedTab.attr('href');
        const $targetContent = $(targetTabSelector);

        // Do nothing if the clicked tab is already the active one.
        if ($clickedTab.hasClass('nav-tab-active')) {
            return;
        }

        // Update the active state on the navigation tabs.
        $('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
        $clickedTab.addClass('nav-tab-active');

        // Immediately hide all tab content sections by removing the 'active' class.
        $('.tab-content').removeClass('active');
        
        // Use a minimal timeout. This allows the browser to process the 'display: none'
        // CSS change before we re-introduce the 'active' class, ensuring the
        // fade-in animation triggers correctly every time.
        setTimeout(function() {
            $targetContent.addClass('active');
        }, 10); // 10ms is sufficient.
    });


    /**
     * Module Activation Toggle Handler
     *
     * Sends an AJAX request to activate or deactivate a module when the
     * toggle switch is changed. It shows a loading spinner during the request.
     */
    $('.module-toggle').on('change', function () {
        const $toggleSwitch = $(this);
        const $moduleCard = $toggleSwitch.closest('.module-card');
        const moduleSlug = $toggleSwitch.data('module');
        const isActive = $toggleSwitch.is(':checked');

        // Add the 'loading' class to the card to display the CSS spinner overlay.
        $moduleCard.addClass('loading');
        // Add or remove 'is-active' class for instant visual feedback.
        $moduleCard.toggleClass('is-active', isActive);

        // Perform the AJAX request.
        $.ajax({
            url: wandtech_console_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wandtech_console_toggle_module', // This action must be registered in PHP.
                nonce: wandtech_console_ajax.nonce,
                module: moduleSlug,
                status: isActive
            },
            success: function (response) {
                // On failure, revert the switch and show an alert.
                if (!response.success) {
                    alert('Error: ' + response.data.message);
                    $toggleSwitch.prop('checked', !isActive); // Revert the toggle state.
                    $moduleCard.toggleClass('is-active', !isActive); // Revert the visual state.
                }
                // On success, no action is needed as the UI is already updated.
            },
            error: function () {
                // Handle unexpected server errors.
                alert(wandtech_console_ajax.generic_error);
                $toggleSwitch.prop('checked', !isActive); // Revert the toggle state.
                $moduleCard.toggleClass('is-active', !isActive); // Revert the visual state.
            },
            complete: function () {
                // Always remove the 'loading' class, regardless of success or failure.
                $moduleCard.removeClass('loading');
            }
        });
    });

});