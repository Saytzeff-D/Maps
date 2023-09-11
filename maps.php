<?php

    
?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <!-- Bootstrap core JavaScript-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />

    <!-- Mapbox -->
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.css" rel="stylesheet">
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.14.1/mapbox-gl.js"></script>

    <!-- AWS -->
    <script src="https://sdk.amazonaws.com/js/aws-sdk-2.7.16.min.js"></script>

    <!DOCTYPE html>
    <html lang="en">

    <head>

        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="">
        <meta name="author" content="">

        <link rel="icon" type="image/x-icon" href="img/icon.png">
        <title>REDENES</title>

        <!-- Custom fonts for this template-->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

        <style>
            .dropdown > .btn {
                height: 38px !important;
                background-color: #fff !important;
                border: 1px solid #ced4da !important;
            }
            .filter {
                width: ""
            }

            .title {
            }

            .map-container {
                position: relative;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 80vh;
                width: 100%;
                gap: 10px
            }

        </style>

    </head>

    <body id="page-top">
        <!-- Page Wrapper -->
        <div id="wrapper">

            <!-- Content Wrapper -->
            <div id="content-wrapper" class="d-flex flex-column">

                <!-- Main Content -->
                <div id="content">
                    <!-- End of Topbar -->

                    <!-- Begin Page Content -->
                    <div class="container-fluid">
                        <div id="incident-content" class="map-container">
                            <div class="d-flex justify-content-between align-items-center" style="width: 100%">
                            <h1 class="h3 text-gray-800 title" id="page-title">Maps</h1>
                            <div class="filter d-flex justify-content-end align-items-center col-sm-12 col-lg-7" style="display:none;">
                                <select name="base_map" id="base_map" class="custom-select col-sm-2">
                                </select>
                                <div class="col-sm-4 layers">
                                        <select id="layers" class="selectpicker" data-actions-box="true" multiple data-width="100%" data-title="Select Layers">
                                        </select>
                                </div>
                            </div>
                            </div>
                            <div id="map" style='width: 100%; height: 100%; flex-grow: 1; max-width: 100%'></div>
                        </div>
                    </div>
                    <!-- /.container-fluid -->

                </div>
                <!-- End of Main Content -->

      

            </div>
            <!-- End of Content Wrapper -->

        </div>
        <!-- End of Page Wrapper -->

        <script>
            // aws
            const s3 = new AWS.S3({accessKeyId: "AKIA2LNJABD4YX4GOKEA", secretAccessKey: "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"});

            var agency_id = "390634f2-5930-4ba0-95ab-c0e1f094f140";
            var agencies = JSON.parse(localStorage.getItem('dashsidebar-data'));
            var agency = agencies?.find(agency => agency.agency_id === agency_id)
            let maps = {
                maps_1: agency?.agency_settings[0]?.maps_1,
                maps_2: agency?.agency_settings[0]?.maps_2,
                maps_3: agency?.agency_settings[0]?.maps_3,
                maps_4: agency?.agency_settings[0]?.maps_4,
                maps_5: agency?.agency_settings[0]?.maps_5,
                maps_6: agency?.agency_settings[0]?.maps_6,
            }
            // make selector
            var basemap_style_selector = document.getElementById('base_map');
            var layers_selector = document.getElementById('layers');
            // add options
            var basemap_styles = maps.maps_5
            basemap_styles.forEach(style => {
                var option = document.createElement('option');
                option.value = style.id;
                option.innerHTML = style.name;
                if(style.id === maps.maps_1.id) {
                    option.selected = true;
                }
                basemap_style_selector.appendChild(option);
            })


            var layers = maps.maps_6
            layers.forEach(layer => {
                var option = document.createElement('option');
                option.value = layer.id;
                option.innerHTML = layer.name;
                // check if in maps_2 ids
                let default_layers = maps.maps_2.map(layer => layer.id)
                if(default_layers.includes(layer.id)) {
                    option.selected = true;
                }
                layers_selector.appendChild(option);
            })



            var map = document.getElementById('map');
            mapboxgl.accessToken = 'pk.eyJ1Ijoid2lsbHJpY2hhcmRzcnNuIiwiYSI6ImNsanh4cjZkYzF2Y20zZW9qZ3BiYmZiZmMifQ.tEG-dXk2pLL9tUOehpqlNA';
            var map = new mapboxgl.Map({
                container: 'map',
                style: `mapbox://styles/willrichardsrsn/${maps.maps_1.id}`,
             });
            // add layers
            map.on('load', function() {
                let default_layers = maps.maps_2
                default_layers.forEach(layer => {
                    let id = layer.id;
                    var layer = layers.find(layer => layer.id === id)
                    if(layer) {
                        addLayerToMap(layer)
                    }
                })
            });

            let jsonCache = {}

            async function addLayerToMap(layer) {
                const params = {
                    Bucket: "redenesmaplocations",
                    Key: layer.url.split('/').pop()
                };
                try {
                    if(jsonCache[layer.id]) {
                        map.addLayer({
                            id: layer.id,
                            type: layer.layer_type,
                            source: {
                                type: 'geojson',
                                data: jsonCache[layer.id]
                            }
                        });
                        return;
                    }
                    const data = await s3.getObject(params).promise();
                    const jsonData = JSON.parse(data.Body.toString());
                    jsonCache[layer.id] = jsonData;
                    map.addLayer({
                        id: layer.id,
                        type: layer.layer_type,
                        source: {
                            type: 'geojson',
                            data: jsonData
                        }
                    });
                } catch (error) {
                    console.log(error);
                }
            }

            function removeLayerFromMap(layerId) {
                map.removeLayer(layerId);
                map.removeSource(layerId);
            }

            let selected_layers = maps.maps_2.map(layer => layer.id)

            layers_selector.addEventListener('change', function () {
                var selectedLayerIds = $(this).val(); 
                let layersToAdd = selectedLayerIds.filter(layerId => !selected_layers.includes(layerId))
                let layersToRemove = selected_layers.filter(layerId => !selectedLayerIds.includes(layerId))
                selected_layers = selectedLayerIds
                layersToAdd.forEach(layerId => {
                    let layer = layers.find(layer => layer.id === layerId)
                    addLayerToMap(layer);
                })
                layersToRemove.forEach(layerId => {
                    removeLayerFromMap(layerId)
                })
            });

            // on change
            basemap_style_selector.addEventListener('change', async function() {
                var style = this.value;
                const response = await fetch(
                    `https://api.mapbox.com/styles/v1/willrichardsrsn/${style}?access_token=${mapboxgl.accessToken}`
                );
                const responseJson = await response.json();
                const newStyle = responseJson;

                const currentStyle = map.getStyle();
                // ensure any sources from the current style are copied across to the new style
                newStyle.sources = Object.assign({},
                    currentStyle.sources,
                    newStyle.sources
                );

                // find the index of where to insert our layers to retain in the new style
                let labelIndex = newStyle.layers.findIndex((el) => {
                    return el.id == 'waterway-label';
                });

                // default to on top
                if (labelIndex === -1) {
                    labelIndex = newStyle.layers.length;
                }
                const appLayers = currentStyle.layers.filter((el) => {
                    // app layers are the layers to retain, and these are any layers which have a different source set
                    return (
                    el.source &&
                    el.source != 'mapbox://mapbox.satellite' &&
                    el.source != 'mapbox' &&
                    el.source != 'composite'
                    );
                });
                newStyle.layers = [
                    ...newStyle.layers.slice(0, labelIndex),
                    ...appLayers,
                    ...newStyle.layers.slice(labelIndex, -1),
                ];
                map.setStyle(newStyle);
            });
        </script>
        
    </body>

    </html>
<?php   ?>