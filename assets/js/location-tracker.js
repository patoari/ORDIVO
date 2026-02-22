/**
 * ORDIVO Location Tracker
 * Shared location tracking functionality for all pages
 */

// Initialize Location Modal
function initializeLocationModal() {
    const locationOptions = document.querySelectorAll('.location-option');
    const confirmBtn = document.getElementById('confirmLocation');
    const useCurrentLocationBtn = document.getElementById('useCurrentLocation');
    const locationBtnText = document.getElementById('locationBtnText');
    const locationSpinner = document.getElementById('locationSpinner');
    const locationStatus = document.getElementById('locationStatus');
    let selectedLocation = 'Dhaka, Bangladesh';
    let selectedCoords = { lat: 23.8103, lng: 90.4125 };
    
    locationOptions.forEach(option => {
        option.addEventListener('click', function() {
            locationOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            selectedLocation = this.dataset.location;
            selectedCoords = {
                lat: parseFloat(this.dataset.lat),
                lng: parseFloat(this.dataset.lng)
            };
        });
    });
    
    // Use Current Location with Geolocation API
    useCurrentLocationBtn?.addEventListener('click', function() {
        if (!navigator.geolocation) {
            showLocationStatus('Geolocation is not supported by your browser', 'danger');
            return;
        }
        
        // Show loading state
        locationBtnText.textContent = 'Detecting Location...';
        locationSpinner.classList.remove('d-none');
        useCurrentLocationBtn.disabled = true;
        
        navigator.geolocation.getCurrentPosition(
            // Success callback
            async function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const accuracy = position.coords.accuracy;
                
                console.log('Location detected:', { lat, lng, accuracy });
                
                // Try to get address from coordinates using reverse geocoding
                try {
                    const address = await reverseGeocode(lat, lng);
                    selectedLocation = address;
                    selectedCoords = { lat, lng };
                    
                    // Update UI
                    showLocationStatus(`Location detected: ${address} (Accuracy: ${Math.round(accuracy)}m)`, 'success');
                    
                    // Add detected location to the list
                    addDetectedLocationOption(address, lat, lng);
                    
                    // Store in localStorage
                    localStorage.setItem('user_location', JSON.stringify({
                        address: address,
                        lat: lat,
                        lng: lng,
                        timestamp: Date.now()
                    }));
                    
                } catch (error) {
                    console.error('Reverse geocoding error:', error);
                    selectedLocation = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                    selectedCoords = { lat, lng };
                    showLocationStatus(`Location detected: ${selectedLocation}`, 'success');
                }
                
                // Reset button state
                locationBtnText.textContent = 'Location Detected';
                locationSpinner.classList.add('d-none');
                useCurrentLocationBtn.disabled = false;
                useCurrentLocationBtn.classList.remove('btn-success');
                useCurrentLocationBtn.classList.add('btn-primary');
            },
            // Error callback
            function(error) {
                let errorMessage = 'Unable to detect location';
                
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'Location permission denied. Please enable location access in your browser settings.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'Location information unavailable. Please try again.';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'Location request timed out. Please try again.';
                        break;
                }
                
                console.error('Geolocation error:', error);
                showLocationStatus(errorMessage, 'danger');
                
                // Reset button state
                locationBtnText.textContent = 'Use Current Location';
                locationSpinner.classList.add('d-none');
                useCurrentLocationBtn.disabled = false;
            },
            // Options
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    });
    
    confirmBtn?.addEventListener('click', function() {
        const currentLocationEl = document.getElementById('currentLocation');
        const currentLocationMobileEl = document.getElementById('currentLocationMobile');
        
        if (currentLocationEl) currentLocationEl.textContent = selectedLocation;
        if (currentLocationMobileEl) currentLocationMobileEl.textContent = selectedLocation;
        
        // Store location with coordinates
        localStorage.setItem('user_location', JSON.stringify({
            address: selectedLocation,
            lat: selectedCoords.lat,
            lng: selectedCoords.lng,
            timestamp: Date.now()
        }));
        
        const modalElement = document.getElementById('locationModal');
        if (modalElement) {
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) {
                modalInstance.hide();
            }
        }
        
        // Show success message
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Location Updated',
                text: `Delivery location set to ${selectedLocation}`,
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
    
    // Helper function to show location status
    function showLocationStatus(message, type) {
        if (!locationStatus) return;
        
        locationStatus.textContent = message;
        locationStatus.className = `alert alert-${type}`;
        locationStatus.classList.remove('d-none');
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            locationStatus.classList.add('d-none');
        }, 5000);
    }
    
    // Helper function to add detected location to options
    function addDetectedLocationOption(address, lat, lng) {
        // Remove any existing detected location
        const existingDetected = document.querySelector('.location-option.detected');
        if (existingDetected) {
            existingDetected.remove();
        }
        
        // Create new option
        const newOption = document.createElement('div');
        newOption.className = 'location-option detected selected';
        newOption.dataset.location = address;
        newOption.dataset.lat = lat;
        newOption.dataset.lng = lng;
        newOption.innerHTML = `
            <i class="fas fa-crosshairs me-2 text-success"></i>
            <span>${address}</span>
            <small class="text-muted ms-2">(Current Location)</small>
        `;
        
        // Add click handler
        newOption.addEventListener('click', function() {
            locationOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            selectedLocation = this.dataset.location;
            selectedCoords = {
                lat: parseFloat(this.dataset.lat),
                lng: parseFloat(this.dataset.lng)
            };
        });
        
        // Insert at the top
        const modalBody = document.querySelector('#locationModal .modal-body');
        const firstOption = document.querySelector('.location-option');
        if (modalBody && firstOption) {
            modalBody.insertBefore(newOption, firstOption);
        }
        
        // Deselect other options
        locationOptions.forEach(opt => opt.classList.remove('selected'));
    }
    
    // Reverse geocoding function (using OpenStreetMap Nominatim API)
    async function reverseGeocode(lat, lng) {
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
                {
                    headers: {
                        'Accept-Language': 'en'
                    }
                }
            );
            
            if (!response.ok) {
                throw new Error('Geocoding failed');
            }
            
            const data = await response.json();
            
            // Extract meaningful address
            const address = data.address;
            let formattedAddress = '';
            
            if (address.road || address.neighbourhood) {
                formattedAddress = address.road || address.neighbourhood;
            }
            
            if (address.suburb || address.city_district) {
                formattedAddress += formattedAddress ? ', ' : '';
                formattedAddress += address.suburb || address.city_district;
            }
            
            if (address.city || address.town) {
                formattedAddress += formattedAddress ? ', ' : '';
                formattedAddress += address.city || address.town;
            }
            
            if (address.country) {
                formattedAddress += formattedAddress ? ', ' : '';
                formattedAddress += address.country;
            }
            
            return formattedAddress || data.display_name;
            
        } catch (error) {
            console.error('Reverse geocoding error:', error);
            // Fallback to coordinates
            return `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
        }
    }
    
    // Load saved location on page load
    loadSavedLocation();
}

// Load saved location from localStorage
function loadSavedLocation() {
    const savedLocation = localStorage.getItem('user_location');
    if (savedLocation) {
        try {
            const locationData = JSON.parse(savedLocation);
            // Check if location is less than 24 hours old
            const hoursSinceUpdate = (Date.now() - locationData.timestamp) / (1000 * 60 * 60);
            
            if (hoursSinceUpdate < 24) {
                const currentLocationEl = document.getElementById('currentLocation');
                const currentLocationMobileEl = document.getElementById('currentLocationMobile');
                
                if (currentLocationEl) currentLocationEl.textContent = locationData.address;
                if (currentLocationMobileEl) currentLocationMobileEl.textContent = locationData.address;
            }
        } catch (error) {
            console.error('Error loading saved location:', error);
        }
    }
}

// Get current user location
function getUserLocation() {
    const savedLocation = localStorage.getItem('user_location');
    if (savedLocation) {
        try {
            return JSON.parse(savedLocation);
        } catch (error) {
            console.error('Error parsing saved location:', error);
        }
    }
    return null;
}

// Initialize on DOM load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        initializeLocationModal();
    });
} else {
    initializeLocationModal();
}
