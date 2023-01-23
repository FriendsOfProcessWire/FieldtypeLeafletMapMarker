<?php namespace ProcessWire;

/**
 * Class to hold an address and geocode it to latitude/longitude
 *
 */
class LeafletMapMarker extends WireData {

    const statusNoGeocode = -100;

    protected $geocodeStatuses = array(

        0 => 'N/A',
        1 => 'OK',
        2 => 'OK_ROOFTOP',
        3 => 'OK_RANGE_INTERPOLATED',
        4 => 'OK_GEOMETRIC_CENTER',
        5 => 'OK_APPROXIMATE',

        -1 => 'UNKNOWN',
        -2 => 'ZERO_RESULTS',
        -3 => 'OVER_QUERY_LIMIT',
        -4 => 'REQUEST_DENIED',
        -5 => 'INVALID_REQUEST',

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
        $this->set('raw', '');
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

        // use openstreetmap api for get geocode
        $url = 'https://nominatim.openstreetmap.org/search?limit=1&format=json&q=' . urlencode($this->address);
        $http = new WireHttp();
        $response = $http->get($url);
        if ($response !== false) {
            $result = json_decode($response, true);
        } else {
            $this->error("Error geocoding address");
            if(isset($json['status'])) $this->status = (int) array_search($json['status'], $this->geocodeStatuses);
            else $this->status = -1;
            $this->lat = 0;
            $this->lng = 0;
            $this->raw = '';
            return $this->status;
        }

        $this->lat = $result[0]['lat'];
        $this->lng = $result[0]['lon'];
        $this->raw = $response;

        $this->message("Geocode OK: '{$this->address}'");
        $this->status = 1;
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
