<template>
    <div>
        <md-toolbar class="md-dense">
            <h3 class="md-title" style="flex: 1">{{ $tc('phrases.clustersDashboard') }}</h3>
            <md-button class="md-raised" @click="updateCacheData">
                <md-icon>update</md-icon>
                {{ $tc('phrases.refreshData') }}
                <md-progress-bar v-if="loading" md-mode="indeterminate"></md-progress-bar>
            </md-button>
        </md-toolbar>
        <div>
            <div class="md-layout md-gutter" style="margin-top: 3rem">
                <div class="md-layout-item md-size-100">
                    <box-group :clusters="clustersData"/>
                </div>
                <div class="md-layout-item md-size-100">
                    <financial-overview :revenue="clustersData"
                                        :periodChanged="financialOverviewPeriodChanged"
                    />
                </div>
                <div class="md-layout-item md-size-100">
                    <cluster-map :clustersData="clustersData"/>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
import '../../shared/TableList'
import BoxGroup from './BoxGroup'
import FinancialOverview from './FinancialOverview'
import ClusterMap from './ClusterMap'
import Loader from '@/shared/Loader.vue'
import { notify } from '@/mixins/notify'
import { EventBus } from '@/shared/eventbus'

export default {
    name: 'ClusterList',
    components: { Loader, ClusterMap, FinancialOverview, BoxGroup },
    mixins: [notify],
    data () {
        return {
            loading: false,
            clustersData: [],
        }
    },
    created () {
        this.getClusterList()
    },
    methods: {
        async getClusterList () {
            this.loading = true
            EventBus.$emit('clustersCachedDataLoading', this.loading)
            await this.$store.dispatch('clusterDashboard/list')
            this.clustersData = this.$store.getters['clusterDashboard/getClustersData']
            this.loading = false
            EventBus.$emit('clustersCachedDataLoading', this.loading)
        },
        async updateCacheData () {
            this.loading = true
            try {
                EventBus.$emit('clustersCachedDataLoading', this.loading)
                await this.$store.dispatch('clusterDashboard/update')
                this.clustersData = this.$store.getters['clusterDashboard/getClustersData']

                this.alertNotify('success', 'Dashboard data refreshed successfully.')
            } catch (e) {
                this.alertNotify('error', e.message)
            }
            this.loading = false
            EventBus.$emit('clustersCachedDataLoading', this.loading)
        },
        financialOverviewPeriodChanged (fromDate, toDate) {
            const cachedData = this.$store.getters['clusterDashboard/getClustersData']
            this.clustersData = cachedData.map((cluster) => {
                const newPeriod = Object.entries(cluster.period).reduce((acc, [period, revenue]) => {

                    if (moment(period).isSameOrAfter(fromDate) && moment(period).isSameOrBefore(toDate)) {
                        acc = { ...acc, [period]: revenue }
                    }
                    return acc
                }, {})
                return {
                    ...cluster,
                    period: newPeriod
                }
            })
        }
    }
}
</script>

