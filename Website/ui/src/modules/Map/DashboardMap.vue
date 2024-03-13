<template>
    <div id="map"></div>
</template>

<script>
import { ICON_OPTIONS, ICONS, MARKER_TYPE } from '@/services/MappingService'
import { notify, sharedMap } from '@/mixins'

export default {
    name: 'DashboardMap',
    mixins: [sharedMap, notify],
    methods: {
        drawClusters() {
            this.editableLayer.clearLayers()
            this.mappingService.geoData.map((geoData) => {
                const geoType = geoData.geojson.type
                const coordinatesClone = geoData.geojson.coordinates[0].reduce(
                    (acc, coord) => {
                        acc[0].push([coord[1], coord[0]])
                        return acc
                    },
                    [[]],
                )
                const drawing = {
                    type: 'FeatureCollection',
                    crs: {
                        type: 'name',
                        properties: {
                            name: 'urn:ogc:def:crs:OGC:1.3:CRS84',
                        },
                    },
                    features: [
                        {
                            type: 'Feature',
                            properties: {
                                popupContent: geoData.display_name,
                                draw_type:
                                    geoData.draw_type === undefined
                                        ? 'set'
                                        : geoData.draw_type,
                                selected:
                                    geoData.selected === undefined
                                        ? false
                                        : geoData.selected,
                                clusterId:
                                    geoData.clusterId === undefined
                                        ? -1
                                        : geoData.clusterId,
                            },
                            geometry: {
                                type: geoType,
                                coordinates: geoData.searched
                                    ? geoData.geojson.coordinates
                                    : coordinatesClone,
                            },
                        },
                    ],
                }
                const polygonColor = this.mappingService.strToHex(
                    geoData.display_name,
                )
                // "this"  cannot be used inside the L.geoJson function
                const editableLayer = this.editableLayer
                const geoDataItems = this.geoDataItems
                const parent = this
                const drawnCluster = L.geoJson(drawing, {
                    style: { fillColor: polygonColor, color: polygonColor },
                    onEachFeature: function (feature, layer) {
                        const type = layer.feature.geometry.type
                        const clusterId = layer.feature.properties.clusterId
                        if (type === 'Polygon' && clusterId !== -1) {
                            layer.on('click', () => {
                                parent.routeToDetail('/clusters', clusterId)
                            })
                        }

                        editableLayer.addLayer(layer)
                        const geoDataItem = {
                            leaflet_id: layer._leaflet_id,
                            type: 'manual',
                            geojson: {
                                type: geoData.geojson.type,
                                coordinates:
                                    geoData.searched === true
                                        ? coordinatesClone
                                        : geoData.geojson.coordinates,
                            },
                            searched: false,
                            display_name: geoData.display_name,
                            selected: feature.properties.selected,
                            draw_type: feature.properties.draw_type,
                            lat: geoData.lat,
                            lon: geoData.lon,
                        }
                        geoDataItems.push(geoDataItem)
                    },
                })
                const bounds = drawnCluster.getBounds()
                this.map.fitBounds(bounds)
            })
        },
        setMiniGridMarkers() {
            this.mappingService.markingInfos
                .filter(
                    (markingInfo) =>
                        markingInfo.markerType === MARKER_TYPE.MINI_GRID,
                )
                .map((markingInfo) => {
                    const miniGridMarkerIcon = L.icon({
                        ...ICON_OPTIONS,
                        iconUrl: ICONS[markingInfo.markerType],
                    })
                    const miniGridMarker = L.marker(
                        [markingInfo.lat, markingInfo.lon],
                        { icon: miniGridMarkerIcon },
                    )
                    miniGridMarker.bindTooltip('Mini Grid: ' + markingInfo.name)
                    miniGridMarker.addTo(this.map)
                })
        },
    },
}
</script>

<style scoped>
#map {
    height: 100%;
    min-height: 500px;
    width: 100%;
}

.leaflet-draw-actions a {
    background: white !important;
}
</style>
