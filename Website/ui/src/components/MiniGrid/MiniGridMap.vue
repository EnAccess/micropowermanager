<template>

    <widget
        :title="$tc('phrases.miniGridMap')"
        id="miniGrid-map">


        <Map :geoData="geoData"
             :center="center"
             :markerLocations="markerLocations"
             :constantLocations="constantLocations"
             :constantMarkerUrl="miniGridIcon"
             :markerUrl="meterIcon"
             :edit="true"
             :markingInfos="markingInfos"
             :isMeter="true"
             :parentName="'MiniGrid'"

        />

    </widget>
</template>
<script>
import Widget from '../../shared/widget'
import { ClusterService } from '@/services/ClusterService'
import { MappingService } from '@/services/MappingService'
import Map from '../../shared/Map'
import { MiniGridService } from '@/services/MiniGridService'
import { MeterService } from '@/services/MeterService'
import miniGridIcon from '../../assets/icons/miniGrid.png'
import meterIcon from '../../assets/icons/meter.png'
import { EventBus } from '@/shared/eventbus'
import { notify } from '@/mixins/notify'
import Loader from '@/shared/Loader.vue'

export default {
    name: 'MiniGridMap',
    components: {
        Loader,
        Widget,
        Map,
    },
    mixins: [notify],
    data () {
        return {
            clusterService: new ClusterService(),
            mappingService: new MappingService(),
            miniGridService: new MiniGridService(),
            meterService: new MeterService(),
            meterIcon: meterIcon,
            miniGridIcon: miniGridIcon,
            miniGridLatLng: {
                lat: null,
                lon: null
            },
            meterLatLng: {
                lat: null,
                lon: null
            },
            markerLocations: [],
            constantLocations: [],
            markingInfos: [],
            loading: false,
            show: true,
            geoData: null,
            center: [this.$store.getters['settings/getMapSettings'].latitude, this.$store.getters['settings/getMapSettings'].longitude],
            miniGrids: null,
            clusterLayer: null,
            clusterId: null,
            clusterGeo: {},
            meters: [],

        }
    },
    computed: {},
    props: {
        miniGridId: {
            type: String,
            required: true
        }
    },
    mounted () {
        this.getMiniGrid(this.miniGridId)

        EventBus.$on('getEditedGeoDataItems', (editedItems) => {
            this.$swal({
                title: this.$tc('phrases.relocateMeter', 1),
                text: this.$tc('phrases.relocateMeter', 2),
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: this.$tc('words.relocate'),
                cancelButtonText: this.$tc('words.dismiss')
            }).then((result) => {
                if (result) {
                    let meters = []
                    editedItems.forEach((e) => {
                        let meter = {
                            id: e.id,
                            lat: e.lat.toFixed(5),
                            lng: e.lng.toFixed(5),
                        }
                        meters.push(meter)
                    })
                    this.updateEditedMeters(meters)
                }

            })

        })
    },
    methods: {
        async updateEditedMeters (meters) {
            try {
                await this.meterService.updateMeter(meters)
                this.alertNotify('success', this.$tc('phrases.relocateMeter', 3))
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        async getGeoData (clusterId) {
            try {
                this.clusterId = clusterId
                let clusterGeoData = await this.clusterService.getClusterGeoLocation(clusterId)
                this.center = [clusterGeoData.lat, clusterGeoData.lon]
                this.geoData = this.mappingService.focusLocation(clusterGeoData)
            } catch (e) {
                this.alertNotify('error', e.message)
            }
        },
        getMiniGrid: async function (miniGridId) {
            try {
                this.loading = true
                this.markerLocations = []
                this.constantLocations = []
                this.markingInfos = []
                let miniGridGeoData = await this.miniGridService.getMiniGridGeoData(miniGridId)
                await this.getGeoData(miniGridGeoData.cluster_id)
                let points = miniGridGeoData.location.points.split(',')
                this.miniGridLatLng.lat = points[0]
                this.miniGridLatLng.lon = points[1]
                this.constantLocations.push([this.miniGridLatLng.lat, this.miniGridLatLng.lon])
                await this.getMiniGridMeters(miniGridId)

            } catch (e) {
                this.alertNotify('error', e.message)
            }
            this.loading = false
            this.$nextTick(() => {
                this.mapData = this.geoData
            })
        },
        async getMiniGridMeters (miniGridId) {
            try {

                this.meters = await this.meterService.getMeterGeos(miniGridId)
                this.markerLocations = []
                this.markingInfos = []
                for (let i in this.meters) {
                    let points = this.meters[i].meter_parameter.address.geo.points.split(',')
                    this.meterLatLng.lat = points[0]
                    this.meterLatLng.lon = points[1]
                    let markingInfo = this.mappingService.createMarkingInformation(this.meters[i].id, null, this.meters[i].serial_number, points[0], points[1], -1)
                    this.markingInfos.push(markingInfo)
                    this.markerLocations.push([this.meterLatLng.lat, this.meterLatLng.lon])
                }
            } catch (e) {

                this.alertNotify('error', e.message)
            }

        },
    },

}
</script>

