<template>
  <div>
    <md-toolbar class="md-dense">
      <h3 class="md-title" style="flex: 1">
        {{ $tc("phrases.clustersDashboard") }}
      </h3>
      <md-button class="md-raised" @click="updateCacheData">
        <md-icon>update</md-icon>
        {{ $tc("phrases.refreshData") }}
        <md-progress-bar
          v-if="loading"
          md-mode="indeterminate"
        ></md-progress-bar>
      </md-button>
    </md-toolbar>
    <div>
      <div class="md-layout md-gutter" style="margin-top: 3rem">
        <div class="md-layout-item md-size-100">
          <box-group :clusters="clustersData" />
        </div>
        <div class="md-layout-item md-size-100">
          <financial-overview
            :revenue="clustersData"
            :periodChanged="financialOverviewPeriodChanged"
          />
        </div>
        <div class="md-layout-item md-size-100">
          <widget :title="$tc('phrases.clusterMap')" id="cluster-map">
            <dashboard-map
              :mapping-service="mappingService"
              ref="dashboardMapRef"
            />
          </widget>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import "@/shared/TableList"
import BoxGroup from "@/modules/Dashboard/BoxGroup"
import FinancialOverview from "@/modules/Dashboard/FinancialOverview"
import DashboardMap from "@/modules/Map/DashboardMap.vue"
import { notify } from "@/mixins/notify"
import Widget from "@/shared/Widget.vue"
import moment from "moment"
import { MappingService, MARKER_TYPE } from "@/services/MappingService"

export default {
  name: "Dashboard",
  components: { DashboardMap, FinancialOverview, BoxGroup, Widget },
  mixins: [notify],
  data() {
    return {
      loading: false,
      clustersData: [],
      mappingService: new MappingService(),
    }
  },
  created() {
    this.getClusterList()
  },
  methods: {
    async getClusterList() {
      this.loading = true
      await this.$store.dispatch("clusterDashboard/list")
      this.clustersData =
        this.$store.getters["clusterDashboard/getClustersData"]
      this.loading = false
      this.setClustersMapData()
    },
    async updateCacheData() {
      this.loading = true
      try {
        await this.$store.dispatch("clusterDashboard/update")
        this.clustersData =
          this.$store.getters["clusterDashboard/getClustersData"]
        this.alertNotify("success", "Dashboard data refreshed successfully.")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
    },
    setClustersMapData() {
      const markingInfos = []
      const clustersGeoData = []
      this.clustersData.map((data) => {
        if (data.geo_data !== null) {
          const clusterGeo = data.geo_data
          this.mappingService.setCenter([clusterGeo.lat, clusterGeo.lon])
          clusterGeo.clusterId = data.id
          clustersGeoData.push(clusterGeo)
          const miniGridsOfCluster = data.clusterData.mini_grids
          miniGridsOfCluster.map((miniGrid) => {
            const points = miniGrid.location.points.split(",")
            if (points.length !== 2) {
              this.alertNotify("error", "Mini-Grid has no location")
              return
            }
            const lat = parseFloat(points[0])
            const lon = parseFloat(points[1])
            markingInfos.push({
              id: miniGrid.id,
              name: miniGrid.name,
              serialNumber: null,
              lat: lat,
              lon: lon,
              deviceType: null,
              markerType: MARKER_TYPE.MINI_GRID,
              clusterId: clusterGeo.clusterId,
              clusterDisplayName: clusterGeo.display_name,
            })
          })
        }
      })
      this.mappingService.setGeoData(clustersGeoData)
      this.mappingService.setMarkingInfos(markingInfos)
      this.$refs.dashboardMapRef.drawClusters()
      this.$refs.dashboardMapRef.setMiniGridMarkers()
    },
    financialOverviewPeriodChanged(fromDate, toDate) {
      const cachedData = this.$store.getters["clusterDashboard/getClustersData"]
      this.clustersData = cachedData.map((cluster) => {
        const newPeriod = Object.entries(cluster.period).reduce(
          (acc, [period, revenue]) => {
            if (
              moment(period).isSameOrAfter(fromDate) &&
              moment(period).isSameOrBefore(toDate)
            ) {
              acc = { ...acc, [period]: revenue }
            }
            return acc
          },
          {},
        )
        return {
          ...cluster,
          period: newPeriod,
        }
      })
    },
  },
}
</script>
