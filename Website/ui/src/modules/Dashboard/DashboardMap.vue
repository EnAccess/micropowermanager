<template>
    <widget
        :title="$tc('phrases.clusterMap')"
        id="cluster-map">
        <div v-if="loading">
            <loader size="sm"/>
        </div>
        <div v-else>
            <Map
                :geoData="geoData"
                :center="center"
                :constantMarkerUrl="miniGridIcon"
                :markingInfos="markingInfos"
                :constantLocations="constantLocations"
                :parentName="'Top'">
            </Map>
        </div>

    </widget>
</template>

<script>
import Widget from '@/shared/widget'
import Map from '@/shared/Map'
import miniGridIcon from '@/assets/icons/miniGrid.png'
import { MappingService } from '@/services/MappingService'
import { EventBus } from '@/shared/eventbus'
import Loader from '@/shared/Loader.vue'

export default {
    name: 'DashboardMap',
    components: {
        Loader,
        Map,
        Widget,
    },
    props: {
        clustersData: {
            required: true
        },
    },
    data () {
        return {
            mappingService: new MappingService(),
            center: [
                this.$store.getters['settings/getMapSettings'].latitude,
                this.$store.getters['settings/getMapSettings'].longitude],
            clusterGeo: {},
            miniGridIcon: miniGridIcon,
            constantLocations: [],
            markingInfos: [],
            mapData: [],
            loading: false

        }
    },
    mounted () {
        EventBus.$on('clustersCachedDataLoading', (loading) => {
            this.loading = loading
        })
    },
    computed: {
        geoData () {
            let geoData = []
            this.constantLocations = []
            this.mapData.forEach((e) => {
                if (e.geo_data !== null) {
                    this.clusterGeo = e.geo_data
                    this.clusterGeo.clusterId = e.id
                    geoData.push(this.clusterGeo)
                    e.clusterData.mini_grids.map((miniGrid) => {
                        const location = miniGrid.location
                        const lat = location.points.split(',')[0]
                        const lon = location.points.split(',')[1]
                        this.constantLocations.push([lat, lon])
                        let markingInfo = this.mappingService.createMarkingInformation(miniGrid.id, miniGrid.name, null, lat, lon, miniGrid.data_stream)
                        this.markingInfos.push(markingInfo)
                    })
                }
            })
            return geoData
        }
    },
    watch: {
        clustersData (newVal, oldVal) {
            this.$nextTick(() => {
                this.mapData = newVal
            })
        }
    },
}
</script>
