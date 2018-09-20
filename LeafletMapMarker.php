<?php

/**
 * Class to hold an address and geocode it to latitude/longitude
 *
 */
class LeafletMapMarker extends WireData {

    const statusNoGeocode = -100;

    protected $geocodeStatuses = array(

        -1 => 'N/A',
        1 => 'OK',
        
        
        -100 => 'Geocode OFF', // RCD

    );

    protected $geocodedAddress = '';

    public function __construct() {
        $this->set('lat', '');
        $this->set('lng', '');
        $this->set('address', '');
        $this->set('status', 0);
        $this->set('zoom', 0);
        $this->set('provider', '');
        // temporary runtime property to indicate the geocode should be skipped
        $this->set('skipGeocode', false);
    }

    public function set($key, $value) {

        if($key == 'lat' || $key == 'lng') {
            // if value isn't numeric, then it's not valid: make it blank
            if(strpos($value, ',') !== false) $value = str_replace(',', '.', $value); // convert 123,456 to 123.456
            if(!is_numeric($value)) $value = '';

        } else if($key == 'address') {
            $value = wire('sanitizer')->text($value);

        } else if($key == 'status') {
            $value = (int) $value;
            if(!isset($this->geocodeStatuses[$value])) $value = -1; // -1 = unknown
        } else if($key == 'zoom') {
            $value = (int) $value;
        } else if($key == 'provider') {
            $value = $value;
        }

        return parent::set($key, $value);
    }

    public function get($key) {
        if($key == 'statusString') return str_replace('_', ' ', $this->geocodeStatuses[$this->status]);
        return parent::get($key);
    }

    public function geocode() {
        if($this->skipGeocode) return -100;

        // check if address was already geocoded
        if($this->geocodedAddress == $this->address) return $this->status;
        $this->geocodedAddress = $this->address;

        if(!ini_get('allow_url_fopen')) {
            $this->error("Geocode is not supported because 'allow_url_fopen' is disabled in PHP");
            return 0;
        }

        // Create a stream
        $opts = array('http'=>array('header'=>"User-Agent: LeafletMapMarkerModule\r\n"));
        $context = stream_context_create($opts);

        // Open the file using the HTTP headers set above
        $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($this->address) . "&format=json&addressdetails=1";
        $json = file_get_contents($url, false, $context);  
        $json = json_decode($json, true);

        if(empty($json[0]['place_id'])) {
            $this->error("Error geocoding address");
            $this->status = -1;
            $this->lat = 0;
            $this->lng = 0;
            return $this->status;
        }

        $locationType = $json[0]['class'];

        $this->lat = $json[0]['lat'];
        $this->lng = $json[0]['lon'];
        $this->raw = json_encode($json[0]);


        $statusString = $json['status'] . '_' . $locationType;
        $status = array_search($statusString, $this->geocodeStatuses);
        if($status === false) $status = 1; // OK

        $this->status = $status;
        $this->message("Geocode {$this->statusString}: '{$this->address}'");

        return $this->status;
    }

    /**
     * If accessed as a string, then just output the lat, lng coordinates
     *
     */
    public function __toString() {
        return "$this->address ($this->lat, $this->lng, $this->zoom) [$this->statusString]";
    }
}
