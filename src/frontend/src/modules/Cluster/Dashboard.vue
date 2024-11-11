<template>
  <div>
    <md-toolbar style="margin-bottom: 3rem" class="md-dense">
      <div class="md-toolbar-row">
        <div class="md-toolbar-section-start">
          {{ $tc("words.cluster") }} :
          <span style="font-size: 1.3rem; font-weight: bold" v-if="clusterData">
            {{ clusterData.name }}
          </span>
        </div>
        <div class="md-toolbar-section-end">
          <md-button class="md-raised" @click="updateCacheData">
            <md-icon>update</md-icon>
            {{ $tc("phrases.refreshData") }}
            <md-progress-bar
              v-if="loading"
              md-mode="indeterminate"
            ></md-progress-bar>
          </md-button>
        </div>
      </div>
    </md-toolbar>
    <div class="md-layout md-gutter">
      <div class="md-layout-item md-size-100">
        <box-group :cluster="clusterData" />
      </div>
      <div class="md-layout-item md-size-100">
        <financial-overview
          :revenue="revenue"
          :periodChanged="financialOverviewPeriodChanged"
        />
      </div>
      <div class="md-layout-item md-size-100" style="margin-top: 2vh">
        <md-card>
          <md-card-content>
            <cluster-map
              :mapping-service="mappingService"
              ref="clusterMapRef"
            />
          </md-card-content>
        </md-card>
      </div>
      <div class="md-layout-item md-size-100">
        <revenue-trends
          :clusterId="clusterId"
          :clusterRevenueAnalysis="clusterData.revenueAnalysis"
        />
      </div>
    </div>
  </div>
</template>

<script>
import "@/shared/TableList"

import BoxGroup from "./BoxGroup"
import RevenueTrends from "./RevenueTrends"
import { MappingService, MARKER_TYPE } from "@/services/MappingService"
import { notify } from "@/mixins"
import FinancialOverview from "@/modules/Dashboard/FinancialOverview"
import { EventBus } from "@/shared/eventbus"
import moment from "moment"
import ClusterMap from "@/modules/Map/ClusterMap.vue"

export default {
  name: "Dashboard",
  mixins: [notify],
  components: {
    RevenueTrends,
    FinancialOverview,
    BoxGroup,
    ClusterMap,
  },
  data() {
    return {
      clusterData: {},
      mappingService: new MappingService(),
      clusterId: null,
      loading: false,
      boxData: {
        revenue: {
          period: "-",
          total: "-",
        },
        people: "-",
        meters: "-",
      },
      revenue: [],
    }
  },
  created() {
    this.clusterId = this.$route.params.id
  },
  mounted() {
    this.$store.dispatch("clusterDashboard/get", this.$route.params.id)
    this.clusterData = this.$store.getters["clusterDashboard/getClusterData"]
    this.boxData["mini_grids"] = this.clusterData.clusterData.mini_grids.length
    this.revenue = this.clusterData.citiesRevenue
    this.setClusterMapData()
  },
  methods: {
    setClusterMapData() {
      const markingInfos = []
      const clusterGeoData = this.clusterData.geo_data
      this.mappingService.setCenter([clusterGeoData.lat, clusterGeoData.lon])
      this.mappingService.setGeoData(clusterGeoData)
      const miniGridsOfCluster = this.clusterData.clusterData.mini_grids
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
        })
      })
      this.mappingService.setMarkingInfos(markingInfos)
      this.$refs.clusterMapRef.drawCluster()
      this.$refs.clusterMapRef.setMiniGridMarkers()
    },
    async updateCacheData() {
      this.loading = true
      try {
        EventBus.$emit("clustersCachedDataLoading", this.loading)
        await this.$store.dispatch("clusterDashboard/update")
        this.$store.dispatch("clusterDashboard/get", this.$route.params.id)
        this.clusterData =
          this.$store.getters["clusterDashboard/getClusterData"]
        this.revenue = this.clusterData.citiesRevenue
        this.alertNotify("success", "Dashboard refreshed successfully.")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
      this.loading = false
      EventBus.$emit("clustersCachedDataLoading", this.loading)
    },
    financialOverviewPeriodChanged(fromDate, toDate) {
      const cachedData = this.$store.getters["clusterDashboard/getClusterData"]
      this.revenue = cachedData.citiesRevenue.map((cityRevenue) => {
        const newPeriod = Object.entries(cityRevenue.period).reduce(
          (acc, [period, revenue]) => {
            const date = moment(period, "YYYY-MM")
            const lastDayOfMonth = date.endOf("month")
            const formattedPeriod = lastDayOfMonth.format("YYYY-MM-DD")
            if (
              moment(formattedPeriod).isSameOrAfter(fromDate) &&
              moment(period).isSameOrBefore(toDate)
            ) {
              acc = { ...acc, [period]: revenue }
            }
            return acc
          },
          {},
        )
        return {
          ...cityRevenue,
          period: newPeriod,
        }
      })
    },
    addRevenue(data) {
      this.boxData["revenue"] = {
        total: data["sum"],
        period: data["period"],
      }
    },
    addConnections(data) {
      this.boxData["people"] = data
      this.boxData["meters"] = data
    },
  },
}
</script>

<style></style>
