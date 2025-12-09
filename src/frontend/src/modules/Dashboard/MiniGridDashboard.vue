<template>
  <div>
    <md-toolbar class="md-dense">
      <h3 class="md-title" style="flex: 1">
        {{ $tc("phrases.miniGridDashboard") }}
      </h3>
      <md-button class="md-raised" @click="refreshData" :disabled="loading">
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
        <!-- Basic Mini-Grid Metrics -->
        <div class="md-layout-item md-size-100">
          <div class="md-layout md-gutter">
            <div
              class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
            >
              <box
                :box-color="'blue'"
                :center-text="true"
                :header-text="$tc('phrases.totalMiniGrids')"
                :sub-text="totalMiniGrids.toString()"
                :box-icon="'bolt'"
              />
            </div>
            <div
              class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
            >
              <box
                :box-color="'green'"
                :center-text="true"
                :header-text="$tc('phrases.totalClusters')"
                :sub-text="totalClusters.toString()"
                :box-icon="'account_tree'"
              />
            </div>
            <div
              class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
            >
              <box
                :box-color="'orange'"
                :center-text="true"
                :header-text="$tc('phrases.totalCities')"
                :sub-text="totalCities.toString()"
                :box-icon="'location_city'"
              />
            </div>
            <div
              class="md-layout-item md-size-25 md-small-size-50 md-xsmall-size-100"
            >
              <box
                :box-color="'red'"
                :center-text="true"
                :header-text="$tc('phrases.totalAgents')"
                :sub-text="totalAgents.toString()"
                :box-icon="'supervisor_account'"
              />
            </div>
          </div>
        </div>

        <!-- Mini-Grid List -->
        <div class="md-layout-item md-size-100">
          <widget :title="$tc('phrases.miniGridList')" id="mini-grid-list">
            <div class="search-section">
              <md-field>
                <label>{{ $tc("words.search") }}</label>
                <md-input v-model="searchTerm" @input="filterMiniGrids" />
                <md-icon>search</md-icon>
              </md-field>
            </div>

            <md-table md-card style="margin-left: 0">
              <md-table-row>
                <md-table-head>{{ $tc("words.name") }}</md-table-head>
                <md-table-head>{{ $tc("words.cluster") }}</md-table-head>
                <md-table-head>{{ $tc("words.createdAt") }}</md-table-head>
                <md-table-head>{{ $tc("words.cities") }}</md-table-head>
                <md-table-head>{{ $tc("words.agents") }}</md-table-head>
              </md-table-row>
              <md-table-row
                v-for="miniGrid in filteredMiniGrids"
                :key="miniGrid.id"
                style="cursor: pointer"
                @click="viewMiniGridDetail(miniGrid.id)"
              >
                <md-table-cell>
                  {{ miniGrid.name }}
                </md-table-cell>
                <md-table-cell>
                  {{ miniGrid.cluster?.name || "-" }}
                </md-table-cell>
                <md-table-cell>
                  {{ formatDate(miniGrid.created_at) }}
                </md-table-cell>
                <md-table-cell>
                  {{ miniGrid.cities_count || 0 }}
                </md-table-cell>
                <md-table-cell>
                  {{ miniGrid.agents_count || 0 }}
                </md-table-cell>
              </md-table-row>
            </md-table>
          </widget>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Box from "@/shared/Box.vue"
import Widget from "@/shared/Widget.vue"
import { MiniGridService } from "@/services/MiniGridService"
import { notify } from "@/mixins/notify"
import moment from "moment"

export default {
  name: "MiniGridDashboard",
  components: { Box, Widget },
  mixins: [notify],
  data() {
    return {
      loading: false,
      searchTerm: "",
      miniGridService: new MiniGridService(),
      miniGrids: [],
    }
  },
  computed: {
    totalMiniGrids() {
      return this.miniGrids.length
    },
    totalClusters() {
      const uniqueClusters = new Set(
        this.miniGrids
          .map((mg) => mg.cluster_id)
          .filter((id) => id !== null && id !== undefined),
      )
      return uniqueClusters.size
    },
    totalCities() {
      // If cities_count is available, use it; otherwise count cities array if available
      return this.miniGrids.reduce((sum, mg) => {
        if (mg.cities_count !== undefined) {
          return sum + mg.cities_count
        }
        if (mg.cities && Array.isArray(mg.cities)) {
          return sum + mg.cities.length
        }
        return sum
      }, 0)
    },
    totalAgents() {
      // If agents_count is available, use it; otherwise count agents array if available
      return this.miniGrids.reduce((sum, mg) => {
        if (mg.agents_count !== undefined) {
          return sum + mg.agents_count
        }
        if (mg.agents && Array.isArray(mg.agents)) {
          return sum + mg.agents.length
        }
        return sum
      }, 0)
    },
    filteredMiniGrids() {
      if (!this.searchTerm) return this.miniGrids

      const searchLower = this.searchTerm.toLowerCase()
      return this.miniGrids.filter((miniGrid) => {
        const name = miniGrid.name || ""
        const clusterName = miniGrid.cluster?.name || ""
        return (
          name.toLowerCase().includes(searchLower) ||
          clusterName.toLowerCase().includes(searchLower)
        )
      })
    },
  },
  mounted() {
    this.loadMiniGridData()
  },
  methods: {
    async loadMiniGridData() {
      this.loading = true
      try {
        const miniGrids = await this.miniGridService.getMiniGrids()
        if (miniGrids instanceof Error) {
          this.alertNotify("error", "Failed to load mini-grid data")
          this.miniGrids = []
        } else {
          this.miniGrids = miniGrids || []
        }
      } catch (error) {
        this.alertNotify("error", "Failed to load mini-grid data")
        this.miniGrids = []
      } finally {
        this.loading = false
      }
    },
    async refreshData() {
      await this.loadMiniGridData()
      this.alertNotify("success", "Mini-grid data refreshed successfully")
    },
    filterMiniGrids() {
      // Filtering is handled by computed property
    },
    viewMiniGridDetail(miniGridId) {
      this.$router.push({ path: `/mini-grids/${miniGridId}` })
    },
    formatDate(date) {
      if (!date) return "-"
      return moment(date).format("YYYY-MM-DD")
    },
  },
}
</script>

<style lang="scss" scoped>
.search-section {
  margin-bottom: 1rem;
  padding: 0 1rem;
}

.md-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.md-toolbar .md-title {
  flex: 1;
}

.md-toolbar .md-button {
  margin-left: auto;
}
</style>

