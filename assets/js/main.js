// Dr. Subhash Placement Portal - Client Interactions & UI Helpers

document.addEventListener('DOMContentLoaded', () => {
    // 1. Dynamic Table Search Filter
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('.custom-table tbody tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    // 2. Global Modal Handlers
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    };

    window.openOfferUploadModal = function(studentId, studentName, companyName, appId = 0) {
        const modal = document.getElementById('uploadOfferLetterModal');
        if (!modal) return;
        const stIdEl = document.getElementById('offerStudentId');
        const stNameEl = document.getElementById('offerStudentName');
        const compNameEl = document.getElementById('offerCompanyName');
        const appIdEl = document.getElementById('offerAppId');

        if (stIdEl) stIdEl.value = studentId;
        if (stNameEl) stNameEl.value = studentName || '';
        if (compNameEl) compNameEl.value = companyName && companyName !== 'Assigned Recruiter' ? companyName : '';
        if (appIdEl) appIdEl.value = appId || 0;

        modal.classList.add('active');
    };

    window.triggerOfferUploadModal = function(btn) {
        if (!btn) return;
        const studentId = btn.getAttribute('data-id') || 0;
        const studentName = btn.getAttribute('data-name') || '';
        const companyName = btn.getAttribute('data-company') || '';
        const appId = btn.getAttribute('data-appid') || 0;

        openOfferUploadModal(studentId, studentName, companyName, appId);
    };

    window.updateOfferFileName = function(input) {
        const display = document.getElementById('offerFileNameDisplay');
        if (!display) return;
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            display.innerHTML = '✅ Selected: <strong>' + file.name + '</strong> (' + sizeMB + ' MB)';
            display.style.color = '#065F46';
        } else {
            display.innerHTML = 'Choose file or drag & drop here';
            display.style.color = '#065F46';
        }
    };

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
            }
        });
    });

    // 3. Multi-Color Flash Toast Notification Engine with Live Progress Bar
    window.showToast = function(title, message, type = 'info', durationMs = 7000) {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        // Standardize event types to colors (success, info, warning, danger)
        let themeType = 'info';
        let iconSvg = `🔍`;

        if (type === 'success' || type === 'placed') {
            themeType = 'success';
            iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>`;
        } else if (type === 'warning' || type === 'in-process') {
            themeType = 'warning';
            iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 16 14"/></svg>`;
        } else if (type === 'danger' || type === 'unplaced' || type === 'delete') {
            themeType = 'danger';
            iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`;
        } else if (type === 'filter') {
            themeType = 'info';
            iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>`;
        } else if (type === 'dossier') {
            themeType = 'info';
            iconSvg = `📄`;
        } else if (type === 'export') {
            themeType = 'success';
            iconSvg = `📊`;
        } else {
            themeType = 'info';
            iconSvg = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`;
        }

        const toast = document.createElement('div');
        toast.className = `toast-flash type-${themeType}`;

        toast.innerHTML = `
            <div class="toast-body-flex">
                <div class="toast-icon-badge ${themeType}">${iconSvg}</div>
                <div class="toast-content-text">
                    <h4>${title}</h4>
                    <p>${message}</p>
                </div>
                <button class="toast-close-btn" onclick="this.closest('.toast-flash').remove()">&times;</button>
            </div>
            <div class="toast-progress-track">
                <div class="toast-progress-fill" style="animation-duration: ${durationMs}ms;"></div>
            </div>
        `;

        container.appendChild(toast);

        // Auto Dismiss after duration
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => {
                toast.remove();
            }, 400);
        }, durationMs);
    };

    // 4. Automatic Event Listeners for Filters & Excel Export
    document.querySelectorAll('.select-pill').forEach(selectEl => {
        selectEl.addEventListener('change', function() {
            const label = this.options[this.selectedIndex].text;
            if (window.showToast) {
                window.showToast('🔍 Filter Applied', 'Updating roster for: ' + label, 'filter');
            }
        });
    });

    document.querySelectorAll('a[href*="export_csv"], a[href*="export_attendance"]').forEach(exportBtn => {
        exportBtn.addEventListener('click', function() {
            if (window.showToast) {
                window.showToast('📊 Export Started', 'Downloading Excel CSV spreadsheet report...', 'export');
            }
        });
    });
});

// =========================================================================
// 5. GLOBAL ZOOM LOCK ENGINE (Disables Zoom In & Zoom Out completely)
// =========================================================================
(function lockBrowserZoom() {
    // A. Prevent Ctrl + Mouse Wheel Zoom
    window.addEventListener('wheel', function(e) {
        if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
        }
    }, { passive: false });

    // B. Prevent Keyboard Zoom Shortcuts (Ctrl + '+', Ctrl + '-', Ctrl + '0', etc.)
    window.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && (
            e.key === '+' || 
            e.key === '-' || 
            e.key === '=' || 
            e.key === '_' || 
            e.key === '0' || 
            e.code === 'Minus' || 
            e.code === 'Equal' || 
            e.code === 'NumpadAdd' || 
            e.code === 'NumpadSubtract' || 
            e.code === 'Numpad0' || 
            e.code === 'Digit0' ||
            e.keyCode === 187 || 
            e.keyCode === 189 || 
            e.keyCode === 107 || 
            e.keyCode === 109 || 
            e.keyCode === 48 || 
            e.keyCode === 96
        )) {
            e.preventDefault();
        }
    }, { passive: false });

    // C. Prevent Touch Pinch-to-Zoom on Mobile & Tablets
    window.addEventListener('touchstart', function(e) {
        if (e.touches && e.touches.length > 1) {
            e.preventDefault();
        }
    }, { passive: false });

    window.addEventListener('touchmove', function(e) {
        if (e.touches && e.touches.length > 1) {
            e.preventDefault();
        }
    }, { passive: false });

    // D. Prevent Safari Gesture Scaling
    window.addEventListener('gesturestart', function(e) { e.preventDefault(); }, { passive: false });
    window.addEventListener('gesturechange', function(e) { e.preventDefault(); }, { passive: false });
    window.addEventListener('gestureend', function(e) { e.preventDefault(); }, { passive: false });
})();

// =========================================================================
// 6. GLOBAL KEYBOARD NAVIGATION: ARROW KEYS SCROLLING & ENTER NEXT FIELD
// =========================================================================
(function initKeyboardNavigation() {
    window.addEventListener('keydown', function(e) {
        const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        const isEditingInput = (activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select');

        // A. Smooth Universal Arrow Keys Scrolling (Up, Down, Left, Right)
        if (!isEditingInput && ['ArrowDown', 'ArrowUp', 'ArrowRight', 'ArrowLeft'].includes(e.key)) {
            e.preventDefault();
            
            const activeModalScroll = document.querySelector('.modal-backdrop.active .dossier-scroll-area, .modal.active .dossier-scroll-area, .modal-box');
            const mainContent = document.querySelector('.main-content');
            const scrollableTable = document.querySelector('.table-responsive');

            const verticalStep = 180;
            const horizontalStep = 180;

            if (e.key === 'ArrowDown') {
                if (activeModalScroll) {
                    activeModalScroll.scrollBy({ top: verticalStep, behavior: 'smooth' });
                } else if (mainContent && (mainContent.scrollHeight > mainContent.clientHeight)) {
                    mainContent.scrollBy({ top: verticalStep, behavior: 'smooth' });
                }
                window.scrollBy({ top: verticalStep, behavior: 'smooth' });
                document.documentElement.scrollBy({ top: verticalStep, behavior: 'smooth' });
            } else if (e.key === 'ArrowUp') {
                if (activeModalScroll) {
                    activeModalScroll.scrollBy({ top: -verticalStep, behavior: 'smooth' });
                } else if (mainContent && (mainContent.scrollHeight > mainContent.clientHeight)) {
                    mainContent.scrollBy({ top: -verticalStep, behavior: 'smooth' });
                }
                window.scrollBy({ top: -verticalStep, behavior: 'smooth' });
                document.documentElement.scrollBy({ top: -verticalStep, behavior: 'smooth' });
            } else if (e.key === 'ArrowRight') {
                if (scrollableTable) {
                    scrollableTable.scrollBy({ left: horizontalStep, behavior: 'smooth' });
                } else {
                    window.scrollBy({ left: horizontalStep, behavior: 'smooth' });
                }
            } else if (e.key === 'ArrowLeft') {
                if (scrollableTable) {
                    scrollableTable.scrollBy({ left: -horizontalStep, behavior: 'smooth' });
                } else {
                    window.scrollBy({ left: -horizontalStep, behavior: 'smooth' });
                }
            }
        }

        // B. Enter Key: Move Focus to Next Form Input Field, or Submit Form on Last Input
        if (e.key === 'Enter' && (activeTag === 'input' || activeTag === 'select')) {
            const inputType = document.activeElement.type ? document.activeElement.type.toLowerCase() : '';
            if (inputType !== 'submit' && inputType !== 'button' && inputType !== 'checkbox' && inputType !== 'radio') {
                const currentForm = document.activeElement.form;
                if (currentForm) {
                    const inputs = Array.from(currentForm.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="checkbox"]):not([type="radio"]), select, textarea'));
                    const currentIndex = inputs.indexOf(document.activeElement);
                    if (currentIndex > -1 && currentIndex < inputs.length - 1) {
                        e.preventDefault();
                        inputs[currentIndex + 1].focus();
                    } else if (currentIndex === inputs.length - 1) {
                        e.preventDefault();
                        if (typeof currentForm.requestSubmit === 'function') {
                            currentForm.requestSubmit();
                        } else {
                            currentForm.submit();
                        }
                    }
                }
            }
        }
    });
})();

// 5. Cross-Platform Touchpad & Panel Zoom Control Engine
// Allows keyboard zoom (Ctrl + '+' / Ctrl + '-') while blocking accidental touchpad pinch-to-zoom & Ctrl+Scroll shifts
window.addEventListener('wheel', function(e) {
    if (e.ctrlKey) {
        e.preventDefault();
    }
}, { passive: false });

window.addEventListener('gesturestart', function(e) {
    e.preventDefault();
});

window.addEventListener('gesturechange', function(e) {
    e.preventDefault();
});
