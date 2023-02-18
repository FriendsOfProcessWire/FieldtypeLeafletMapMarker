/**
 * Display a Leaflet Map and pinpoint a location for InputfieldLeafletMapMarker
 *
 */

var InputfieldLeafletMapMarker = {

    options: {
        zoom: 9,
        draggable: true,
        center: null
    },


    init: function(mapId, lat, lng, zoom, mapType, provider) {

        var options = InputfieldLeafletMapMarker.options;

        if(zoom < 1) zoom = 9;
        //options.center = new google.maps.LatLng(lat, lng);
        options.zoom = parseInt(zoom);

        var map = L.map(document.getElementById(mapId)). setView([lat, lng], options.zoom);
        L.tileLayer.provider(provider).addTo(map);

        var coder = L.Control.Geocoder.nominatim(),
        geocoder = L.Control.geocoder({
            geocoder: geocoder, placeholder: ''
        }).addTo(map);

        var marker = L.marker(
            [lat,lng],
            {draggable: options.draggable}
        ).addTo(map);

        var $map = $('#' + mapId);
        //var $latlng = $map.siblings(".InputfieldLeafletMapMarkerLatLng").find("input[type=text]");
        var $lat = $map.siblings(".InputfieldLeafletMapMarkerLat").find("input[type=text]");
        var $lng = $map.siblings(".InputfieldLeafletMapMarkerLng").find("input[type=text]");
        var $addr = $map.siblings(".InputfieldLeafletMapMarkerAddress").find("input[type=text]");
        var $addrJS = $map.siblings(".InputfieldLeafletMapMarkerAddress").find("input[type=hidden]");
        var $raw = $map.siblings(".InputfieldLeafletMapMarkerAddress").find("input[name$=_raw]");
        var $toggle = $map.siblings(".InputfieldLeafletMapMarkerToggle").find("input[type=checkbox]");
        var $zoom = $map.siblings(".InputfieldLeafletMapMarkerZoom").find("input[type=number]");
        var $notes = $map.siblings(".notes");
        var $latlng = '';

        $( ".InputfieldLeafletMapMarkerAddress" ).on( "click", function() {
            $(".leaflet-control-geocoder.leaflet-control").toggleClass("leaflet-control-geocoder-expanded");
            setTimeout(function() { $('input.undefined').focus() }, 300);
        });

        $lat.val(marker.getLatLng().lat);
        $lng.val(marker.getLatLng().lng);
        $zoom.val(map.getZoom());

        $zoom.change(function(event) {
            map.setZoom($zoom.val());
            map.setView(marker.getLatLng());
        });

        $lat.change(function(event) {
            marker.setLatLng([$lat.val(),$lng.val()]);
            map.setView(marker.getLatLng(), 9);

            coder.reverse(marker.getLatLng(), map.options.crs.scale(map.getZoom()), function(results) {
                var r = results[0];
                if (r) {
                    $addr.val(r.name);
                    $raw.val(JSON.stringify(r.properties));
                }
            })
        });


        $lng.change(function(event) {
            marker.setLatLng([$lat.val(),$lng.val()]);
            map.setView(marker.getLatLng(), 9);
            coder.reverse(marker.getLatLng(), map.options.crs.scale(map.getZoom()), function(results) {
                var r = results[0];
                if (r) {
                    $addr.val(r.name);
                    $raw.val(JSON.stringify(r.properties));                 
                }
            })
        });


        geocoder.markGeocode = function(result) {
            marker.setLatLng(result.center);
            map.fitBounds(result.bbox);
            $lat.val(result.center.lat);
            $lng.val(result.center.lng);
            $addr.val(result.name);
            $raw.val(JSON.stringify(result.properties));
        };


        marker.on('dragend', function(event) {
            var result = marker.getLatLng();
            $lat.val(result.lat).trigger('change.custom');
            $lng.val(result.lng).trigger('change.custom');

            //reverse geocoding displays in the adress field
            coder.reverse(marker.getLatLng(), map.options.crs.scale(map.getZoom()), function(results) {
                var r = results[0];
                if (r) {
                    $addr.val(r.name);
                    $raw.val(JSON.stringify(r.properties));
                }
            })
        });

        map.on('zoomend', function(event){
            $zoom.val(map.getZoom());
        })

        /*
           google.maps.event.addListener(map, 'zoom_changed', function() {
           $zoom.val(map.getZoom());
           });

           $addr.blur(function() {
           if(!$toggle.is(":checked")) return true;
           var geocoder = new google.maps.Geocoder();
           geocoder.geocode({ 'address': $(this).val()}, function(results, status) {
           if(status == google.maps.GeocoderStatus.OK && results[0]) {
           var position = results[0].geometry.location;
           map.setCenter(position);
           marker.setPosition(position);
           $lat.val(position.lat());
           $lng.val(position.lng());
           $addrJS.val($addr.val());
           }
           $notes.text(status);
           });
           return true;
           });

           $zoom.change(function() {
           map.setZoom(parseInt($(this).val()));
           });

           $toggle.click(function() {
           if($(this).is(":checked")) {
           $notes.text('Geocode ON');
        // google.maps.event.trigger(marker, 'dragend');
        $addr.trigger('blur');
        } else {
        $notes.text('Geocode OFF');
        }
        return true;
        });

        // added by diogo to solve the problem of maps not rendering correctly in hidden elements
        // trigger a resize on the map when either the tab button or the toggle field bar are pressed

        // get the tab element where this map is integrated
        var $map = $('#' + mapId);
        var $tab = $('#_' + $map.closest('.InputfieldFieldsetTabOpen').attr('id'));
        // get the inputfield where this map is integrated and add the tab to the stack
        var $inputFields = $map.closest('.Inputfield').find('.InputfieldStateToggle').add($tab);

        $inputFields.on('click',function(){
        // give it time to open
        window.setTimeout(function(){
        google.maps.event.trigger(map,'resize');
        map.setCenter(options.center);
        }, 200);
        });
        */
    }
};


$(document).ready(function() {
    $(".InputfieldLeafletMapMarkerMap").each(function() {
        var $t = $(this);
        InputfieldLeafletMapMarker.init($t.attr('id'), $t.attr('data-lat'), $t.attr('data-lng'), $t.attr('data-zoom'), $t.attr('data-type'), $t.attr('data-provider'));
    });
});
