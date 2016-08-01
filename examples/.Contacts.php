<!DOCTYPE html>
<html lang="en">
<?php $map = $modules->get('MarkupLeafletMap'); ?>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title><?php echo $page->title; ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo $config->urls->templates?>styles/main.css" />

    <!-- Load Leaflet files -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <?php echo $map->getLeafletMapHeaderLines(); ?>
</head>
<body>
    <h1><?php echo $page->title; ?></h1>
<?php

        // Extract the pages that will be turned into markers on the map
        $items = $pages->find("template=Contact"); // Update the selector to choose the pages you want to use


        $map_options = array(
            // Define a callback function that gets called once for each page passed to the map render function
            // This will be called to prepare the content of the marker's popup dialog and is given access
            // to the page it needs to generate its content. Returns a string that will be included in the JS
            // inserted into the page hosting the map.
            'popupFormatter' => function($page) {
                $lines   = array();

                // Here's the phone number
                if ($page->phone) {
                    $lines[] = "Phone: {$page->phone}";
                }

                // Here's the geocoded address for this location...
                if ($page->location->address) {
                    $lines[] = "Address: {$page->location->address}";
                }

                // NOTE: If your images are setup to always supply an array, please use this code...
                if ($page->image->first()) {
                    // NB: THIS IS TRICKY. We must not use single quotes in any generated HTML as this is being
                    // inserted into the Javascript that drives leaflet, so we must escape double quotes within this
                    // string.
                    $lines[] = "<img src=\"{$page->image->first()->url}\" height=\"100\" width=\"100\" />"; // change 'image' if needed
                }
                // ELSE: If your images are setup to a single image or null, please comment out the above code and uncomment the following...
                //  if ($page->image)) {
                //      $lines[] = "<img src=\"{$page->image->url}\" height=\"100\" width=\"100\" />"; // change 'image' if needed
                //  }

                return implode("<br />", $lines);
            },


            // This routine formats the marker icon that gets used on the map.
            'markerFormatter' => function ($page, $marker_options) {
                // Pull override values from the $page.
                if ($page->marker_icon) {
                    $marker_options['icon'] = $page->marker_icon;
                }

                if ($page->marker_colour->title) {
                    $marker_options['markerColor'] = $page->marker_colour->title;
                }

                // And the icon colour. This is another text field. Colour values like White, Black or an RGB value are ok here.
                if ($page->marker_icon_colour) {
                    $marker_options['iconColor'] = $page->marker_icon_colour;
                }

                return $marker_options;
            }
        );

        // Generate the HTML and JS that will render the map with a marker for each page in the $items page array
        echo $map->render($items, 'location', $map_options);
    ?>
</body>
</html>
