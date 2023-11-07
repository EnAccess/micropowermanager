<template>
    <div>
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
                        <md-progress-bar v-if="loading" md-mode="indeterminate"></md-progress-bar>
                    </md-button>
                </div>
            </div>
        </md-toolbar>
        <div class="md-layout md-gutter">
            <div class="md-layout-item md-size-100">
                <box-group
                    :cluster="clusterData"
                />

            </div>
            <div class="md-layout-item md-size-100">
                <financial-overview :revenue="revenue"
                                    :periodChanged="financialOverviewPeriodChanged"
                />
            </div>
            <div class="md-layout-item md-size-100" style="margin-top: 2vh;">
                <md-card>
                    <md-card-content>
                        <div v-if="loading">
                            <loader size="sm"/>
                        </div>
                        <div v-else>
                            <Map
                                :geoData="mappingService.focusLocation(mapData)"
                                :markerLocations="constantLocations"
                                :markerUrl="miniGridIcon"
                                :center="center"
                                :markingInfos="markingInfos"
                                :parentName="'Top-MiniGrid'"
                                :zoom="7"
                            />
                        </div>
                    </md-card-content>

                </md-card>
            </div>
            <div class="md-layout-item md-size-100">
                <revenue-trends :clusterId="clusterId" :clusterRevenueAnalysis="clusterData.revenueAnalysis"/>
            </div>
        </div>
    </div>
</template>

<script>

import '@/shared/TableList'
import Map from '@/shared/Map'
import BoxGroup from './BoxGroup'
import RevenueTrends from './RevenueTrends'
import { MappingService } from '@/services/MappingService'
import miniGridIcon from '@/assets/icons/miniGrid.png'
import { notify } from '@/mixins/notify'
import FinancialOverview from '@/modules/Dashboard/FinancialOverview.vue'
import { EventBus } from '@/shared/eventbus'
import Loader from '@/shared/Loader.vue'

export default {
    name: 'Dashboard',
    mixins: [notify],
    components: {
        Loader,
        RevenueTrends,
        FinancialOverview,
        BoxGroup,
        Map
    },
    data () {
        return {
            clusterData: {},
            mappingService: new MappingService(),
            miniGridIcon: miniGridIcon,
            clusterId: null,
            geoData: null,
            constantLocations: [],
            markingInfos: [],
            loading: false,
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
            revenue: [],
            mapData: [],

        }
    },
    created () {
        this.clusterId = this.$route.params.id
    },
    mounted () {
        this.$store.dispatch('clusterDashboard/get', this.$route.params.id)
        this.clusterData = this.$store.getters['clusterDashboard/getClusterData']
        this.revenue = this.clusterData.citiesRevenue
        this.mapData = this.clusterData.geo_data
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
            this.loading = true
            try {
                EventBus.$emit('clustersCachedDataLoading', this.loading)
                await this.$store.dispatch('clusterDashboard/update')
                this.$store.dispatch('clusterDashboard/get', this.$route.params.id)
                this.clusterData = this.$store.getters['clusterDashboard/getClusterData']
                this.revenue = this.clusterData.citiesRevenue
                this.alertNotify('success', 'Dashboard refreshed successfully.')
            } catch (e) {
                this.alertNotify('error', e.message)
            }
            this.loading = false
            EventBus.$emit('clustersCachedDataLoading', this.loading)
            this.$nextTick(() => {
                this.mapData = this.clusterData.geo_data
                this.setMiniGridsOfClusterMapSettings()
            })
        },
        financialOverviewPeriodChanged (fromDate, toDate) {
            const cachedData = this.$store.getters['clusterDashboard/getClusterData']
            this.revenue = cachedData.citiesRevenue.map((cityRevenue) => {
                const newPeriod = Object.entries(cityRevenue.period).reduce((acc, [period, revenue]) => {
                    const date = moment(period, 'YYYY-MM')
                    const lastDayOfMonth = date.endOf('month')
                    const formattedPeriod = lastDayOfMonth.format('YYYY-MM-DD')
                    if (moment(formattedPeriod).isSameOrAfter(fromDate) && moment(period).isSameOrBefore(toDate)) {
                        acc = { ...acc, [period]: revenue }
                    }
                    return acc
                }, {})
                return {
                    ...cityRevenue,
                    period: newPeriod
                }
            })
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
        }
    }
}
</script>

<style>


</style>

