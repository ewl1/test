/**
 * Downloads Module Admin JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    // =========================================================================
    // Tab Switching
    // =========================================================================

    // Handle Bootstrap tab events
    var tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');

    tabButtons.forEach(function(tab) {
        tab.addEventListener('show.bs.tab', function(event) {
            // Optional: Add custom behavior when tabs are switched
            // console.log('Switching to tab:', event.target.getAttribute('aria-controls'));
        });

        tab.addEventListener('shown.bs.tab', function(event) {
            // Optional: Add behavior after tabs have switched
            // Useful for resizing/refreshing content
        });
    });

    // =========================================================================
    // Form Validation
    // =========================================================================

    var downloadsForms = document.querySelectorAll('.downloads-form');

    downloadsForms.forEach(function(form) {
        // Validate on submit
        form.addEventListener('submit', function(event) {
            // Check for basic validation
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // =========================================================================
    // File Input Display Enhancement
    // =========================================================================

    var fileInputs = document.querySelectorAll('input[type="file"]');

    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                var fileName = this.files[0].name;
                var fileSize = this.files[0].size;

                // Create or update file info display
                var infoElement = this.parentElement.querySelector('.file-info');
                if (!infoElement) {
                    infoElement = document.createElement('div');
                    infoElement.className = 'file-info form-text mt-2';
                    this.parentElement.appendChild(infoElement);
                }

                var fileSelected = (window.downloadsTranslations && window.downloadsTranslations.fileSelected) || 'Pasirinktas';
                infoElement.innerHTML = '<i class="fa-solid fa-check text-success"></i> ' +
                    fileSelected + ': <strong>' + escapeHtml(fileName) + '</strong> ' +
                    '(' + formatFileSize(fileSize) + ')';
            }
        });
    });

    // =========================================================================
    // Settings Form Validation
    // =========================================================================

    var settingsForm = document.querySelector('form input[name="action"][value="save_settings"]');
    if (settingsForm) {
        settingsForm = settingsForm.closest('form');

        // Validate max file size
        var maxSizeInput = settingsForm.querySelector('input[name="downloads_max_file_size"]');
        if (maxSizeInput) {
            maxSizeInput.addEventListener('change', function() {
                var value = parseInt(this.value, 10);
                if (value < 1048576) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
        }

        // Validate max uploads per user
        var maxUploadsInput = settingsForm.querySelector('input[name="downloads_max_uploads_per_user"]');
        if (maxUploadsInput) {
            maxUploadsInput.addEventListener('change', function() {
                var value = parseInt(this.value, 10);
                if (value < 1) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
        }
    }

    // =========================================================================
    // Category/Download Edit Mode Indicators
    // =========================================================================

    // Add visual feedback for edit forms
    var editCatId = document.querySelector('input[name="download_cat_id"]');
    if (editCatId && editCatId.value) {
        var editContainer = editCatId.closest('.downloads-form-section');
        if (editContainer) {
            var titleElement = editContainer.querySelector('h2');
            if (titleElement && !titleElement.classList.contains('edit-mode-indicator')) {
                titleElement.classList.add('edit-mode-indicator');
            }
        }
    }

    // =========================================================================
    // Modal Confirmation Helper
    // =========================================================================

    // Enhance delete confirmations with better UX
    var deleteButtons = document.querySelectorAll('button[type="submit"][class*="outline-danger"]');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            var form = this.closest('form');
            var confirmMessage = form.getAttribute('onsubmit');
            // onsubmit already handles confirm, so this is just for tracking
        });
    });

    // =========================================================================
    // Helper Functions
    // =========================================================================

    /**
     * Format file size in human readable format
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    /**
     * Escape HTML special characters
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // =========================================================================
    // Source Type Toggle (for Downloads form)
    // =========================================================================

    var sourceTypeRadios = document.querySelectorAll('input[name="source_type"]');
    if (sourceTypeRadios.length > 0) {
        var panelFile = document.getElementById('panel-file');
        var panelUrl = document.getElementById('panel-url');

        if (panelFile && panelUrl) {
            var fileInput = panelFile.querySelector('input[type="file"]');
            var urlInput = panelUrl.querySelector('input[type="url"]');

            function toggleSourceType() {
                var isUrl = document.getElementById('src-url').checked;

                // Toggle panel visibility
                panelFile.style.display = isUrl ? 'none' : '';
                panelUrl.style.display = isUrl ? '' : 'none';

                // Update required attributes
                if (fileInput) {
                    fileInput.required = !isUrl && !fileInput.dataset.existing;
                }
                if (urlInput) {
                    urlInput.required = isUrl;
                }
            }

            // Set initial state
            if (fileInput && !document.getElementById('edit_dl')) {
                fileInput.dataset.existing = '';
            } else if (fileInput) {
                fileInput.dataset.existing = '1';
            }

            // Add event listeners
            sourceTypeRadios.forEach(function(radio) {
                radio.addEventListener('change', toggleSourceType);
            });

            // Initial toggle
            toggleSourceType();
        }
    }

    // =========================================================================
    // Module Panel Support
    // =========================================================================

    document.querySelectorAll('[data-sdk-module="downloads"]').forEach(function () {
        // Frontend downloads module functionality
    });
});
