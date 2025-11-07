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
        if (data.geo_json !== null) {
          const clusterGeo = data.geo_json
          // Calculate center point from coordinates
          let lat = 0
          let lon = 0
          if (clusterGeo.coordinates && clusterGeo.coordinates[0]) {
            const coords = clusterGeo.coordinates[0]
            const count = coords.length
            coords.forEach((coord) => {
              lat += coord[1]
              lon += coord[0]
            })
            lat = lat / count
            lon = lon / count
          }
          this.mappingService.setCenter([lat, lon])
          const geoDataWithMetadata = {
            ...clusterGeo,
            clusterId: data.id,
            clusterName: data.name,
            lat: lat,
            lon: lon,
          }
          clustersGeoData.push(geoDataWithMetadata)
          const miniGridsOfCluster = data.clusterData.mini_grids
          miniGridsOfCluster.map((miniGrid) => {
            const points = miniGrid.location.points.split(",")
            if (points.length !== 2) {
              this.alertNotify("error", "Mini-Grid has no location")
              return
            }
            const miniGridLat = parseFloat(points[0])
            const miniGridLon = parseFloat(points[1])
            markingInfos.push({
              id: miniGrid.id,
              name: miniGrid.name,
              serialNumber: null,
              lat: miniGridLat,
              lon: miniGridLon,
              deviceType: null,
              markerType: MARKER_TYPE.MINI_GRID,
              clusterId: data.id,
              clusterName: data.name,
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
