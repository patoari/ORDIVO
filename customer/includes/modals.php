<!-- Location Modal -->
<div class="modal fade" id="locationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Your Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Use Current Location Button -->
                <button type="button" class="btn btn-success w-100 mb-3" id="useCurrentLocation">
                    <i class="fas fa-crosshairs me-2"></i>
                    <span id="locationBtnText">Use Current Location</span>
                    <span class="spinner-border spinner-border-sm ms-2 d-none" id="locationSpinner"></span>
                </button>
                
                <!-- Location Status Message -->
                <div class="alert alert-info d-none" id="locationStatus"></div>
                
                <div class="text-center text-muted mb-2">
                    <small>OR</small>
                </div>
                
                <input type="text" class="form-control mb-3" id="locationSearch" placeholder="Search for your location...">
                
                <div class="location-option selected" data-location="Dhaka, Bangladesh" data-lat="23.8103" data-lng="90.4125">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    <span>Dhaka, Bangladesh</span>
                </div>
                <div class="location-option" data-location="Chittagong, Bangladesh" data-lat="22.3569" data-lng="91.7832">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    <span>Chittagong, Bangladesh</span>
                </div>
                <div class="location-option" data-location="Sylhet, Bangladesh" data-lat="24.8949" data-lng="91.8687">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    <span>Sylhet, Bangladesh</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmLocation">Confirm Location</button>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Filters Modal -->
<div class="mobile-filters-modal" id="mobileFiltersModal">
    <div class="mobile-filters-content">
        <div class="mobile-filters-header">
            <h5>Filters</h5>
            <button class="mobile-filters-close" id="closeFiltersBtn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="filter-section">
            <h6>Sort By</h6>
            <div class="filter-option">
                <input type="radio" name="mobile-sort" id="mobile-sort-relevance" value="relevance" checked>
                <label for="mobile-sort-relevance">Relevance</label>
            </div>
            <div class="filter-option">
                <input type="radio" name="mobile-sort" id="mobile-sort-fastest" value="fastest">
                <label for="mobile-sort-fastest">Fastest Delivery</label>
            </div>
            <div class="filter-option">
                <input type="radio" name="mobile-sort" id="mobile-sort-rating" value="top-rated">
                <label for="mobile-sort-rating">Top Rated</label>
            </div>
        </div>

        <div class="filter-section">
            <h6>Delivery Options</h6>
            <div class="filter-option">
                <input type="checkbox" id="mobile-filter-free-delivery">
                <label for="mobile-filter-free-delivery">Free Delivery</label>
            </div>
            <div class="filter-option">
                <input type="checkbox" id="mobile-filter-fast-delivery">
                <label for="mobile-filter-fast-delivery">Fast Delivery</label>
            </div>
        </div>

        <div class="mobile-filters-footer">
            <button class="btn-clear-filters" id="mobileClearFilters">Clear All</button>
            <button class="btn-apply-filters" id="mobileApplyFilters">Apply Filters</button>
        </div>
    </div>
</div>
