<template>
  <div>
    <md-toolbar style="margin-bottom: 3rem" class="md-dense">
      <div class="md-toolbar-row">
        <div class="md-toolbar-section-start">
          <md-menu
            md-direction="bottom-end"
            md-size="big"
            :md-offset-x="127"
            :md-offset-y="-36"
          >
            <md-button md-menu-trigger>
              <md-icon>keyboard_arrow_down</md-icon>
              {{ $tc("words.cluster") }}:
              {{ clusterData.name }}
            </md-button>
            <md-menu-content>
              <md-menu-item @click="goToAllClusters">
                <span>{{ $tc("phrases.allClusters") }}</span>
              </md-menu-item>
              <md-divider></md-divider>
              <md-menu-item
                v-for="(cluster, key) in clusterList"
                :key="key"
                :disabled="cluster.id === parseInt(clusterId)"
                @click="setCluster(cluster.id)"
              >
                <span>{{ cluster.name }}</span>
              </md-menu-item>
            </md-menu-content>
          </md-menu>
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
          <md-button class="md-raised" @click="openEditDialog">
            <md-icon>edit</md-icon>
            {{ $tc("words.rename") }}
          </md-button>
          <md-button class="md-raised md-accent" @click="confirmDelete">
            <md-icon>delete</md-icon>
            {{ $tc("words.delete") }}
          </md-button>
        </div>
      </div>
    </md-toolbar>
    <md-dialog
      :md-active.sync="editDialogActive"
      :md-close-on-esc="true"
      :md-click-outside-to-close="true"
    >
      <md-dialog-title>{{ $tc("phrases.renameCluster") }}</md-dialog-title>
      <md-dialog-content>
        <md-field>
          <label>{{ $tc("words.name") }}</label>
          <md-input v-model="editName" />
        </md-field>
      </md-dialog-content>
      <md-dialog-actions>
        <md-button @click="editDialogActive = false">
          {{ $tc("words.cancel") }}
        </md-button>
        <md-button class="md-primary" @click="saveEdit">
          {{ $tc("words.save") }}
        </md-button>
      </md-dialog-actions>
    </md-dialog>
    <div class="md-layout md-gutter">
      <div class="md-layout-item md-size-100">
        <box-group :cluster="clusterData" />
      </div>
      <div class="md-layout-item md-size-100">
        <financial-overview
          :clusterId="clusterId"
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
import moment from "moment"

import BoxGroup from "./BoxGroup.vue"
import RevenueTrends from "./RevenueTrends.vue"

import { notify } from "@/mixins/notify.js"
import FinancialOverview from "@/modules/Dashboard/FinancialOverview.vue"
import ClusterMap from "@/modules/Map/ClusterMap.vue"
import { ClusterService } from "@/services/ClusterService.js"
import { MappingService, MARKER_TYPE } from "@/services/MappingService.js"
import { EventBus } from "@/shared/eventbus.js"
import "@/shared/TableList.vue"

export default {
  name: "ClusterDetail",
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
      clusterService: new ClusterService(),
      clusterId: null,
      loading: false,
      editDialogActive: false,
      editName: "",
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
  async mounted() {
    await this.$store.dispatch("clusterDashboard/list")
    this.loadClusterData(this.$route.params.id)
  },
  watch: {
    "$route.params.id"(newId) {
      if (newId) {
        this.loadClusterData(newId)
      }
    },
  },
  methods: {
    loadClusterData(id) {
      this.clusterId = id
      this.$store.dispatch("clusterDashboard/get", id)
      this.clusterData = this.$store.getters["clusterDashboard/getClusterData"]
      const clusterModel = this.clusterData.clusterData
      this.boxData["mini_grids"] =
        clusterModel && clusterModel.mini_grids
          ? clusterModel.mini_grids.length
          : 0
      this.revenue = this.clusterData.citiesRevenue
      this.setClusterMapData()
    },
    setCluster(clusterId) {
      this.$router.replace("/clusters/" + clusterId)
    },
    goToAllClusters() {
      this.$router.push("/clusters")
    },
    setClusterMapData() {
      const markingInfos = []
      const cluster = this.clusterData.clusterData || this.clusterData

      if (cluster.geo_json !== null && cluster.geo_json !== undefined) {
        let geoJsonFeature
        if (cluster.geo_json.type === "Feature") {
          geoJsonFeature = cluster.geo_json
        } else if (cluster.geo_json.type === "FeatureCollection") {
          geoJsonFeature = cluster.geo_json.features[0]
        } else {
          throw new Error(
            "cluster.geo_json must be a GeoJSON Feature or FeatureCollection",
          )
        }

        geoJsonFeature = {
          ...geoJsonFeature,
          properties: {
            ...geoJsonFeature.properties,
            name: cluster.name || "",
          },
        }

        this.mappingService.setGeoData(geoJsonFeature)
      }

      const miniGridsOfCluster = this.clusterData.clusterData?.mini_grids || []
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
    openEditDialog() {
      this.editName = this.clusterData.name || ""
      this.editDialogActive = true
    },
    async saveEdit() {
      if (!this.editName || !this.editName.trim()) {
        this.alertNotify("error", this.$tc("phrases.nameRequired"))
        return
      }
      try {
        await this.clusterService.updateCluster(this.clusterId, {
          name: this.editName.trim(),
        })
        this.editDialogActive = false
        this.alertNotify("success", this.$tc("phrases.clusterUpdated"))
        await this.$store.dispatch("clusterDashboard/list")
        this.loadClusterData(this.clusterId)
      } catch (e) {
        this.alertNotify("error", e.message || this.$tc("phrases.updateFailed"))
      }
    },
    confirmDelete() {
      this.$swal({
        type: "question",
        title: this.$tc("phrases.deleteCluster"),
        text: this.$tc("phrases.deleteClusterNotify", 0, {
          name: this.clusterData.name,
        }),
        width: "35%",
        confirmButtonText: this.$tc("words.confirm"),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        focusCancel: true,
      }).then((result) => {
        if (result.value) {
          this.deleteCluster()
        }
      })
    },
    async deleteCluster() {
      try {
        await this.clusterService.deleteCluster(this.clusterId)
        this.alertNotify("success", this.$tc("phrases.clusterDeleted"))
        await this.$store.dispatch("clusterDashboard/list")
        this.$router.push("/clusters")
      } catch (e) {
        this.alertNotify("error", e.message || this.$tc("phrases.deleteFailed"))
      }
    },
  },
  computed: {
    clusterList() {
      return this.$store.getters["clusterDashboard/getClustersData"].map(
        (cluster) => {
          return {
            id: cluster.id,
            name: cluster.clusterData?.name || cluster.name,
          }
        },
      )
    },
  },
}
</script>

<style scoped lang="scss"></style>
