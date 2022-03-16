<template>
    <widget
        :title="$tc('phrases.clusterMap')"
        id="cluster-map">
        <Map
            :geoData="geoData"
            :center="center"
            :parentName="'Top'">
        </Map>
    </widget>
</template>

<script>
import Widget from '../../shared/widget'
import Map from '../../shared/Map'
import { mapGetters } from 'vuex'

export default {
    name: 'ClusterMap',
    components: {
        Map,
        Widget,
    },
    data () {
        return {
            center: [
                this.$store.getters['settings/getMapSettings'].latitude,
                this.$store.getters['settings/getMapSettings'].longitude],
            clusterGeo: {},
        }
    },
    computed: {
        ...mapGetters({
            clustersCacheData: 'clusterDashboard/getClustersData'
        }),
        geoData () {
            let geoData = []
            this.clustersCacheData.clustersList.forEach((e) => {
                if (e.geo_data !== null) {
                    this.clusterGeo = e.geo_data
                    this.clusterGeo.clusterId = e.id
                    geoData.push(this.clusterGeo)
                }
            })
            return geoData
        }
    }
}
</script>
