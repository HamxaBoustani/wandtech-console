/**
 * WandTech Console Admin JavaScript
 *
 * This file handles all the interactivity for the WandTech Console page,
 * including tab navigation, module management, and dynamic modals.
 *
 * @version 3.1.0
 */

jQuery(document).ready(function ($) {

    const ACTIVE_TAB_STORAGE_KEY = 'wandtech_console_active_tab';
    
    // --- STATE MANAGEMENT & HELPERS ---
    let currentFilter = 'all';
    let currentSearchTerm = '';
    let noticeDismissTimer = null;
    const $container = $('.wrap.wandtech-wrap');

    /**
     * A simple debounce function to delay execution of a function.
     */
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    /**
     * A simple HTML escaper to prevent XSS.
     */
    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return '';
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    /**
     * [MODIFIED] Shows a fixed notice aligned with the main content, without scrolling.
     */
    function showAdminNotice(message, type = 'error') {
        const $noticeContainer = $('#wandtech-console-notices');
        if (!$noticeContainer.length) return;
        
        if (noticeDismissTimer) { clearTimeout(noticeDismissTimer); }

        const noticeHtml = `<div class="notice is-dismissible notice-${type}"><p>${message}</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>`;
        
        $noticeContainer.html(noticeHtml);

        // No more scrolling animation is needed.
        
        if (type === 'success') {
            noticeDismissTimer = setTimeout(() => {
                $noticeContainer.find('.notice').fadeOut(500, function() { $(this).remove(); });
            }, 5000);
        }
    }
    
    // [NEW] This function keeps the fixed notice aligned with the main content area.
    function updateNoticePosition() {
        const $wrap = $('.wandtech-wrap');
        const $noticeContainer = $('#wandtech-console-notices');

        if ($wrap.length && $noticeContainer.length) {
            const wrapRect = $wrap[0].getBoundingClientRect();
            $noticeContainer.css({
                left: wrapRect.left + 'px',
                width: wrapRect.width + 'px'
            });
        }
    }
    
    // --- EVENT HANDLERS ---

    /**
     * Handles tab navigation clicks.
     */
    function handleTabClick(e) {
        e.preventDefault();
        const $clickedTab = $(this);
        const targetTabSelector = $clickedTab.attr('href');
        if ($clickedTab.hasClass('nav-tab-active')) return;

        $('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
        $clickedTab.addClass('nav-tab-active');
        $('.tab-content').removeClass('active');

        setTimeout(() => {
            const $targetContent = $(targetTabSelector);
            $targetContent.addClass('active');
            $container.trigger('wandtech:tab_activated', [$targetContent]);
            try { localStorage.setItem(ACTIVE_TAB_STORAGE_KEY, targetTabSelector); } catch (e) {}
        }, 10);
    }
    
    /**
     * Handles the live search input for filtering modules.
     */
    function handleModuleSearch() {
        currentSearchTerm = $(this).val().toLowerCase().trim();
        updateModuleView();
    }

    /**
     * Handles clicks on the status filters (All, Active, Inactive).
     */
    function handleFilterClick(e) {
        e.preventDefault();
        const $link = $(this);
        if ($link.hasClass('current')) return;
        currentFilter = $link.data('filter');
        $('.filter-link').removeClass('current');
        $link.addClass('current');
        updateModuleView();
    }

    /**
     * Handles clicks on the module settings gear icon.
     * Navigates the user to the Settings tab and activates the correct section.
     */
    function handleModuleSettingsClick(e) {
        e.preventDefault();
        const settingsSlug = $(this).data('settings-slug');
        if (!settingsSlug) return;
        
        // 1. Switch to the 'Settings' tab.
        $('.nav-tab[href="#settings"]').trigger('click');
        
        // 2. Find and click the corresponding link in the settings sidebar.
        const $settingsLink = $(`.settings-nav a[href="#${settingsSlug}"]`);
        if ($settingsLink.length) {
            // Use a short timeout to ensure the tab content is visible before clicking.
            setTimeout(() => {
                $settingsLink.trigger('click');
                
                // Optional: Scroll the settings section into view if it's off-screen.
                const $section = $('#section-' + settingsSlug);
                if ($section.length) {
                    $('html, body').animate({
                        scrollTop: $section.offset().top - 100 // Adjust offset as needed
                    }, 300);
                }
            }, 100);
        }
    }

    /**
     * Handles the AJAX request when a module toggle is switched.
     */
    function handleModuleToggle() {
        const $toggleSwitch = $(this);
        const $moduleCard = $toggleSwitch.closest('.module-card');
        const $errorNotice = $moduleCard.find('.module-card-error-notice');
        const moduleSlug = $toggleSwitch.data('module');
        const wantsToActivate = $toggleSwitch.is(':checked');

        $moduleCard.addClass('loading');
        $errorNotice.slideUp(200);

        $.ajax({
            url: wandtech_console_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wandtech_console_toggle_module',
                nonce: wandtech_console_ajax.nonce_toggle,
                module: moduleSlug,
                status: wantsToActivate
            },
            success: (response) => {
                if (response.success) {

                    // [NEW] Smart Reload Logic
                    // Check if the toggled module has a settings page.
                    const hasSettings = wandtech_console_ajax.module_settings_map && wandtech_console_ajax.module_settings_map[moduleSlug];

                    if (hasSettings) {
                        // If it has settings, a full reload is the most robust way
                        // to ensure the Settings Tab UI is perfectly in sync.
                        sessionStorage.setItem('wandtech_console_notice', JSON.stringify({
                            message: response.data.message,
                            type: 'success'
                        }));
                        location.reload();
                    } else {
                        // If no settings, update the UI dynamically without a reload.
                        showAdminNotice(response.data.message, 'success');
                        $moduleCard.toggleClass('is-active', wantsToActivate);
                        $moduleCard.find('.delete-module-link').toggle(!wantsToActivate);
                        // The settings icon doesn't exist for these modules, so no need to toggle it.
                        updateAllStats();
                        $moduleCard.removeClass('loading'); // Manually remove loading here
                    }

                } else {
                    showAdminNotice(response.data.message, 'error');
                    $errorNotice.html(response.data.message).slideDown(200);
                    $toggleSwitch.prop('checked', !wantsToActivate);
                    setTimeout(() => $errorNotice.slideUp(300), 8000);
                    $moduleCard.removeClass('loading');
                }
            },
            error: (jqXHR) => {
                let errorMessage = jqXHR.responseJSON?.data?.message || wandtech_console_ajax.generic_error;
                showAdminNotice(errorMessage, 'error');
                $errorNotice.html(errorMessage).slideDown(200);
                $toggleSwitch.prop('checked', !wantsToActivate);
                setTimeout(() => $errorNotice.slideUp(300), 8000);
                $moduleCard.removeClass('loading');
            },
            complete: () => {
                // The 'complete' callback runs after 'success'.
                // If we are reloading, this part won't run.
                // If we are NOT reloading, we need to update the view.
                updateModuleView();
            }
        });
    }

    // [NEW but necessary] Add this function to your JS file
    function showReloadNotice() {
        try {
            const noticeData = sessionStorage.getItem('wandtech_console_notice');
            if (noticeData) {
                const notice = JSON.parse(noticeData);
                showAdminNotice(notice.message, notice.type);
                sessionStorage.removeItem('wandtech_console_notice');
            }
        } catch (e) { /* sessionStorage might not be available */ }
    }

    /**
     * Centralized handler for the confirmation modal's primary action button.
     */
    function handleConfirmAction() {
        const $button = $(this);
        const action = $button.data('action-type');

        if (action === 'delete-module') {
            performDeleteModule($button);
        }
    }
    
    // --- UI UPDATE FUNCTIONS ---

    /**
     * Central controller for header and empty state visibility in the Modules tab.
     */
    function updateModuleAreaVisibility() {
        const moduleCount = $('.module-card').length;
        
        // [MODIFIED] Simplified logic. Visibility is now controlled by PHP.
        // We just toggle the main containers based on whether any modules exist.
        $('.module-filters, #module-search-input, .header-actions-container').toggle(moduleCount > 0);
        $('.modules-empty-state').toggle(moduleCount === 0);
    }
    
    /**
     * Renders the HTML for a new module card from data object.
     */
    function renderNewModuleCard(moduleData) {
        const name = escapeHtml(moduleData.Name);
        const description = escapeHtml(moduleData.Description);
        const slug = escapeHtml(moduleData.slug);
        const scope = escapeHtml( (moduleData.Scope || 'all').toLowerCase().trim() );
        const version = escapeHtml(moduleData.Version || '1.0.0');
        const author = escapeHtml(moduleData.Author || 'N/A');

        // [MODIFIED] Get the thumbnail URL from the response, or use placeholder.
        const thumbnailUrl = moduleData.thumbnail_url || (wandtech_console_ajax.plugin_url + 'assets/images/module-placeholder.svg');
        const moduleUri = moduleData['Module URI'] ? escapeHtml(moduleData['Module URI']) : '';
        const settingsSlug = moduleData['Settings Slug'] ? escapeHtml(moduleData['Settings Slug']) : '';

        // [MODIFIED] Build the settings icon and details link HTML conditionally.
        let settingsIconHtml = '';
        if (settingsSlug) {
            // Note: The icon is hidden initially because the module is inactive.
            settingsIconHtml = `
                <a href="${escapeHtml(wandtech_console_ajax.admin_url)}admin.php?page=wandtech-console#settings" 
                   class="module-settings-link" 
                   data-settings-slug="${settingsSlug}" 
                   aria-label="Module Settings"
                   style="display:none;">
                    <span class="dashicons dashicons-admin-generic"></span>
                </a>`;
        }

        let detailsLinkHtml = '';
        if (moduleUri) {
            detailsLinkHtml = `| <a href="${moduleUri}" target="_blank" rel="noopener noreferrer">Details</a>`;
        }

        return `
            <div class="module-card" data-scope="${scope}">
                <div class="module-card-thumbnail">
                    <img src="${thumbnailUrl}" alt="${name} thumbnail" loading="lazy">
                    ${settingsIconHtml}
                </div>
                <div class="module-card-content">
                    <div class="module-card-header">
                        <h3>${name}</h3>
                        <label class="switch">
                            <input type="checkbox" class="module-toggle" data-module="${slug}">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="module-card-body">
                        <p>${description}</p>
                        <div class="module-card-error-notice" style="display:none;"></div>
                    </div>
                </div>
                <div class="module-card-footer">
                    <div class="module-meta">
                        <small>Version: ${version}</small>
                        <small>Author: ${author} ${detailsLinkHtml}</small>
                    </div>
                    <div class="module-action-links">
                        <a href="#" class="delete-module-link" 
                           data-module-slug="${slug}" 
                           data-module-name="${name}">
                            Delete
                        </a>
                    </div>
                </div>
                <div class="spinner-overlay"></div>
            </div>`;
    }

    /**
     * Sorts module cards alphabetically by name.
     */
    function sortModuleCards() {
        const $cardsContainer = $('.module-cards');
        const $cards = $cardsContainer.children('.module-card');
        $cards.sort(function(a, b) {
            const nameA = $(a).find('h3').text().toUpperCase();
            const nameB = $(b).find('h3').text().toUpperCase();
            return (nameA < nameB) ? -1 : (nameA > nameB) ? 1 : 0;
        });
        $cards.detach().appendTo($cardsContainer);
    }

    function updateDashboardStats() {
        const $dashboardTab = $('#dashboard');
        if (!$dashboardTab.length) return;
        const $cards = $('#modules .module-card');
        const totalCount = $cards.length;
        const activeCount = $cards.filter('.is-active').length;
        const inactiveCount = totalCount - activeCount;
        const perfCount = $cards.filter('.is-active[data-scope="admin"]').length;
        const animateCounter = ($el, newValue) => { if ($el.text() !== newValue.toString()) { $el.fadeOut(150, function() { $(this).text(newValue).fadeIn(150); }); } };
        animateCounter($('#dashboard-stat-total'), totalCount);
        animateCounter($('#dashboard-stat-active'), activeCount);
        animateCounter($('#dashboard-stat-inactive'), inactiveCount);
        animateCounter($('#dashboard-stat-performance'), perfCount);
        const $explainer = $('#dashboard-performance-explainer');
        if (perfCount > 0) {
            const newText = `To keep your site fast for visitors, we prevented ${perfCount} admin-specific module${perfCount > 1 ? 's' : ''} from loading on the frontend.`;
            $('#dashboard-performance-text').text(newText);
            $explainer.fadeIn(300);
        } else {
            $explainer.fadeOut(300);
        }
    }
    
    function updateFilterCounts() {
        const $cards = $('#modules .module-card');
        const totalCount = $cards.length;
        const activeCount = $cards.filter('.is-active').length;
        const inactiveCount = totalCount - activeCount;
        $('#filter-count-all').text(`(${totalCount})`);
        $('#filter-count-active').text(`(${activeCount})`);
        $('#filter-count-inactive').text(`(${inactiveCount})`);
    }
    
    function updateAllStats() {
        updateFilterCounts();
        updateDashboardStats();
    }
    
    function updateModuleView() {
        const $cards = $('#modules .module-card');
        const $noResultsMessage = $('#modules .no-results-message');
        const moduleCount = $cards.length;
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
        $noResultsMessage.toggle(visibleCount === 0 && moduleCount > 0);
    }
    
    // --- INITIALIZATION FUNCTIONS ---

    function initializeDeactivationNotices() {
        const notices = wandtech_console_ajax.deactivation_notices || [];
        if (notices.length > 0) {
            const combinedMessage = notices.join('<br><br>');
            showAdminNotice(combinedMessage, 'warning');
        }
    }

    function initializeTabs() {
        let lastActiveTab = null;
        try { lastActiveTab = localStorage.getItem(ACTIVE_TAB_STORAGE_KEY); } catch (e) {}
        const $targetTabLink = lastActiveTab ? $(`.nav-tab-wrapper a.nav-tab[href="${lastActiveTab}"]`) : null;

        if ($targetTabLink && $targetTabLink.length && $targetTabLink.is(':visible')) {
            $targetTabLink.trigger('click');
        } else {
            const $firstTabLink = $('.nav-tab-wrapper a.nav-tab:visible').first();
            if ($firstTabLink.length) {
                $firstTabLink.trigger('click');
            }
        }
    }
    
    function initializeModals() {
        $('body').on('click', '.wandtech-modal-overlay', function(e) {
            if (e.target === this) { $(this).fadeOut(200); }
        }).on('click', '.wandtech-modal-close', function(e) {
            e.preventDefault();
            $(this).closest('.wandtech-modal-overlay').fadeOut(200);
        });

        // Event delegation for dynamically added buttons
        $container.on('click', '#install-module-button, #install-module-button-empty', () => $('#install-module-modal').fadeIn(200));
        $container.on('click', '#scaffold-module-button, #scaffold-module-button-empty', () => $('#scaffold-module-modal').fadeIn(200).find('#new_module_slug').trigger('focus'));
        
        $('#install-module-modal').find('form').on('submit', handleInstallFormSubmit);
        $('#scaffold-module-modal').find('form').on('submit', handleScaffoldFormSubmit);

        $('#install-module-modal').on('change', 'input[type="file"]', (e) => $('#install-module-submit').prop('disabled', e.currentTarget.files.length === 0));
        $('#scaffold-module-modal').on('input', 'input[required]', debounce(() => validateScaffolderForm($('#scaffold-module-modal')), 250));

        // Logic to toggle the settings slug field visibility.
        $('#scaffold-module-modal').on('change', '#new_module_has_settings', function() {
            const $wrapper = $('#settings-slug-wrapper');
            const $slugInput = $('#new_module_settings_slug');
            const moduleSlug = $('#new_module_slug').val();
            
            if ($(this).is(':checked')) {
                $slugInput.val(moduleSlug); // Auto-fill with module slug
                $wrapper.slideDown(200);
            } else {
                $wrapper.slideUp(200);
            }
        });
        
        // Sync settings slug with module slug automatically.
        $('#scaffold-module-modal').on('input', '#new_module_slug', function() {
            if ($('#new_module_has_settings').is(':checked')) {
                $('#new_module_settings_slug').val($(this).val());
            }
        });
        
        const $confirmModal = $('#delete-module-modal');
        if ($confirmModal.length) {
            $container.on('click', '.delete-module-link', (e) => openDeleteConfirmModal(e, $confirmModal));
            $confirmModal.find('#cancel-delete-button').on('click', () => $confirmModal.fadeOut(200));
            $confirmModal.find('#confirm-delete-button').on('click', handleConfirmAction);
        }
    }

    // [REMOVED] The toggleDeveloperFeatures function is no longer needed.
    // The visibility of the scaffolder button is now entirely controlled by PHP.

    function toggleDeveloperTabs(isDevMode) {
        // This function might be used in the future, so we can keep it.
        // const devTabSlugs = ['system-logs']; 
        // devTabSlugs.forEach(slug => {
        //     const $tabLink = $(`.nav-tab[data-tab-slug="${slug}"]`);
        //     if (isDevMode) {
        //         $tabLink.fadeIn(200);
        //     } else {
        //         if ($tabLink.hasClass('nav-tab-active')) {
        //             $('.nav-tab[data-tab-slug="dashboard"]').trigger('click');
        //         }
        //         $tabLink.fadeOut(200);
        //     }
        // });
    }

    // --- AJAX HANDLERS & MODAL LOGIC (REFACTORED) ---
    
    function handleSuccessfullModuleAddition(response) {
        const $modal = $('.wandtech-modal-overlay:visible');
        if ($modal.length) $modal.fadeOut(200);
        
        showAdminNotice(response.data.message, 'success');

        const newCardHtml = renderNewModuleCard(response.data.new_module);
        $(newCardHtml).hide().appendTo('.module-cards').fadeIn(400);
        
        updateModuleAreaVisibility();
        sortModuleCards();
        updateAllStats();
        updateModuleView();
        
        const $form = $modal.find('form');
        if ($form.length) $form[0].reset();
    }
    
    function handleInstallFormSubmit(e) {
        e.preventDefault();
        const $form = $(this), $modal = $form.closest('.wandtech-modal-overlay'), $submit = $form.find('button[type="submit"]'), $spinner = $form.find('.spinner'), $noticeArea = $modal.find('.wandtech-modal-notice');
        const formData = new FormData(this);
        formData.append('action', 'wandtech_console_install_module');
        formData.append('nonce', wandtech_console_ajax.nonce_install);
        $submit.prop('disabled', true).text(wandtech_console_ajax.installing_text);
        $spinner.addClass('is-active');
        $noticeArea.html('').slideUp(200);
        $.ajax({
            url: wandtech_console_ajax.ajax_url, type: 'POST', data: formData, processData: false, contentType: false,
            success: (response) => {
                if (response.success && response.data.new_module) {
                    handleSuccessfullModuleAddition(response);
                }
            },
            error: (jqXHR) => {
                const errorMsg = jqXHR.responseJSON?.data?.message || wandtech_console_ajax.generic_error;
                $noticeArea.html(`<div class="notice notice-error is-alt" style="margin:0;"><p>${errorMsg}</p></div>`).slideDown(200);
            },
            complete: () => {
                $submit.prop('disabled', false).text(wandtech_console_ajax.install_now_text);
                $spinner.removeClass('is-active');
            }
        });
    }

    function validateScaffolderForm($modal) {
        const $submit = $modal.find('button[type="submit"]');
        const slug = $modal.find('#new_module_slug').val().trim();
        const description = $modal.find('#new_module_description').val().trim();
        const isSlugValid = /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug);
        $submit.prop('disabled', !(isSlugValid && description.length > 0));
    }

    function handleScaffoldFormSubmit(e) {
        e.preventDefault();
        const $form = $(this), $modal = $form.closest('.wandtech-modal-overlay'), $submit = $form.find('button[type="submit"]'), $spinner = $form.find('.spinner'), $noticeArea = $modal.find('.wandtech-modal-notice');
        $submit.prop('disabled', true).text(wandtech_console_ajax.creating_text);
        $spinner.addClass('is-active');
        $noticeArea.html('').slideUp(200);
        $.ajax({
            url: wandtech_console_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'wandtech_console_scaffold_module',
                nonce: wandtech_console_ajax.nonce_scaffold,
                module_slug: $modal.find('#new_module_slug').val().trim(),
                module_description: $modal.find('#new_module_description').val().trim(),
                module_scope: $modal.find('#new_module_scope').val(),
                module_requires: $modal.find('#new_module_requires').val().trim()
            },
            success: (response) => {
                if (response.success && response.data.new_module) {
                    handleSuccessfullModuleAddition(response);
                }
            },
            error: (jqXHR) => {
                const errorMsg = jqXHR.responseJSON?.data?.message || wandtech_console_ajax.generic_error;
                $noticeArea.html(`<div class="notice notice-error is-alt" style="margin:0;"><p>${errorMsg}</p></div>`).slideDown(200);
            },
            complete: () => {
                $submit.prop('disabled', false).text(wandtech_console_ajax.create_now_text);
                $spinner.removeClass('is-active');
                validateScaffolderForm($modal);
            }
        });
    }
    
    function openDeleteConfirmModal(e, $modal) {
        e.preventDefault();
        const $link = $(e.currentTarget);
        $modal.find('h2').text('Delete Module?');
        $modal.find('#delete-modal-text').html(`You are about to permanently delete the "<strong>${escapeHtml($link.data('module-name'))}</strong>" module and all of its files.`);
        $modal.find('#confirm-delete-button').text('Yes, Delete Module').data('action-type', 'delete-module').data('module-slug', $link.data('module-slug'));
        $modal.fadeIn(200);
    }
    
    function performDeleteModule($button) {
        const moduleSlug = $button.data('module-slug'), $modal = $button.closest('.wandtech-modal-overlay'), $spinner = $modal.find('.spinner');
        const $card = $(`.module-card:has(.delete-module-link[data-module-slug="${moduleSlug}"])`);
        $spinner.addClass('is-active');
        $button.prop('disabled', true);
        $.ajax({
            url: wandtech_console_ajax.ajax_url, type: 'POST', data: { action: 'wandtech_console_delete_module', nonce: wandtech_console_ajax.nonce_delete, module: moduleSlug },
            success: (res) => {
                if (res.success) {
                    showAdminNotice(res.data.message, 'success');
                    if ($card.length) {
                        $card.fadeOut(400, function() {
                            $(this).remove();
                            updateModuleAreaVisibility();
                            updateAllStats();
                            updateModuleView();
                        });
                    }
                } else { showAdminNotice(res.data.message, 'error'); }
            },
            error: (xhr) => { showAdminNotice(xhr.responseJSON?.data?.message || wandtech_console_ajax.generic_error, 'error'); },
            complete: () => { $modal.fadeOut(200); $button.prop('disabled', false); $spinner.removeClass('is-active'); }
        });
    }
    
    function initializeSettingsTab() {
        const $settingsTab = $('#settings');
        if (!$settingsTab.length) return;

        // --- COMMON VARIABLES ---
        const $saveButton = $settingsTab.find('#save-wandtech-settings-button');
        const $navLinks = $settingsTab.find('.settings-nav a');
        const $navItems = $settingsTab.find('.settings-nav li');
        const $searchBox = $settingsTab.find('#wandtech-settings-search');
        const $noResults = $settingsTab.find('.settings-nav .no-results-message');
        const $sections = $settingsTab.find('.settings-section');
        const SETTINGS_SECTION_KEY = 'wandtech_settings_active_section';
        let initialSettingsState = null;

        // --- DIRTY STATE LOGIC ---
        const getCurrentSettingsState = () => {
            const currentState = {
                developer_mode_enabled: $settingsTab.find('#developer_mode_enabled').is(':checked'),
                enable_full_cleanup: $settingsTab.find('#enable_full_cleanup').is(':checked'),
            };
            $container.trigger('wandtech:collect_settings_state', [currentState]);
            return currentState;
        };

        const checkForUnsavedChanges = () => {
            if (initialSettingsState === null) return;
            const currentState = getCurrentSettingsState();
            const hasChanges = JSON.stringify(initialSettingsState) !== JSON.stringify(currentState);
            $saveButton.prop('disabled', !hasChanges);
            $saveButton.toggleClass('has-unsaved-changes', hasChanges);
        };

        $saveButton.on('click', () => {
            const $spinner = $saveButton.siblings('.spinner');
            const settingsData = getCurrentSettingsState();
            $spinner.addClass('is-active');
            $saveButton.prop('disabled', true);
            $saveButton.removeClass('has-unsaved-changes');

            $.ajax({
                url: wandtech_console_ajax.ajax_url, type: 'POST',
                data: { action: 'wandtech_console_save_settings', nonce: wandtech_console_ajax.nonce_settings, settings: settingsData },
                success: (res) => {
                    if (res.success) {
                        // [MODIFIED] We now reload the page to apply PHP-based changes.
                        sessionStorage.setItem('wandtech_console_notice', JSON.stringify({
                            message: res.data.message,
                            type: 'success'
                        }));
                        location.reload();
                    } else { 
                        showAdminNotice(res.data.message, 'error');
                    }
                },
                error: (xhr) => { showAdminNotice(xhr.responseJSON?.data?.message || wandtech_console_ajax.generic_error, 'error'); },
                complete: () => { 
                    // This part will only run on error, as success causes a reload.
                    $spinner.removeClass('is-active'); 
                    checkForUnsavedChanges(); 
                }
            });
        });

        $settingsTab.on('change input', 'input, select', debounce(checkForUnsavedChanges, 250));

        // --- SECTION NAVIGATION & SEARCH LOGIC ---
        $searchBox.on('input', debounce(function() {
            const searchTerm = $(this).val().toLowerCase().trim();
            let visibleCount = 0;
            $navItems.not($noResults).each(function() {
                const $item = $(this);
                const itemText = $item.find('a').text().toLowerCase();
                if (itemText.includes(searchTerm)) {
                    $item.show();
                    visibleCount++;
                } else { $item.hide(); }
            });
            $noResults.toggle(visibleCount === 0);
        }, 200));

        $navLinks.on('click', function(e) {
            e.preventDefault();
            const targetId = $(this).attr('href').substring(1);
            $navItems.removeClass('active');
            $(this).parent('li').addClass('active');
            $sections.removeClass('active');
            $('#section-' + targetId).addClass('active');
            try { localStorage.setItem(SETTINGS_SECTION_KEY, targetId); } catch(e) {}
        });

        // --- INITIALIZATION LOGIC FOR THE TAB ---
        const initializeState = () => {
            if (initialSettingsState === null) {
                initialSettingsState = getCurrentSettingsState();
                checkForUnsavedChanges();
            }
            const lastActiveSection = localStorage.getItem(SETTINGS_SECTION_KEY);
            const $targetLink = lastActiveSection ? $settingsTab.find(`.settings-nav a[href="#${lastActiveSection}"]`) : null;
            if ($targetLink && $targetLink.length && $targetLink.is(':visible')) {
                $targetLink.trigger('click');
            } else {
                $settingsTab.find('.settings-nav a').first().trigger('click');
            }
        };
        
        $container.on('wandtech:tab_activated', (event, $activeTab) => {
            if ($activeTab.attr('id') === 'settings') {
                initializeState();
            }
        });

        if ($settingsTab.is('.active')) {
            setTimeout(initializeState, 100);
        }
    }
    
    // --- MAIN INITIALIZATION ---

    $container.on('click', '.nav-tab-wrapper a.nav-tab', handleTabClick);
    $container.on('change', '.module-toggle', debounce(handleModuleToggle, 300));
    $container.on('input', '#module-search-input', debounce(handleModuleSearch, 250));
    $container.on('click', '.filter-link', handleFilterClick);

    // Event listener for the settings icon, delegated to the main container.
    $container.on('click', '.module-settings-link', handleModuleSettingsClick);

    $container.on('click', '.notice-dismiss', function(e) {
        e.preventDefault();
        $(this).closest('.notice').fadeOut(300, function() { $(this).remove(); });
    });

    // [NEW] Call the position updater on load and on window resize.
    updateNoticePosition();
    $(window).on('resize', debounce(updateNoticePosition, 100));

    showReloadNotice();
    initializeDeactivationNotices();
    // [REMOVED] toggleDeveloperFeatures() is no longer called here.
    initializeTabs();
    initializeModals();
    initializeSettingsTab();
    
    if ($('#modules').length) {
        updateAllStats();
        updateModuleAreaVisibility(); 
    }
});