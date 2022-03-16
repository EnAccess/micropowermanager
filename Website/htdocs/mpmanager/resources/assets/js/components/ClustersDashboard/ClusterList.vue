<template>
    <div>
        <md-toolbar class="md-dense">
            <h3 class="md-title" style="flex: 1">Clusters Dashboard</h3>
            <md-button class="md-raised" @click="updateCacheData">
                <md-icon>update</md-icon>
                Refresh Data
                <md-progress-bar v-if="updateProgress" md-mode="indeterminate"></md-progress-bar>
            </md-button>
        </md-toolbar>
        <div v-if="clustersCacheData">
            <div class="row" style="margin-top: 30px">
                <!-- Holds registered clusters, registered meters etc. -->
                <box-group :clusters="clustersCacheData.clustersList"/>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <financial-overview :clustersRevenue="clustersCacheData.clustersRevenue"/>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12" v-if="clustersCacheData.clustersList.length">
                    <cluster-map/>
                </div>
                <div class="col-sm-12" v-else>
                    <cluster-map/>
                </div>
            </div>
        </div>
        <div v-else>
            <div style="margin-left: 40vw; margin-top: 20vh;">
                <img width="48px" src="../../../spinner/spinner.gif" alt="">
            </div>
        </div>

    </div>
</template>

<script>
import '../../shared/TableList'
import BoxGroup from './BoxGroup'
import FinancialOverview from './FinancialOverview'
import ClusterMap from './ClusterMap'
import { mapGetters } from 'vuex'

export default {
    name: 'ClusterList',
    components: { ClusterMap, FinancialOverview, BoxGroup },
    data () {
        return {
            updateProgress: false,
        }
    },
    mounted () {
        this.getClusterList()
    },
    methods: {
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
        async getClusterList () {
            await this.$store.dispatch('clusterDashboard/list')
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
            clustersCacheData: 'clusterDashboard/getClustersData'
        })
    }
}
</script>

