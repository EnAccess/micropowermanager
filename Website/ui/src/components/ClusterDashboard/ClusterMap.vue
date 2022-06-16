<template>

    <widget
        :title="$tc('phrases.clusterMap')"
        id="cluster-map">
        <div style="padding: 10px">
            <div id="map" class="map" style="height: 500px;"></div>
        </div>
    </widget>
</template>

<script>
import { layerGroup } from 'leaflet'

import Widget from '../../shared/widget'

export default {
    name: 'ClusterMap',
    components: {
        Widget,

    },
    props: {
        miniGrids: {
            type: Array,
            default: function () { return []}
        }
    },
    data () {
        return {
            loading: false,
            show: true,
            enableTooltip: true,
            zoom: 20,
            center: this.$store.state.mapSettings.center,
            geojson: null,
            fillColor: '#e4ce7f',
            url: 'http://{s}.tile.osm.org/{z}/{x}/{y}.png',
            attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors',
            map: null,
        }
    },
    computed: {
        options () {
            return {
                onEachFeature: this.onEachFeatureFunction
            }
        },
        onEachFeatureFunction () {
            if (!this.enableTooltip) {
                return () => {}
            }
            return (feature, layer) => {
                layer.bindTooltip('<div>code:' + feature.properties.code + '</div><div>nom: ' + feature.properties.nom + '</div>', {
                    permanent: false,
                    sticky: true
                })
            }
        },

    },
    mounted () {

        this.initMap()
        this.addMetersToMap(this.miniGrids)
    },
    methods: {
        initMap () {
            //create map
            this.map = L.map('map')
                .setView([38.63, -90.23], 9)
                //set tile
            this.tileLayer = L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                maxZoom: 20,
                attribution: ' <span style="cursor:pointer">&copy; MpManager</span>',
            }
            )

            this.tileLayer.addTo(this.map)
            //   this.getLocation()

        },
        strToHex (str) {
            str += 'z4795dfjkldfnjk4lnjkl'
            let hash = 0
            for (let i = 0; i < str.length; i++) {
                hash = str.charCodeAt(i) + ((hash << 5) - hash)
            }
            let colour = '#'
            for (let i = 0; i < 3; i++) {
                let value = (hash >> (i * 8)) & 0xFF
                colour += ('00' + value.toString(16)).substr(-2)
            }
            return colour
        },
        getLocation () {
            axios.get(resources.clusters.geo)
                .then((response) => {
                    let data = response.data.data
                    for (let i = 0; i < data.length; i++) {
                        let cluster = data[i]
                        let popup_flor = 'MyLabel'
                        let content_flor = 'MyContent'
                        let polygon = L.polygon(cluster.geo, {
                            'label': popup_flor,
                            'popup': content_flor,
                            color: this.strToHex(cluster.name)
                        })
                        let parent = this
                        polygon.on('click', function () {
                            parent.alerto(cluster)
                        })
                        polygon.bindPopup(cluster.name)
                        polygon.addTo(this.map)
                    }

                })

        },
        alerto (miniGrid) {
            this.$router.push('/dashboards/mini-grid/' + miniGrid.id)
        },
        onEachFeature (feature, layer) {
            // does this feature have a property named popupContent?
            if (feature.properties && feature.properties.popupContent) {
                layer.bindPopup(feature.properties.popupContent)
            }
        },

        addMetersToMap (miniGrids) {
            this.mapLayer = layerGroup().addTo(this.map)
            let middle_points = [0, 0]
            for (let i in miniGrids) {
                let points = miniGrids[i].location.points.split(',')

                let miniGridMarker = L.marker(
                    points, {
                        icon: L.icon({
                            iconSize: [40, 40],
                            iconUrl: 'https://cdn0.iconfinder.com/data/icons/energy-free/32/Energy_Energy_Panels_Light_Sun_Power-512.png',
                            iconAnchor: [20, 20],
                        })
                    }
                )
                miniGridMarker.bindTooltip('Mini Grid: ' + miniGrids[i].name)

                miniGridMarker.addTo(this.map)
                let parent = this
                miniGridMarker.on('click', function () {
                    parent.alerto(miniGrids[i])
                })
                if (i === 0) {
                    middle_points[0] = parseFloat(points[0])
                    middle_points[1] = parseFloat(points[1])
                } else {
                    middle_points[0] = (middle_points[0] + parseFloat(points[0])) / 2
                    middle_points[1] = (middle_points[1] + parseFloat(points[1])) / 2
                }
            }
            this.map.setView({ lat: middle_points[0], lng: middle_points[1] })
        }
    },

}
</script>
