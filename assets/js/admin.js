/**
 * WandTech Console Admin JavaScript
 *
 * @version 1.6.3 - Fixed delete module functionality.
 */
jQuery(document).ready(function ($) {

    const ACTIVE_TAB_STORAGE_KEY = 'wandtech_console_active_tab';
    
    // --- STATE MANAGEMENT ---
    let currentFilter = 'all';
    let currentSearchTerm = '';

    // --- HELPER FUNCTIONS ---

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    function showAdminNotice(message, type = 'error') {
        $('.wandtech-ajax-notice').remove();
        const noticeHtml = `<div class="notice is-dismissible wandtech-ajax-notice notice-${type}"><p>${message}</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>`;
        $('.wandtech-wrap h1').after(noticeHtml);
        $('.wandtech-ajax-notice .notice-dismiss').on('click', function(e) { e.preventDefault(); $(this).closest('.notice').fadeOut(300, function() { $(this).remove(); }); });
    }

    // --- TAB NAVIGATION ---

    function handleTabClick(e) {
        e.preventDefault();
        const $clickedTab = $(this);
        const targetTabSelector = $clickedTab.attr('href');
        if ($clickedTab.hasClass('nav-tab-active')) return;
        $('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
        $clickedTab.addClass('nav-tab-active');
        $('.tab-content').removeClass('active');
        setTimeout(() => {
            $(targetTabSelector).addClass('active');
            try { localStorage.setItem(ACTIVE_TAB_STORAGE_KEY, targetTabSelector); } catch (e) {}
        }, 10);
    }

    function initializeTabs() {
        let lastActiveTab = null;
        try { lastActiveTab = localStorage.getItem(ACTIVE_TAB_STORAGE_KEY); } catch (e) {}
        const $targetTab = lastActiveTab ? $('.nav-tab-wrapper a.nav-tab[href="' + lastActiveTab + '"]') : null;
        if ($targetTab && $targetTab.length) {
            $targetTab.trigger('click');
        } else {
            $('.tab-content.active').css('opacity', 1);
        }
    }

    // --- MODULE MANAGEMENT LOGIC ---

    function updateFilterCounts() {
        const $cards = $('#modules .module-card');
        const totalCount = $cards.length;
        const activeCount = $cards.filter('.is-active').length;
        const inactiveCount = totalCount - activeCount;
        $('#filter-count-all').text(`(${totalCount})`);
        $('#filter-count-active').text(`(${activeCount})`);
        $('#filter-count-inactive').text(`(${inactiveCount})`);
    }

    function updateModuleView() {
        const $cards = $('#modules .module-card');
        const $noResultsMessage = $('#modules .no-results-message');
        let visibleCount = 0;
        $cards.each(function() {
            const $card = $(this);
            const isActive = $card.hasClass('is-active');
            const moduleName = $card.find('h3').text().toLowerCase();
            const moduleDescription = $card.find('.module-card-body p').text().toLowerCase();
            const statusMatch = (currentFilter === 'all') || (currentFilter === 'active' && isActive) || (currentFilter === 'inactive' && !isActive);
            const searchMatch = (currentSearchTerm === '') || moduleName.includes(currentSearchTerm) || moduleDescription.includes(currentSearchTerm);
            if (statusMatch && searchMatch) {
                $card.show();
                visibleCount++;
            } else {
                $card.hide();
            }
        });
        $noResultsMessage.toggle(visibleCount === 0);
    }

    function handleFilterClick(e) {
        e.preventDefault();
        const $clickedFilter = $(this);
        if ($clickedFilter.hasClass('current')) return;
        currentFilter = $clickedFilter.data('filter');
        $('.filter-link').removeClass('current');
        $clickedFilter.addClass('current');
        updateModuleView();
    }

    function handleModuleSearch() {
        currentSearchTerm = $(this).val().toLowerCase().trim();
        updateModuleView();
    }
    
    function handleModuleToggle() {
        const $toggleSwitch = $(this);
        const $moduleCard = $toggleSwitch.closest('.module-card');
        const moduleSlug = $toggleSwitch.data('module');
        const wantsToActivate = $toggleSwitch.is(':checked');
        $moduleCard.addClass('loading');
        $.ajax({
            url: wandtech_console_ajax.ajax_url, type: 'POST',
            data: { action: 'wandtech_console_toggle_module', nonce: wandtech_console_ajax.nonce_toggle, module: moduleSlug, status: wantsToActivate },
            success: (response) => {
                if (response.success) {
                    $moduleCard.toggleClass('is-active', wantsToActivate);
                    $moduleCard.find('.delete-module-link').toggle(!wantsToActivate);
                    updateFilterCounts();
                } else {
                    showAdminNotice(response.data.message, 'error');
                    $toggleSwitch.prop('checked', !wantsToActivate);
                }
            },
            error: (jqXHR) => {
                let errorMessage = jqXHR.responseJSON?.data?.message || wandtech_console_ajax.generic_error;
                showAdminNotice(errorMessage, 'error');
                $toggleSwitch.prop('checked', !wantsToActivate);
            },
            complete: () => {
                $moduleCard.removeClass('loading');
                updateModuleView();
            }
        });
    }

    function handleDeleteModuleClick(e) {
        e.preventDefault();
        const $link = $(this);
        const moduleSlug = $link.data('module');
        const $card = $link.closest('.module-card');
        const moduleName = $card.find('h3').text();
        if (!confirm(`Are you sure you want to permanently delete the "${moduleName}" module?`)) return;
        $card.addClass('loading');
        $.ajax({
            url: wandtech_console_ajax.ajax_url, type: 'POST',
            data: { action: 'wandtech_console_delete_module', nonce: wandtech_console_ajax.nonce_delete, module: moduleSlug },
            success: (response) => {
                if (response.success) {
                    showAdminNotice(response.data.message, 'success');
                    $card.fadeOut(400, function() {
                        $(this).remove();
                        updateFilterCounts();
                        updateModuleView();
                    });
                } else {
                    showAdminNotice(response.data.message, 'error');
                    $card.removeClass('loading');
                }
            },
            error: (jqXHR) => {
                let errorMessage = jqXHR.responseJSON?.data?.message || wandtech_console_ajax.generic_error;
                showAdminNotice(errorMessage, 'error');
                $card.removeClass('loading');
            }
        });
    }

    // --- MODULE INSTALLER LOGIC ---
    function initializeModuleInstaller() {
        const $installButton = $('#install-module-button');
        if (!$installButton.length) return;
        const $modal = $('#install-module-modal'), $form = $('#install-module-form'), $submitButton = $('#install-module-submit'), $fileInput = $('#module_zip_file'), $spinner = $modal.find('.spinner'), $noticeArea = $modal.find('.wandtech-modal-notice');
        function openInstallerModal() { $modal.fadeIn(200); }
        function closeInstallerModal() { $modal.fadeOut(200); resetModalForm(); }
        function handleInstallFormSubmit(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'wandtech_console_install_module');
            formData.append('nonce', wandtech_console_ajax.nonce_install);
            $submitButton.prop('disabled', true).text(wandtech_console_ajax.installing_text);
            $spinner.addClass('is-active');
            $noticeArea.slideUp(200);
            $.ajax({
                url: wandtech_console_ajax.ajax_url, type: 'POST', data: formData, processData: false, contentType: false,
                success: (response) => {
                    if (response.success) {
                        displayModalNotice(response.data.message, 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        displayModalNotice(response.data.message, 'error');
                        resetModalButtons();
                    }
                },
                error: (jqXHR) => {
                    let errorMessage = jqXHR.responseJSON?.data?.message || wandtech_console_ajax.generic_error;
                    displayModalNotice(errorMessage, 'error');
                    resetModalButtons();
                }
            });
        }
        function displayModalNotice(message, type) { $noticeArea.html(`<p class="notice notice-${type} is-alt">${message}</p>`).slideDown(200); }
        function resetModalButtons() { $submitButton.prop('disabled', $fileInput[0].files.length === 0).text(wandtech_console_ajax.install_now_text); $spinner.removeClass('is-active'); }
        function resetModalForm() { $form[0].reset(); $noticeArea.hide().empty(); resetModalButtons(); }
        $installButton.on('click', openInstallerModal);
        $('body').on('click', '#install-module-modal', function(e) { if (e.target === this) closeInstallerModal(); });
        $('body').on('click', '#install-module-modal .wandtech-modal-close', closeInstallerModal);
        $('body').on('change', '#module_zip_file', () => $submitButton.prop('disabled', $fileInput[0].files.length === 0));
        $('body').on('submit', '#install-module-form', handleInstallFormSubmit);
    }
    
    // --- EVENT LISTENERS & INITIALIZATION ---
    
    const $container = $('.wrap.wandtech-wrap');
    
    $container.on('click', '.nav-tab-wrapper a.nav-tab', handleTabClick);
    $container.on('change', '.module-toggle', debounce(handleModuleToggle, 300));
    $container.on('input', '#module-search-input', debounce(handleModuleSearch, 250));
    $container.on('click', '.filter-link', handleFilterClick);
    $container.on('click', '#install-module-button', () => { /* Now handled by initializeModuleInstaller */ });

    // ===== THIS LINE WAS MISSING AND IS NOW RESTORED =====
    $container.on('click', '.delete-module-link', handleDeleteModuleClick);
    
    initializeTabs();
    initializeModuleInstaller();
    
    if ($('#modules').length) {
        updateFilterCounts();
    }
});