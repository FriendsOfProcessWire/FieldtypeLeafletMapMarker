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

        var mapElement = document.getElementById(mapId);
        var options = InputfieldLeafletMapMarker.options;

        if(zoom < 1) zoom = 9;
        //options.center = new google.maps.LatLng(lat, lng);
        options.zoom = parseInt(zoom);
        
        var map = L.map(mapElement).setView([lat, lng], options.zoom);
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
        });

        // get the tab element where this map is integrated
        var $map = $('#' + mapId); 

        // This seem to no longer work with Uikit theme.
        // var $tab = $('#_' + $map.closest('.InputfieldFieldsetTabOpen').attr('id'));
        // // get the inputfield where this map is integrated and add the tab to the stack
        // var $inputFields = $map.closest('.Inputfield').find('.InputfieldStateToggle').add($tab);
        
        // Get closed wrappers around the map.
        var $inputFields = $map.parents('.Inputfield.InputfieldStateCollapsed');

        // Refresh the map when any of the wrappers open.
        $inputFields.on('opened',function(){
            window.setTimeout(function(){
                map.invalidateSize();
            }, 200);
        });
    }
};

function initializeLeafletMap() {
    $(".InputfieldLeafletMapMarkerMap").each(function(item) {
        var $t = $(this);
        if (!$t.children().length) {
            InputfieldLeafletMapMarker.init($t.attr('id'), $t.attr('data-lat'), $t.attr('data-lng'), $t.attr('data-zoom'), $t.attr('data-type'), $t.attr('data-provider'));
        }
    });
}

$(document).ready(function() {
    initializeLeafletMap();
    $(document).on('reloaded', '.InputfieldLeafletMapMarker', initializeLeafletMap);
}); 
