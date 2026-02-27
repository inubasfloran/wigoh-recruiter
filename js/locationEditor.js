/**
 * CATS
 * Location Editor JavaScript
 *
 * Handles multi-location management for job orders.
 */

var LocationEditor = {
    locations: [],
    containerID: 'locationEditorContainer',
    listID: 'locationList',
    hiddenFieldID: 'locationsJSON',

    /**
     * Initialize the location editor with existing locations.
     * @param {Array|string} initialLocations - Array of location objects or JSON string
     * @param {string} city - Legacy single city value (for backwards compatibility)
     * @param {string} state - Legacy single state value (for backwards compatibility)
     */
    init: function(initialLocations, city, state) {
        this.locations = [];

        // Parse initialLocations if it's a JSON string
        if (typeof initialLocations === 'string') {
            try {
                initialLocations = JSON.parse(initialLocations);
            } catch (e) {
                initialLocations = [];
            }
        }

        // If we have locations from database, use them
        if (Array.isArray(initialLocations) && initialLocations.length > 0) {
            for (var i = 0; i < initialLocations.length; i++) {
                this.locations.push({
                    city: initialLocations[i].city || '',
                    state: initialLocations[i].state || ''
                });
            }
        }
        // Otherwise, if city/state provided (legacy single location), use that
        else if (city || state) {
            this.locations.push({
                city: city || '',
                state: state || ''
            });
        }

        this.render();
        this.updateHiddenField();
    },

    /**
     * Add a new location from the input fields.
     */
    addLocation: function() {
        var cityInput = document.getElementById('newLocationCity');
        var stateInput = document.getElementById('newLocationState');

        var city = cityInput ? cityInput.value.trim() : '';
        var state = stateInput ? stateInput.value.trim() : '';

        if (!city && !state) {
            alert('Please enter a city or state.');
            return;
        }

        this.locations.push({
            city: city,
            state: state
        });

        // Clear input fields
        if (cityInput) cityInput.value = '';
        if (stateInput) stateInput.value = '';

        this.render();
        this.updateHiddenField();
    },

    /**
     * Remove a location by index.
     * @param {number} index - Index of location to remove
     */
    removeLocation: function(index) {
        if (index >= 0 && index < this.locations.length) {
            // Don't allow removal of the last location
            if (this.locations.length <= 1) {
                alert('You must have at least one location.');
                return;
            }
            this.locations.splice(index, 1);
            this.render();
            this.updateHiddenField();
        }
    },

    /**
     * Make a location the primary one (move to first position).
     * @param {number} index - Index of location to make primary
     */
    makePrimary: function(index) {
        if (index > 0 && index < this.locations.length) {
            var location = this.locations.splice(index, 1)[0];
            this.locations.unshift(location);
            this.render();
            this.updateHiddenField();
        }
    },

    /**
     * Render the location list to the DOM.
     */
    render: function() {
        var listElement = document.getElementById(this.listID);
        if (!listElement) return;

        var html = '';
        for (var i = 0; i < this.locations.length; i++) {
            var loc = this.locations[i];
            var displayText = this.formatLocation(loc.city, loc.state);
            var isPrimary = (i === 0);

            html += '<div class="locationItem" style="padding: 5px; margin: 2px 0; background: ' +
                    (isPrimary ? '#e8f4e8' : '#f5f5f5') + '; border: 1px solid #ddd; border-radius: 3px;">';
            html += '<span style="font-weight: ' + (isPrimary ? 'bold' : 'normal') + ';">';
            html += this.escapeHtml(displayText);
            if (isPrimary) {
                html += ' <span style="color: #666; font-size: 11px;">(Primary)</span>';
            }
            html += '</span>';

            html += '<span style="float: right;">';
            if (!isPrimary && this.locations.length > 1) {
                html += '<a href="javascript:void(0);" onclick="LocationEditor.makePrimary(' + i + ');" ' +
                        'title="Make Primary" style="margin-right: 10px; color: #0066cc; text-decoration: none;">' +
                        'Make Primary</a>';
            }
            if (this.locations.length > 1) {
                html += '<a href="javascript:void(0);" onclick="LocationEditor.removeLocation(' + i + ');" ' +
                        'title="Remove" style="color: #cc0000; text-decoration: none;">' +
                        'Remove</a>';
            }
            html += '</span>';
            html += '<div style="clear: both;"></div>';
            html += '</div>';
        }

        if (this.locations.length === 0) {
            html = '<div style="color: #999; padding: 5px;">No locations added yet.</div>';
        }

        listElement.innerHTML = html;
    },

    /**
     * Format city and state into a display string.
     * @param {string} city
     * @param {string} state
     * @returns {string}
     */
    formatLocation: function(city, state) {
        if (city && state) {
            return city + ', ' + state;
        } else if (city) {
            return city;
        } else if (state) {
            return state;
        }
        return '';
    },

    /**
     * Update the hidden JSON field with current locations.
     */
    updateHiddenField: function() {
        var hiddenField = document.getElementById(this.hiddenFieldID);
        if (hiddenField) {
            hiddenField.value = JSON.stringify(this.locations);
        }

        // Also update legacy city/state fields for backwards compatibility
        var cityField = document.getElementById('city');
        var stateField = document.getElementById('state');

        if (this.locations.length > 0) {
            if (cityField) cityField.value = this.locations[0].city;
            if (stateField) stateField.value = this.locations[0].state;
        }
    },

    /**
     * Escape HTML special characters.
     * @param {string} text
     * @returns {string}
     */
    escapeHtml: function(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    },

    /**
     * Validate that at least one location exists.
     * @returns {boolean}
     */
    validate: function() {
        if (this.locations.length === 0) {
            return false;
        }

        // Check that at least the first location has city or state
        var first = this.locations[0];
        if (!first.city && !first.state) {
            return false;
        }

        return true;
    },

    /**
     * Get locations as array.
     * @returns {Array}
     */
    getLocations: function() {
        return this.locations;
    },

    /**
     * Get locations as JSON string.
     * @returns {string}
     */
    getLocationsJSON: function() {
        return JSON.stringify(this.locations);
    }
};
