<template>
    <div v-if="Object.keys(clusterData.clusterData).length">
        <md-toolbar style="margin-bottom: 3rem" class="md-dense">
            <div class="md-toolbar-row">
                <div class="md-toolbar-section-start">
                    {{ $tc('words.cluster') }} : <span style="font-size: 1.3rem; font-weight: bold"
                                                       v-if="clusterData"> {{ clusterData.name }}</span>
                </div>
                <div class="md-toolbar-section-end">
                    <md-button class="md-raised" @click="updateCacheData">
                        <md-icon>update</md-icon>
                        {{ $tc('phrases.refreshData') }}
                        <md-progress-bar v-if="updateProgress" md-mode="indeterminate"></md-progress-bar>
                    </md-button>
                </div>
            </div>
        </md-toolbar>
        <div class="md-layout md-gutter md-size-100">
            <div class="md-layout-item
       md-size-100">
                <box-group
                    :cluster="clusterData.clusterData"
                />

            </div>
            <div class="md-layout-item
       md-size-100">
                <financial-overview
                    :cluster-id="clusterId"
                    :financial-data="clusterData.citiesRevenue"
                    @complete="addRevenue"
                />

            </div>
            <div class="md-layout-item
       md-size-100" style="margin-top: 2vh;">
                <md-card>
                    <md-card-content>
                        <Map
                            :geoData="mappingService.focusLocation(clusterData.clusterData.geo_data)"
                            :markerLocations="constantLocations"
                            :markerUrl="miniGridIcon"
                            :center="center"
                            :markingInfos="markingInfos"
                            :parentName="'Top-MiniGrid'"
                            :zoom="7"
                        />
                    </md-card-content>

                </md-card>
            </div>
            <div class="md-layout-item
       md-size-100">
                <revenue-trends
                    :cluster-id="clusterId"
                    :cluster-revenue-analysis="clusterData.revenueAnalysis"/>
            </div>
        </div>

    </div>
</template>

<script>

import '../../shared/TableList'
import Map from '../../shared/Map'
import BoxGroup from './BoxGroup'
import FinancialOverview from './FinancialOverview'
import RevenueTrends from './RevenueTrends'
import { MappingService } from '@/services/MappingService'
import miniGridIcon from '../../assets/icons/miniGrid.png'
import { mapGetters } from 'vuex'

export default {
    name: 'ClusterList',
    components: {
        RevenueTrends,
        FinancialOverview,
        BoxGroup,
        Map
    },
    data () {
        return {
            mappingService: new MappingService(),
            miniGridIcon: miniGridIcon,
            clusterId: null,
            geoData: null,
            constantLocations: [],
            markingInfos: [],
            updateProgress: false,
            center: [
                this.$store.getters['settings/getMapSettings'].latitude,
                this.$store.getters['settings/getMapSettings'].longitude
            ],
            boxData: {
                'revenue': {
                    'period': '-',
                    'total': '-',
                },
                'people': '-',
                'meters': '-',
            },

        }
    },
    created () {
        this.clusterId = this.$route.params.id
        this.$store.dispatch('clusterDashboard/get', this.$route.params.id)
    },
    mounted () {
        this.setMiniGridsOfClusterMapSettings()
    },
    methods: {
        async setMiniGridsOfClusterMapSettings () {
            this.center = [this.clusterData.geo_data.lat, this.clusterData.geo_data.lon]
            this.boxData['mini_grids'] = this.clusterData.clusterData.mini_grids.length
            for (let i in this.clusterData.clusterData.mini_grids) {
                let miniGrids = this.clusterData.clusterData.mini_grids
                let points = miniGrids[i].location.points.split(',')
                let lat = points[0]
                let lon = points[1]
                let markingInfo = this.mappingService.createMarkingInformation(miniGrids[i].id, miniGrids[i].name,
                    null, lat, lon, miniGrids[i].data_stream)
                this.markingInfos.push(markingInfo)
                this.constantLocations.push([lat, lon])
            }
        },
        async updateCacheData () {
            this.updateProgress = true
            try {
                await this.$store.dispatch('clusterDashboard/update')
                this.alertNotify('success', 'Dashboard refreshed successfully.')
            } catch (e) {
                this.alertNotify('error', e.message)
            }
            this.updateProgress = false
        },
        addRevenue (data) {
            this.boxData['revenue'] = {
                'total': data['sum'],
                'period': data['period']
            }
        },
        addConnections (data) {
            this.boxData['people'] = data
            this.boxData['meters'] = data
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
    computed: {
        ...mapGetters({
            clusterData: 'clusterDashboard/getClusterData'
        })
    }

}
</script>

<style>


</style>

