<template>
    <div id="map">

    </div>
</template>
<script>

import 'leaflet/dist/leaflet.css'
import 'leaflet-draw/dist/leaflet.draw.css'
import 'leaflet.markercluster/dist/MarkerCluster.css'
import 'leaflet.markercluster/dist/MarkerCluster.Default.css'
import L from 'leaflet'
import 'leaflet.markercluster'
import 'leaflet.featuregroup.subgroup'
import 'leaflet-draw'
import 'leaflet-bing-layer'

import { MappingService } from '@/services/MappingService'
import { EventBus } from './eventbus'

const debounce = require('debounce')
export default {
    name: 'Map',
    components: {},
    props: {
        clusterName: {
            type: String,
            default: '',
        },
        polygon: {
            type: Boolean,
            default: false
        },
        marker: {
            type: Boolean,
            default: false
        },
        constantMarkerUrl: {
            type: String,
            default: ''
        },
        markerUrl: {
            type: String,
            default: 'leaflet/dist/images/layers.png'

        },
        markerCount: {
            type: Number,
            default: 1
        },
        polyline: {
            type: Boolean,
            default: false
        },
        circlemarker: {
            type: Boolean,
            default: false
        },
        constantLocations: {
            default: null
        },
        rectangle: {
            type: Boolean,
            default: false
        },
        circle: {
            type: Boolean,
            default: false
        },
        geoData: {
            default: null
        },

        edit: {
            type: Boolean,
            default: false
        },
        remove: {
            type: Boolean,
            default: false
        },
        markerLocations: {

            default: null
        },
        markingInfos: {
            default: null
        },
        center: {
            type: Array,
            default: function () {
                return [this.$store.getters['settings/getMapSettings'].latitude, this.$store.getters['settings/getMapSettings'].longitude]
            }
        },
        mutatingCenter: {
            type: Array,
        },
        zoom: {
            type: Number,
            default: function () {
                return this.$store.getters['settings/getMapSettings'].zoom
            }
        },
        maxZoom: {
            type: Number,
            default: 20
        },
        isMeter: {
            type: Boolean,
            default: false
        },
        parentName: {
            type: String,
            default: ''
        },
        position: {
            type: String,
            default: 'topright',
            required: false
        }
    },
    data () {
        return {
            mappingService: new MappingService(),
            osmUrl: 'http://{s}.tile.osm.org/{z}/{x}/{y}.png',
            osmAttrib: '<span style="cursor:pointer">&copy; MpManager</span>',
            osm: null,
            map: null,
            test: null,
            editableLayer: null,
            markersLayer: null,
            dataLoggerActives: null,
            dataLoggerInactives: null,
            geoDataItems: [],
            parentGroup: null,
        }

    },
    destroyed () {
        this.map = null
    },
    computed:{
        mapProvider(){
            if(this.$store.getters['settings/getMapSettings'].provider === 'Bing Maps'){
                return true
            }else{
                return false
            }
        },
        bingMapApiKey(){
            return this.$store.getters['settings/getMapSettings'].bingMapApiKey
        }
    },
    mounted () {
        this.drawingOptions = {
            position: this.position,
            draw: {
                polygon: this.polygon,
                polyline: this.polyline,
                marker: this.marker,
                circlemarker: this.circlemarker,
                rectangle: this.rectangle,
                circle: this.circle,
            },
            edit: {
                featureGroup: null,
                remove: this.remove,
                edit: this.edit
            }

        }
        this.generateMap(this.drawingOptions, this.center)

        let drawLayer = this.editableLayer
        let service = this.mappingService
        let markerCount = this.markerCount
        let markersLayer = this.markersLayer
        let map = this.map

        this.map.on('draw:edited', function (e) {

            let editedItems = []
            let editedLayers = e.layers
            editedLayers.eachLayer((layer) => {

                let type = layer._latlngs === undefined ? 'Marker' : 'Polygon'
                if (type === 'Marker') {

                    let geoDataItem = {
                        id: layer._tooltip._content.split('/')[1],
                        meterSerial: layer._tooltip._content.split('/')[0],
                        lat: layer._latlng.lat,
                        lng: layer._latlng.lng
                    }
                    editedItems.push(geoDataItem)
                } else {

                    let sumLat = 0
                    let sumLon = 0

                    for (let i = 0; i < layer._latlngs[0].length; i++) {

                        let coordinates = layer._latlngs[0][i]
                        sumLat += coordinates.lat
                        sumLon += coordinates.lng
                    }
                    let avgLat = sumLat / layer._latlngs[0].length
                    let avgLon = sumLon / layer._latlngs[0].length
                    let geoDataItem = service.manualDrawingLocationConvert({
                        leaflet_id: layer._leaflet_id,
                        type: 'manual',
                        geojson: {
                            type: type,
                            coordinates: layer._latlngs,
                        },
                        display_name: '',
                        selected: false,
                        lat: avgLat,
                        lon: avgLon,
                    })

                    editedItems.push(geoDataItem)
                }
            })
            EventBus.$emit('getEditedGeoDataItems', editedItems)

        })
        this.map.on('draw:toolbaropened', function () {

            map.removeLayer(markersLayer)
            let markers = markersLayer.getLayers()
            drawLayer.eachLayer((layer) => {
                let type = layer._latlngs === undefined ? 'Marker' : 'Polygon'
                if (type === 'Marker') {
                    drawLayer.removeLayer(layer)
                }
            })

            for (let i = 0; i < markers.length; i++) {
                markers[i].setOpacity(1)
                markers[i].addTo(drawLayer)
            }
            map.addLayer(drawLayer)

        })
        this.map.on('draw:deleted', function (e) {
            let deletedItems = []
            let deletedLayers = e.layers
            deletedLayers.eachLayer((layer) => {
                deletedItems.push(layer._leaflet_id)
            })
            EventBus.$emit('getDeletedGeoDataItems', deletedItems)
        })
        this.map.on('draw:created', function (e) {

            let type = e.layerType
            let layer = e.layer
            if (type === 'marker') {
                type = 'Marker'
                let drawedLayers = drawLayer.getLayers()
                let polygon = drawedLayers[0]
                let bounds = polygon.getBounds()
                if (markerCount !== 0) {
                    if (drawedLayers.length > markerCount) {
                        for (let i = 0; i <= drawedLayers.length - markerCount; i++) {
                            let marker = drawedLayers[i]

                            if (marker._icon !== undefined && !marker._icon.currentSrc.includes('miniGrid')) {

                                drawLayer.removeLayer(marker)
                            }

                        }
                    }
                }

                if (bounds.contains(layer._latlng)) {
                    drawLayer.addLayer(layer)
                    let geoDataItem = {
                        leaflet_id: layer._leaflet_id,
                        type: 'manual',
                        geojson: {
                            type: type,
                            coordinates: layer._latlng,
                        },
                        display_name: '',
                        selected: false,
                        draw_type: 'draw',
                        lat: 0,
                        lon: 0,
                    }
                    EventBus.$emit('getDrawedMarker', geoDataItem)
                } else {
                    EventBus.$emit('markerError', 'Please position your mini-grid within the selected cluster boundaries.')
                }

            } else if (type === 'polygon') {
                type = 'Polygon'
                let feature = layer.feature = layer.feature || {}
                feature.type = feature.type || 'Feature'
                let props = feature.properties = feature.properties || {}
                props.draw_type = 'draw'
                props.selected = false

                drawLayer.addLayer(layer)

                let sumLat = 0
                let sumLon = 0
                for (let i = 0; i < layer._latlngs[0].length; i++) {
                    let coordinates = layer._latlngs[0][i]
                    sumLat += coordinates.lat
                    sumLon += coordinates.lng
                }

                let avgLat = sumLat / layer._latlngs[0].length
                let avgLon = sumLon / layer._latlngs[0].length
                let geoDataItem = service.manualDrawingLocationConvert({
                    leaflet_id: layer._leaflet_id,
                    type: 'manual',
                    geojson: {
                        type: type,
                        coordinates: layer._latlngs,
                    },
                    display_name: '',
                    selected: false,
                    draw_type: 'draw',
                    lat: avgLat,
                    lon: avgLon,
                })
                EventBus.$emit('getDrawedLocation', geoDataItem)
            }

        })
    },
    methods: {
        generateMap (options, center) {
            this.mapInitialized = true
            this.map = L.map('map').setView(center, this.zoom)
            if(this.mapProvider){
                L.tileLayer.bing(this.bingMapApiKey, {maxZoom: this.maxZoom, attribution: this.osmAttrib}).addTo(this.map)
            }else{
                L.tileLayer(this.osmUrl, { maxZoom: this.maxZoom, attribution: this.osmAttrib }).addTo(this.map)
            }
            this.editableLayer = new L.FeatureGroup()
            this.markersLayer = new L.markerClusterGroup({
                chunkedLoading: true,
                spiderfyOnMaxZoom: false
            })


            this.map.addLayer(this.editableLayer)
            options.edit.featureGroup = this.editableLayer

            if (options.draw.marker) {
                let marker = L.Icon.extend({
                    options: {
                        iconSize: [40.4, 44],
                        iconAnchor: [20, 43],
                        popupAnchor: [0, -51],
                        iconUrl: this.markerUrl
                    }
                })
                options.draw.marker = {}
                options.draw.marker.icon = new marker()
            }

            this.drawControl = new L.Control.Draw(options)
            if (this.parentName === 'Top-MiniGrid') {
                this.dataLoggerActives = L.featureGroup.subGroup(this.markersLayer)
                this.dataLoggerInactives = L.featureGroup.subGroup(this.markersLayer)
                let control = L.control.layers(null, null, { collapsed: false })
                control.addOverlay(this.dataLoggerActives, 'Data Stream Active')
                control.addOverlay(this.dataLoggerInactives, 'Data Stream Inactive')
                control.addTo(this.map)
            }
            this.map.addControl(this.drawControl)
        },
        setLocation (geoData) {
            this.geoDataItems = []
            this.editableLayer.clearLayers()
            let editableLayer = this.editableLayer
            let nonEditableLayers = new L.FeatureGroup()
            const geographicalInformation = geoData
            for (let i in geographicalInformation) {
                let geoData = geographicalInformation[i].geo !== undefined ? geographicalInformation[i].geo : geographicalInformation[i]
                let geoType = geoData.geojson.type
                let coordinatesClone = []
                coordinatesClone[0] = []
                geoData.geojson.coordinates[0].forEach(e => {
                    coordinatesClone[0].push([e[1], e[0]])
                })
                let drawing = {
                    'type': 'FeatureCollection',
                    'crs': {
                        'type': 'name',
                        'properties': {
                            'name': 'urn:ogc:def:crs:OGC:1.3:CRS84'
                        }
                    },
                    'features': [{
                        'type': 'Feature',
                        'properties': {
                            'popupContent': geoData.display_name,
                            'draw_type': geoData.draw_type === undefined ? 'set' : geoData.draw_type,
                            'selected': geoData.selected === undefined ? false : geoData.selected,
                            'clusterId': geoData.clusterId === undefined ? -1 : geoData.clusterId,
                        },
                        'geometry': {
                            'type': geoType,
                            'coordinates': geoData.searched ? geoData.geojson.coordinates : coordinatesClone
                        }
                    }]
                }

                let polygonColor = this.mappingService.strToHex(geoData.display_name)
                let geoDataItems = this.geoDataItems
                let router = this.$router
                let map = this.map
                let parent = this.parentName
                L.geoJson(drawing,
                    {
                        style: { fillColor: polygonColor, color: polygonColor },
                        onEachFeature: function (feature, layer) {

                            let type = layer.feature.geometry.type
                            let clusterId = layer.feature.properties.clusterId
                            if (type === 'Polygon' && clusterId !== -1) {
                                layer.on('click', function () {
                                    router.push({ path: '/clusters/' + clusterId })
                                })
                            }

                            if (parent === 'MiniGrid') {
                                nonEditableLayers.addLayer(layer)
                                map.addLayer(nonEditableLayers)
                            } else if (parent === 'Top' || parent === 'Top-MiniGrid') {
                                layer.bindTooltip('Cluster :' + layer.feature.properties.popupContent)
                                editableLayer.addLayer(layer)

                            } else {
                                editableLayer.addLayer(layer)
                            }
                            let geoDataItem = {
                                leaflet_id: layer._leaflet_id,
                                type: 'manual',
                                geojson: {
                                    type: geoData.geojson.type,
                                    coordinates: geoData.searched === true ? coordinatesClone : geoData.geojson.coordinates
                                },
                                searched: false,
                                display_name: geoData.display_name,
                                selected: feature.properties.selected,
                                draw_type: feature.properties.draw_type,
                                lat: geoData.lat,
                                lon: geoData.lon,
                            }
                            geoDataItems.push(geoDataItem)
                        }
                    })

                this.map.setView([geoData.lat, geoData.lon], this.zoom)
            }
            EventBus.$emit('getSearchedGeoDataItems', this.geoDataItems)
        },
        setMarker (markerLocations, isConstant) {

            if (isConstant) {
                markerLocations.forEach((e) => {
                    let markerIcon = L.icon({
                        iconSize: [40.4, 44],
                        iconAnchor: [20, 43],
                        popupAnchor: [0, -51],
                        iconUrl: this.constantMarkerUrl
                    })
                    let constantMarker = L.marker(e, { icon: markerIcon })

                    if (this.markingInfos !== null) {
                        let markingInfo = this.markingInfos.filter(x => x.lat === e[0] && x.lon === e[1])[0]
                        if (markingInfo !== undefined) {
                            constantMarker.bindTooltip('Mini Grid: ' + markingInfo.name)
                            let parent = this
                            constantMarker.on('click', function () {
                                parent.routeToDetail(markingInfo.id, markingInfo.name)
                            })
                        }

                    }

                    constantMarker.addTo(this.map)
                })
            } else {
                this.markersLayer.clearLayers()
                let drawedLayers = this.editableLayer.getLayers()
                markerLocations.forEach((e, k) => {
                    let polygon = drawedLayers[0]
                    if (this.markerCount !== 0) {
                        if (drawedLayers.length > this.markerCount) {
                            for (let i = 0; i <= drawedLayers.length - this.markerCount; i++) {
                                let marker = drawedLayers[i]
                                if (marker._icon) {
                                    if (!marker._icon.currentSrc.includes('miniGrid')) {
                                        this.editableLayer.removeLayer(marker)
                                    }

                                }

                            }
                        }
                    }
                    let markerIcon = L.icon({
                        iconSize: [40.4, 44],
                        iconAnchor: [20, 43],
                        popupAnchor: [0, -51],
                        iconUrl: this.markerUrl
                    })
                    let newMarker = L.marker(e, { icon: markerIcon })
                    if (this.markingInfos !== null) {
                        let markingInfo = this.markingInfos.filter(x => x.lat === e[0] && x.lon === e[1])[0]
                        if (markingInfo !== undefined) {
                            if (this.isMeter) {
                                newMarker.bindTooltip(markingInfo.serialNumber + '/' + markingInfo.id)
                                let parent = this
                                newMarker.on('click', function () {
                                    parent.routeToDetail(markingInfo.serialNumber, markingInfo.name)
                                })
                                if (k === markerLocations.length - 1) {
                                    let editableMarker = L.marker(e, { icon: markerIcon }).setOpacity(0)
                                    editableMarker.addTo(this.editableLayer)
                                }
                            } else {

                                newMarker.bindTooltip('Mini Grid: ' + markingInfo.name)
                                let parent = this
                                newMarker.on('click', function () {
                                    parent.routeToDetail(markingInfo.id, markingInfo.name)
                                })

                                if (markingInfo.dataStream > 0) {
                                    newMarker.addTo(this.dataLoggerActives)
                                } else {
                                    newMarker.addTo(this.dataLoggerInactives)
                                }

                            }

                        }

                    }
                    newMarker.addTo(this.markersLayer)

                    if (polygon !== undefined) {
                        let bounds = polygon.getBounds()
                        if (bounds.contains(newMarker._latlng)) {
                            newMarker.addTo(this.editableLayer)
                        } else {
                            if (this.isMeter) {
                                newMarker.addTo(this.editableLayer)
                                let lat = newMarker._latlng.lat
                                let lon = newMarker._latlng.lng
                                this.map.setView({ lat, lon }, this.zoom)
                            } else {
                                this.editableLayer.removeLayer(newMarker)
                            }
                            EventBus.$emit('markerError', 'Please position your mini-grid within the selected cluster boundaries.')
                        }
                    } else {
                        let lat = newMarker._latlng.lat
                        let lon = newMarker._latlng.lng
                        this.map.setView({ lat, lon }, this.zoom)
                    }

                })

                this.map.addLayer(this.markersLayer)
                if (this.map.hasLayer(this.dataLoggerActives)) {
                    this.map.addLayer(this.dataLoggerActives)
                    this.map.addLayer(this.dataLoggerInactives)
                }

            }

        },
        routeToDetail (Id, name) {
            if (name === null) {
                this.$router.push('/meters/' + Id)
            } else {
                this.$router.push('/dashboards/mini-grid/' + Id)
            }

        },
        reGenerateMap (mutatingCenter) {
            this.map.flyTo(mutatingCenter, this.zoom, this.drawingOptions)
        },
        getLatLng(){
            let latlng
            let zoom
            this.map.on('move',function (e){
                zoom = Math.round(e.target._zoom)
                EventBus.$emit('mapZoom',zoom)

            })
            latlng = {
                lat: this.map.getCenter().lat.toFixed(4),
                lng: this.map.getCenter().lng.toFixed(4)
            }
            EventBus.$emit('mapEvent', latlng)
        },
        alertNotify (type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message
            })
        },
    },

    watch: {
        zoom(){
            this.map.setZoom(this.zoom)
        },
        mutatingCenter(){
            this.reGenerateMap(this.mutatingCenter)
        },
        geoData: debounce(function () {
            this.setLocation(this.geoData)
        }, 10),
        markerLocations: debounce(function () {
            this.setMarker(this.markerLocations, false)
        }, 10),
        constantLocations: debounce(function () {

            this.setMarker(this.constantLocations, true)
        }, 10),
    }
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
