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

    $('.ui.sticky')
        .sticky({
            context: '#main'
        })
    ;

    if (jQuery('input[type=datetime]').length > 0) {
        jQuery('input[type=datetime]').datetimepicker({lang: 'de', format: 'Y-m-d H:i'});
    }

    if (jQuery('#map').length == 1) {
        jQuery('.add_geo').click(addGeoCoordinates);
        map = L.map('map');

        // add an OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
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

function calcBoxSize(columns) {
    var card_selector = jQuery('.ui.cards .card');
    var screen_width = $(window).width() - 14 - 14; /* padding of basic segment */
    // first check if we can display 4 cards on the screen with a minimum width of 399px
    var box_width = Math.floor((screen_width / columns)) - 10;
    if ((box_width >= 395) || (columns == 1)) {
        card_selector.css('width',box_width);
    } else {
        calcBoxSize(columns - 1);
    }
}

$(window).resize(function(){
    var card_selector = jQuery('.ui.cards .card');

    if (card_selector.length > 0) {
        calcBoxSize(4);
    }
});

$(document).ready(function() {
    var view_map_selector = jQuery('#view-map');
    var card_selector = jQuery('.ui.cards .card');

    if (card_selector.length > 0) {
        calcBoxSize(4);
    }
    
    $location = $('#event_location');

    if ($location.length == 1) {
        $('#event_location')
            .dropdown({
                minCharacters: 4,
                allowAdditions: true,
                apiSettings: {
                    url: '/orte/?q={query}'
                },
                fields: {
                    remoteValues : 'results', // grouping for api results
                    values       : 'values', // grouping for all dropdown values
                    name         : 'name',   // displayed dropdown text
                    value        : 'name'   // actual dropdown value
                },
                templates: {
                    menu: function(response, fields) {
                        var
                        values = response[fields.values] || {},
                        html   = ''
                        ;
                        $.each(values, function(index, option) {
                            var item = option;
                            html += '<div class="item" data-value="' + option[fields.value] + '"><div class="ui fluid green card">' +
                                                 '<div class="content">'+
                                                     '<div class="header">' +
                                                         '<i class="ui icon map marker"></i>' + item.name +
                                                     '</div>' +
                                                     '<div class="meta">'+
                                                     (item.lon && item.lat ? 'lon: '+ escape(item.lon)+' lat: ' + escape(item.lat) : '')+
                                             (item.streetaddress ? ' Anschrift: ' + item.streetaddress + ' ' + item.streetnumber + ' ' + item.zipcode + ' ' + item.city : '')+
                                                     '</div>'+
                                                     (item.description ? '<div class="description">' + item.description + '</div>' : '') +
                                                 '</div>'+
                                             '</div></div>';
                        });
                        return html;
                    }
                }
            })
        ;
    }

    $('#event_tags')
        .dropdown({
                minCharacters: 2,
                allowAdditions: true,
                apiSettings: {
                    url: '/tags/?q={query}'
                },
                fields: {
                    remoteValues: 'results', // grouping for api results
                    values: 'values', // grouping for all dropdown values
                    name: 'name',   // displayed dropdown text
                    value: 'name'   // actual dropdown value
                }/*,
                templates: {
                    label: function (value, text) {
                        return '<i class="tag icon"></i>' + text + '<i class="delete icon"></i>';
                    }
                }*/
            }
        );

    if (view_map_selector.length == 1) {
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
                var lat = view_map_selector.data('lat');
                var lon = view_map_selector.data('lon');
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
