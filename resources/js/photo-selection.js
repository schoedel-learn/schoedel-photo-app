/**
 * Photo Selection Management
 */

class PhotoSelection {
    constructor() {
        this.selectedPhotos = new Set();
        this.init();
    }

    init() {
        this.setupElements();
        this.setupEvents();
        this.loadSelections();
    }

    setupElements() {
        this.selectionCounter = document.getElementById('selection-counter');
        this.selectedCount = document.getElementById('selected-count');
        this.selectAllBtn = document.getElementById('select-all-btn');
        this.clearBtn = document.getElementById('clear-selection-btn');
        this.filterSelect = document.getElementById('filter-select');
        this.sortSelect = document.getElementById('sort-select');
    }

    setupEvents() {
        // Checkbox toggle
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('photo-checkbox')) {
                const photoId = parseInt(e.target.dataset.photoId);
                this.toggleSelection(photoId);
            }
        });

        // Select all
        this.selectAllBtn.addEventListener('click', () => {
            const allCheckboxes = document.querySelectorAll('.photo-checkbox');
            const allSelected = Array.from(allCheckboxes).every(cb => cb.checked);
            
            allCheckboxes.forEach(cb => {
                if (!allSelected) {
                    cb.checked = true;
                    const photoId = parseInt(cb.dataset.photoId);
                    if (!this.selectedPhotos.has(photoId)) {
                        this.toggleSelection(photoId, false); // Don't update UI, we'll do it in batch
                    }
                } else {
                    cb.checked = false;
                    const photoId = parseInt(cb.dataset.photoId);
                    if (this.selectedPhotos.has(photoId)) {
                        this.toggleSelection(photoId, false);
                    }
                }
            });
            this.updateUI();
        });

        // Clear selections
        this.clearBtn.addEventListener('click', () => {
            this.clearAll();
        });

        // Filter
        this.filterSelect.addEventListener('change', () => {
            this.applyFilter();
        });

        // Sort
        this.sortSelect.addEventListener('change', () => {
            this.applySort();
        });
    }

    async toggleSelection(photoId, updateUI = true) {
        const wasSelected = this.selectedPhotos.has(photoId);
        const checkbox = document.querySelector(`.photo-checkbox[data-photo-id="${photoId}"]`);
        const photoItem = checkbox.closest('.photo-item');

        try {
            const response = await fetch(
                window.galleryData.toggleSelectionUrl.replace(':id', photoId),
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.galleryData.csrfToken,
                    },
                }
            );

            const data = await response.json();

            if (data.success) {
                if (data.selected) {
                    this.selectedPhotos.add(photoId);
                    checkbox.checked = true;
                    photoItem.classList.add('selected');
                    photoItem.querySelector('.selected-indicator')?.classList.remove('hidden');
                } else {
                    this.selectedPhotos.delete(photoId);
                    checkbox.checked = false;
                    photoItem.classList.remove('selected');
                    photoItem.querySelector('.selected-indicator')?.classList.add('hidden');
                }

                if (updateUI) {
                    this.updateUI();
                    this.applyFilter(); // Reapply filter in case filtering by selection
                }
            }
        } catch (error) {
            console.error('Error toggling selection:', error);
        }
    }

    async clearAll() {
        try {
            const response = await fetch(window.galleryData.clearSelectionUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': window.galleryData.csrfToken,
                },
            });

            const data = await response.json();

            if (data.success) {
                this.selectedPhotos.clear();
                document.querySelectorAll('.photo-checkbox').forEach(cb => {
                    cb.checked = false;
                });
                document.querySelectorAll('.photo-item').forEach(item => {
                    item.classList.remove('selected');
                    item.querySelector('.selected-indicator')?.classList.add('hidden');
                });
                this.updateUI();
                this.applyFilter();
            }
        } catch (error) {
            console.error('Error clearing selections:', error);
        }
    }

    loadSelections() {
        // Load selections from server
        fetch(`/api/galleries/${window.galleryData.id}/selections`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.photos) {
                    data.photos.forEach(photo => {
                        this.selectedPhotos.add(photo.id);
                        const checkbox = document.querySelector(`.photo-checkbox[data-photo-id="${photo.id}"]`);
                        if (checkbox) {
                            checkbox.checked = true;
                            const photoItem = checkbox.closest('.photo-item');
                            photoItem.classList.add('selected');
                            photoItem.querySelector('.selected-indicator')?.classList.remove('hidden');
                        }
                    });
                    this.updateUI();
                }
            })
            .catch(error => {
                console.error('Error loading selections:', error);
            });
    }

    updateUI() {
        const count = this.selectedPhotos.size;
        this.selectedCount.textContent = count;

        if (count > 0) {
            this.selectionCounter.classList.remove('hidden');
            this.clearBtn.classList.remove('hidden');
        } else {
            this.selectionCounter.classList.add('hidden');
            this.clearBtn.classList.add('hidden');
        }

        // Update "Add to Cart" form inputs
        const addToCartBtn = document.getElementById('add-to-cart-btn');
        const photoIdsContainer = document.getElementById('selected-photo-ids-container');
        
        if (addToCartBtn && photoIdsContainer) {
            const selectedIds = Array.from(this.selectedPhotos);
            
            // Clear existing inputs
            photoIdsContainer.innerHTML = '';
            
            // Create hidden inputs for each selected photo ID
            selectedIds.forEach(photoId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'photo_ids[]';
                input.value = photoId;
                photoIdsContainer.appendChild(input);
            });
            
            if (selectedIds.length > 0) {
                addToCartBtn.disabled = false;
                addToCartBtn.textContent = `Add ${selectedIds.length} Photo(s) to Cart`;
            } else {
                addToCartBtn.disabled = true;
                addToCartBtn.textContent = 'Add Selected to Cart';
            }
        }
    }

    applyFilter() {
        const filter = this.filterSelect.value;
        const photoItems = document.querySelectorAll('.photo-item');

        photoItems.forEach(item => {
            const photoId = parseInt(item.dataset.photoId);
            const isSelected = this.selectedPhotos.has(photoId);

            let show = true;
            if (filter === 'selected' && !isSelected) {
                show = false;
            } else if (filter === 'unselected' && isSelected) {
                show = false;
            }

            item.style.display = show ? '' : 'none';
        });
    }

    applySort() {
        const sortBy = this.sortSelect.value;
        const grid = document.getElementById('photo-grid');
        const photoItems = Array.from(grid.querySelectorAll('.photo-item'));

        photoItems.sort((a, b) => {
            switch(sortBy) {
                case 'date-asc':
                    return parseInt(a.dataset.photoDate) - parseInt(b.dataset.photoDate);
                case 'date-desc':
                    return parseInt(b.dataset.photoDate) - parseInt(a.dataset.photoDate);
                case 'name-asc':
                    return a.dataset.photoName.localeCompare(b.dataset.photoName);
                case 'name-desc':
                    return b.dataset.photoName.localeCompare(a.dataset.photoName);
                case 'selected-first':
                    const aSelected = this.selectedPhotos.has(parseInt(a.dataset.photoId));
                    const bSelected = this.selectedPhotos.has(parseInt(b.dataset.photoId));
                    if (aSelected && !bSelected) return -1;
                    if (!aSelected && bSelected) return 1;
                    return 0;
                default:
                    return 0;
            }
        });

        // Re-append in sorted order
        photoItems.forEach(item => grid.appendChild(item));
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.photoSelection = new PhotoSelection();
});

