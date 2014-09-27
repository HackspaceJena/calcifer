/**
 * Created by tim on 06.07.14.
 */

// initializing with settings

function addGeoCoordinates(ev) {
    return false;
}
var map = null;
jQuery(document).ready(function () {
    if (jQuery('.icon.link').length > 0) {
        jQuery('.icon.link').popup();
    }

    if (jQuery('input[type=datetime]').length > 0) {
        jQuery('input[type=datetime]').datetimepicker({lang: 'de', format: 'Y-m-d H:i'});
    }

    if (jQuery('#map').length == 1) {
        jQuery('.add_geo').click(addGeoCoordinates);
        map = L.map('map');

        // add an OpenStreetMap tile layer
        L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        map.setView([51.505, -0.09], 0);

        L.Icon.Default.imagePath = '/css/images';
        var popup = L.popup();

        var marker = L.marker();

        function onMapClick(e) {
            marker
                .setLatLng(e.latlng)
                //.setContent("You clicked the map at " + e.latlng.toString())
                .addTo(map);
        }

        map.on('click', onMapClick);

        jQuery('.geo.chooser').modal('setting', {
            closable: false,
            onApprove: function () {
                var coords = marker.getLatLng();
                if (!(jQuery('input[name=location_lat]').val() == undefined)) {
                    jQuery('input[name=location_lat]').val(coords.lat);
                    jQuery('input[name=location_lon]').val(coords.lng);
                    jQuery('input[name=location]').css('margin-bottom', '3.2rem');
                    jQuery('span.coords').text('Folgende Koordinaten sind angegeben: lat:' + coords.lat + ', lon:' + coords.lng);
                } else {
                    jQuery('input[name=geocords]').val(coords.lat + ',' + coords.lng);
                }
            },
            onDeny: function () {

            },
            onVisible: function () {
                map.invalidateSize(true);
                var lat = 0;
                var lon = 0;
                if (!(jQuery('input[name=location_lat]').val() == undefined)) {
                    lat = parseFloat(jQuery('input[name=location_lat]').val());
                    lon = parseFloat(jQuery('input[name=location_lon]').val());
                } else {
                    var latlon = jQuery('input[name=geocords]').val();
                    lat = latlon.split(',')[0];
                    lon = latlon.split(',')[1];
                }
                if ((lat > 0) && (lon > 0)) {
                    map.setView([lat, lon], 16);
                    var latlng = new L.LatLng(lat, lon);
                    marker.setLatLng(latlng);
                    marker.addTo(map);
                } else {
                    map.locate({setView: true});
                }
            }
        }).modal('attach events', '.add_geo', 'show');
    }
});

$(document).ready(function() {

    if (jQuery('#view-map').length == 1) {
        jQuery('.show_map').click(addGeoCoordinates);
        map = L.map('view-map');

        // add an OpenStreetMap tile layer
        L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        map.setView([51.505, -0.09], 0);

        L.Icon.Default.imagePath = '/css/images';
        var popup = L.popup();

        var marker = L.marker();

        jQuery('.geo.viewer').modal('setting', {
            closable: true,
            onDeny: function () {

            },
            onVisible: function () {
                map.invalidateSize(true);
                var lat = $('#view-map').data('lat');
                var lon = $('#view-map').data('lon');
                if ((lat > 0) && (lon > 0)) {
                    map.setView([lat, lon], 16);
                    var latlng = new L.LatLng(lat, lon);
                    marker.setLatLng(latlng);
                    marker.addTo(map);
                } else {
                    map.locate({setView: true});
                }
            }
        }).modal('attach events', '.show_map', 'show');
    }
});