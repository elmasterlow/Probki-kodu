(function ($, google) {
    "use strict";

    var map = {
        coordsContainer: '#coords',
        mapContainer: '#map',
        lat: null,
        lng: null,
        setCoords: function(lat, lng) {

            var container = $(this.coordsContainer);

            var latInput = container.find('input[name*="[latitude]"]');
            var lngInput = container.find('input[name*="[longitude]"]');

            // set values
            latInput.val(lat.toFixed(3));
            lngInput.val(lng.toFixed(3));
        },
        getCoordsFromForm: function () {

            var container = $(this.coordsContainer);
            var latInput = container.find('input[name*="[latitude]"]');
            var lngInput = container.find('input[name*="[longitude]"]');

            if (latInput.val() && lngInput.val()) {
                return {
                    lat: parseFloat(latInput.val()),
                    lng: parseFloat(lngInput.val()),
                };
            }

            return false;
        },
        init: function (lat, lng) {
            var self = this;

            self.setCoords(lat, lng);

            this.lat = lat;
            this.lng = lng;

            var map = $(this.mapContainer);
            map.css({
                width: '100%',
                height: '300px',
                margin: '15px 0',
            });

            // map instance
            var instance = new google.maps.Map(map[0], {
                center: {lat: this.lat, lng: this.lng},
                zoom: 14,
            });

            // marker instance
            var marker = new google.maps.Marker({
                map: instance,
                position: {
                    lat: this.lat,
                    lng: this.lng
                },
                title: 'x',
                zIndex: 3,
                draggable: true,
            });

            google.maps.event.addListener(marker, 'dragend', function(e) {
                self.setCoords(e.latLng.lat(), e.latLng.lng());
            });
        }
    };

    var address = {
        container: '#init-map',

        // prepare address
        prepare: function () {
            var container = $(this.container);

            var country = container.find('select[name*="[country]"]').val();
            var postcode = container.find('input[name*="[postCode]"]').val();
            var city = container.find('input[name*="[city]"]').val();
            var street = container.find('input[name*="[street]"]').val();
            var no = container.find('input[name*="[no]"]').val();

            var address = '';
            if (street) {
                address += street;
            }

            if (no) {
                address += ((street) ? ' ' : '') + no;
            }

            if (postcode) {
                address += ((address.length > 0) ? ', ' : '') + postcode;
            }

            if (city) {
                address += ((postcode) ? ' ' : (address.length > 0) ? ', ' : '') + city;
            }

            if (country) {
                address += ((address.length > 0) ? ', ' : '') + country;
            }

            return address;
        },
        handle: function (address) {
            var callback = function(results, status) {
                if (status === google.maps.GeocoderStatus.OK) {
                    var lat = results[0].geometry.location.lat();
                    var lng = results[0].geometry.location.lng();

                    map.init(lat, lng);
                } else {
                    console.log('Geocode was not successful for the following reason: ' + status);
                }

                return false;
            };

            this.geocoder.geocode({'address': address}, callback);
        },
        geocoder: new google.maps.Geocoder(),
        init: function () {
            var self = this;

            $('#check-on-map').on('click', function () {
                var address = self.prepare();
                if (!address) {
                    console.log('There is no address');
                    return;
                }

                self.handle(address);
            });
        },
    };

    $(document).ready(function () {
        // init address
        address.init();

        // check is lat lng filled
        var form = map.getCoordsFromForm();
        if (form) {
            map.init(form.lat, form.lng);
        }
    });

})(jQuery, google);