<?php
declare(strict_types=1);
namespace ProcessWire;

// Isolated contract test: execute the actual module, without a site bootstrap or DB.
// Only the framework container/translation/field interfaces are stand-ins.
class WireData {
    private $data = array();
    public function set($key, $value) { $this->data[$key] = $value; return $this; }
    public function get($key) { return $this->data[$key] ?? null; }
    public function __get($key) { return $this->get($key); }
    public function __set($key, $value) { $this->set($key, $value); }
    public function isChanged($key) { return false; }
}
class Inputfield extends WireData {
    public function __construct() {}
    public function attr($key, $value = null) {
        return func_num_args() === 1 ? $this->get($key) : $this->set($key, $value);
    }
    public function _($text) { return $text; }
}
class WireInputData extends \ArrayObject {
    public function __get($key) { return $this[$key] ?? null; }
    public function __isset($key) { return isset($this[$key]); }
    public function offsetGet($key): mixed { return parent::offsetExists($key) ? parent::offsetGet($key) : null; }
}
function wire($name) {
    if ($name !== 'sanitizer') throw new \RuntimeException('Unexpected framework service');
    return new class { public function text($value) { return strip_tags($value); } };
}

$moduleDir = $argv[1] ?? dirname(__DIR__) . '/code/site/modules/FieldtypeLeafletMapMarker';
if (!is_file($moduleDir . '/InputfieldLeafletMapMarker.module')) {
    fwrite(STDERR, "Usage: php tests/leaflet-raw.php [module-directory]\n");
    exit(1);
}
require $moduleDir . '/InputfieldLeafletMapMarker.module';

$checks = 0;
function check($condition, $message) {
    global $checks;
    if (!$condition) throw new \RuntimeException($message);
    $checks++;
}
function field() {
    $field = new InputfieldLeafletMapMarker();
    $field->attr('name', 'coordinate');
    $field->attr('id', 'Inputfield_coordinate');
    $field->attr('value', new LeafletMapMarker());
    $field->set('defaultLat', 41.9);
    $field->set('defaultLng', 12.5);
    $field->set('defaultType', '');
    $field->set('defaultProvider', 'OpenStreetMap.Mapnik');
    return $field;
}
function document($html) {
    $dom = new \DOMDocument();
    $previous = libxml_use_internal_errors(true);
    try {
        $dom->loadHTML('<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>');
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }
    return $dom;
}
function rawInputs($dom) {
    return (new \DOMXPath($dom))->query('//input[@name="_coordinate_raw"]');
}

try {
    set_error_handler(function ($severity, $message) { throw new \RuntimeException($message); });
    $json = json_encode(array('display_name' => "L'Opera <>& \"Catania\" &amp; &#039; 🍃", 'path' => 'a\\b'), JSON_UNESCAPED_UNICODE);
    $cases = array(
        'empty' => '',
        'missing metadata' => null,
        'plain' => 'Catania, Italia',
        'JSON' => $json,
        'quote breakout' => "'><img src=x onerror=alert(1)><input value='",
        'attribute breakout' => "' autofocus onfocus='alert(1)",
        'literal entities' => '&amp; &#039; &quot; &lt;script&gt;',
        'both quotes and Unicode' => "'\"<>& Città 🍃",
        'multiline metadata' => "first\nsecond\r\nthird\t\\last",
    );
    foreach ($cases as $label => $raw) {
        $field = field();
        $input = new WireInputData(array('coordinate' => 'Catania', '_coordinate_lat' => '37.5', '_coordinate_lng' => '15.1', '_coordinate_zoom' => '12', '_coordinate_status' => null, '_coordinate_raw' => $raw));
        if ($raw === null) unset($input['_coordinate_raw']);
        $field->___processInput($input);
        check($field->value->raw === $raw, "$label: request processing changed raw");
        check($field->value->lat === '37.5' && $field->value->lng === '15.1' && $field->value->zoom === 12, "$label: coordinates/zoom changed");
        $dom = document($field->___render());
        $nodes = rawInputs($dom);
        check($nodes->length === 1, "$label: expected one raw input");
        $node = $nodes->item(0);
        check($node->getAttribute('value') === (string) $raw, "$label: raw did not survive HTML round trip");
        check($field->value->raw === $raw, "$label: render mutated stored metadata");
        // Normalize the legitimate controls to the baseline before comparing all markup.
        $control = field();
        $control->attr('value', $field->value);
        $saved = $field->value->raw;
        $field->value->raw = '';
        $expected = document($control->___render());
        $field->value->raw = $saved;
        rawInputs($expected)->item(0)->removeAttribute('value');
        $node->removeAttribute('value');
        check($dom->saveHTML() === $expected->saveHTML(), "$label: metadata introduced markup or attributes");
        echo "PASS $label\n";
    }
    echo "$checks checks passed. No database, geocoding, or network requests.\n";
} catch (\Throwable $error) {
    fwrite(STDERR, "FAIL: " . $error->getMessage() . "\n");
    exit(1);
}
