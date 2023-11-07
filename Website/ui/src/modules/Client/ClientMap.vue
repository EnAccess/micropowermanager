<template>

    <widget
        :title="$tc('words.detail',2)"
        id="client-map"
    >
        <Map
            :zoom="14"
            :center="center"
            :markerLocations="markerLocations"
            :markingInfos="markingInfos"
            :isMeter="true"
            :edit="true"
            :markerUrl="meterIcon"
        />

    </widget>
</template>

<script>
import Widget from '../../shared/widget'
import { ClusterService } from '@/services/ClusterService'
import { MappingService } from '@/services/MappingService'
import Map from '../../shared/Map'
import { MiniGridService } from '@/services/MiniGridService'
import { PersonService } from '@/services/PersonService'
import { MeterService } from '@/services/MeterService'
import meterIcon from '../../assets/icons/meter.png'
import { EventBus } from '@/shared/eventbus'

export default {
    name: 'ClientMap',
    components: {
        Widget,
        Map,
    },
    props: {
        meterIds: {
            default: null
        }
    },
    data () {
        return {
            clusterService: new ClusterService(),
            mappingService: new MappingService(),
            miniGridService: new MiniGridService(),
            personService: new PersonService(),
            meterService: new MeterService(),
            markerLocations: [],
            meterIcon: meterIcon,
            meterLatLng: {
                lat: null,
                lon: null
            },
            meters: [],
            markingInfos: [],
            loading: false,
            show: true,
            geoData: null,
            center: [this.$store.getters['settings/getMapSettings'].latitude, this.$store.getters['settings/getMapSettings'].longitude],
            miniGrids: null,
            clusterLayer: null,
            clusterGeo: {}
        }
    },
    computed: {},
    mounted () {

        this.getMeterPoints(this.meterIds)
        EventBus.$on('getEditedGeoDataItems', (editedItems) => {
            this.$swal({
                title: this.$tc('phrases.relocateMeter', 0),
                text: this.$tc('phrases.relocateMeter', 1),
                type: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: this.$tc('words.relocate'),
                cancelButtonText: this.$tc('words.dismiss'),
            }).then((result) => {

                if (result) {
                    let meters = []
                    editedItems.forEach(async (e) => {
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
        async getGeoData () {
            this.clusterGeo = {}
            let clusters = await this.clusterService.getClusters()
            let geoData = []

            clusters.forEach((e) => {
                this.clusterGeo = e.geo[0]
                this.clusterGeo.clusterId = e.id
                geoData.push(this.clusterGeo)

            })
            this.geoData = geoData

        },

        async getMiniGrids () {
            this.miniGrids = await this.miniGridService.getMiniGrids()
        },
        getMeterPoints (metersIds) {
            this.markingInfos = []
            metersIds.forEach(async (e) => {
                let meter = await this.meterService.getMeterDetails(e)
                this.meters.push(meter)
                for (let i in this.meters) {
                    let points = this.meters[i].geo
                    this.meterLatLng.lat = points[0]
                    this.meterLatLng.lon = points[1]
                    let markingInfo = this.mappingService.createMarkingInformation(this.meters[i].id, null, this.meters[i].serial_number, points[0], points[1], -1)
                    this.markingInfos.push(markingInfo)

                    this.markerLocations.push([this.meterLatLng.lat, this.meterLatLng.lon])
                }
            })

        },
        alertNotify (type, message) {
            this.$notify({
                group: 'notify',
                type: type,
                title: type + ' !',
                text: message,
                speed: 0
            })
        },
    },

}
</script>


