<template>
    <widget
        :title="'Details'+ (currentMap === 0 ? '[Open Street Maps]': '[Google Maps]')"
    >
        <div slot="tabbar">
            <md-field>
                <label for="map">Map Provider</label>
                <md-select md-selected="mapSelected">
                    <md-option value="0">Open Street Map</md-option>
                    <md-option value="1">Google Maps</md-option>
                </md-select>
            </md-field>
        </div>


        <div v-if="currentMap===0">
            <div id="map" class="map"></div>
        </div>

        <div v-else>
            <GmapMap
                :zoom="zoom"
                :center="center"
                map-type-id="terrain"
                style="width: 100%; height: 300px"
            >
                <GmapMarker
                    :key="index"
                    v-for="(m, index) in markers"
                    :position="makePosition(m)"
                    :clickable="true"
                    :draggable="false"
                    @click="center=m.position"
                    :shape="shape"
                />
            </GmapMap>
        </div>
    </widget>

</template>

<script>
import { Meters } from '@/classes/person/meters'
import { EventBus } from '@/shared/eventbus'
import Widget from '../../shared/widget'

export default {
    name: 'Mapiko',
    components: {
        Widget
    },
    props: {
        meters: {},
    },
    data () {
        return {
            map: null,
            tileLayer: null,
            center: { lat: 0, lng: 0 },
            zoom: 7,
            markers: [],
            currentMap: 0,
            gMap: {},
            shape: {
                coords: [10, 10, 10, 15, 15, 15, 15, 10],
                type: 'poly'
            },
        }
    },
    created () {
        EventBus.$on('map', data => {

            let marker = this.markers.filter(x => x.id === data)
            if (marker.length > 0) {

                this.map.setView(marker[0].coords, 22)
                this.center = this.makePosition(marker[0])
                this.zoom = 14
            }

        })

    },
    mounted () {
        this.initMap()
        this.addMeters()

    },
    watch: {
        meters () {
        },
        currentMap () {
            let self = this
            this.markers = []
            setTimeout(function () {
                self.initMap()
                self.addMeters()
            }, 1000)
        }
    },
    methods: {

        mapSelected (val) {
            this.currentMap = val
        },

        addMeters () {
            for (let i = 0; i < this.meters.length; i++) {
                new Meters().getMeterDetails(this.meters[i]).then(meters => {
                    this.addMarker(this.newMarker(meters))
                })

            }

        },
        newMarker (meter) {
            let marker = {
                id: meter.id,
                name: meter.serial_number +
                        '<br> Tariff: ' + meter.tariff.name +
                        '<br> Phase: ' + meter.phase +
                        '<br> Max Current:' + meter.max_current + 'A ' +
                        '<br><a href="#/meters/' + meter.serial_number + '" >Detail</a>',

                type: 'marker',
                coords: meter.geo,
            }
            return marker
        },
        initMap () {
            if (this.currentMap === 0) {
                this.map = L.map('map')
                    .setView([38.63, -90.23], 12)
                this.tileLayer = L.tileLayer(
                    'https://cartodb-basemaps-{s}.global.ssl.fastly.net/rastertiles/voyager/{z}/{x}/{y}.png',
                    {
                        maxZoom: 18,
                        attribution: ' <span style="cursor:pointer">&copy; MpManager</span>',
                    }
                )

                this.tileLayer.addTo(this.map)
            }

        },
        addMarker (_marker) {
            if (this.currentMap === 0) {
                let marker = L.marker(_marker.coords, {
                    icon: L.icon({
                        iconSize: [40, 40],
                        iconUrl: 'https://cdn2.iconfinder.com/data/icons/gadgets-and-devices/48/87-128.png',
                        iconAnchor: [20, 20],
                    })
                })
                marker.bindPopup(_marker.name)

                marker.addTo(this.map)
                this.markers.push(_marker)
                this.map.setView(_marker.coords, 16)
            } else {
                this.markers.push(_marker)
                this.center = this.makePosition(_marker)
            }
        },
        makePosition (marker) {
            return { lat: parseFloat(marker.coords[0]), lng: parseFloat(marker.coords[1]) }
        }

    },

}
</script>

<style>
    .map {
        height: 600px;
    }
</style>
