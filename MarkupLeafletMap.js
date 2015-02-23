/**
 * ProcessWire Leaflet Map Markup (JS)
 *
 * Renders maps for the FieldtypeLeafletMapMarker module
 * 
 * ProcessWire 2.x 
 * Port of Google Map Markup by Ryan Cramer
 * Copyright (C) 2013 by Ryan Cramer
 
 * Copyright (C) 2015 by Mats Neander
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 * 
 * http://processwire.com
 *
 * Javascript Usage:
 * =================
 * var map = new MarkupLeafletMap();
 * map.setOption('any-leaflet-maps-option', 'value'); 
 * map.setOption('zoom', 12); // example
 * 
 * // init(container ID, latitude, longitude):
 * map.init('#map-div', 26.0936823, -77.5332796); 
 * 
 * // addMarker(latitude, longitude, optional URL, optional URL to icon file):
 * map.addMarker(26.0936823, -77.5332796, 'en.wikipedia.org/wiki/Castaway_Cay', ''); 
 * map.addMarker(...you may have as many of these as you want...); 
 * 
 * // optionally fit the map to the bounds of the markers you added
 * map.fitToMarkers();
 *
 */

function MarkupLeafletMap() {

	this.map = null;
	this.markers = [];
	this.numMarkers = 0;

	this.options = {
		zoom: 10, 
		center: null, 
	};

	


	this._currentURL = '';

	this.init = function(mapID, lat, lng) {
		if(lat != 0) this.map = L.map(mapID, {center: [lat, lng], zoom: this.options.zoom} );
		
		L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
			attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
		}).addTo(this.map);

	}

	this.setOption = function(key, value) {
		this.options[key] = value; 
	}

	var markers = new L.MarkerClusterGroup();

	this.addMarker = function(lat, lng, url, title) {
		if(lat == 0.0) return;


		var latLng = L.latLng(lat, lng);

		
		var markerOptions = {
			
			linkURL: '',
			title: title
		}; 
	
		
		var marker = L.marker(latLng, markerOptions);

		
		markers.addLayer(marker);
		this.map.addLayer(markers);

		this.markers[this.numMarkers] = marker;
		this.numMarkers++;

		if(url.length > 0) marker.linkURL = url;

		
		if(marker.linkURL.length > 0) {
			
				marker.bindPopup("<b><a href='" + marker.linkURL + "'>" + title + "</a></b>");

			}
		
		

		
	}

	this.fitToMarkers = function() {

		var map = this.map;
		var mg = [];
		for(var i = 0; i < this.numMarkers; i++) {	

			 mg.push(this.markers[i].getLatLng());
			
		}

		map.fitBounds(mg);


	}


	
}

