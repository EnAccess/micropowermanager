<template>
  <div>
    <section id="widget-grid">
      <div class="md-layout md-gutter">
        <div
          class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100"
        >
          <md-toolbar style="margin-bottom: 3rem">
            <md-menu
              md-direction="bottom-end"
              md-size="big"
              :md-offset-x="127"
              :md-offset-y="-36"
            >
              <md-button md-menu-trigger>
                <md-icon>keyboard_arrow_down</md-icon>
                {{ $tc("words.miniGrid") }}:
                {{ miniGridData.name }}
              </md-button>
              <md-menu-content>
                <md-menu-item
                  v-for="(miniGrid, key) in miniGridList"
                  :key="key"
                  @click="setMiniGrid(miniGrid.id)"
                >
                  <span>{{ miniGrid.name }}</span>
                </md-menu-item>
              </md-menu-content>
            </md-menu>

            <div class="md-toolbar-section-end">
              <span style="float: left">Selected Period: {{ periodText }}</span>
              <md-button
                class="md-icon-button md-dense md-raised"
                @click="togglePeriod"
              >
                <md-icon>calendar_today</md-icon>
              </md-button>
              <md-button class="md-raised" @click="openEditModal">
                <md-icon>edit</md-icon>
                {{ $tc("words.edit") }}
              </md-button>
              <md-button class="md-raised md-accent" @click="confirmDelete">
                <md-icon>delete</md-icon>
                {{ $tc("words.delete") }}
              </md-button>
            </div>
          </md-toolbar>
          <div v-if="setPeriod" class="period-selector">
            <p>{{ $tc("phrases.selectPeriod") }}</p>
            <div class="md-layout md-gutter">
              <div class="md-layout-item md-size-100 page-a-datepicker-fix">
                <md-datepicker
                  :class="{
                    'md-invalid': errors.has($tc('phrases.fromDate')),
                  }"
                  :name="$tc('phrases.fromDate')"
                  md-immediately
                  v-model="period.from"
                  v-validate="'required'"
                  :md-close-on-blur="false"
                >
                  <label>
                    {{ $tc("phrases.fromDate") }}
                  </label>
                  <span class="md-error">
                    {{ errors.first($tc("phrases.fromDate")) }}
                  </span>
                </md-datepicker>
              </div>
              <div class="md-layout-item md-size-100 page-a-datepicker-fix">
                <md-datepicker
                  :class="{
                    'md-invalid': errors.has($tc('phrases.toDate')),
                  }"
                  :name="$tc('phrases.toDate')"
                  md-immediately
                  v-model="period.to"
                  v-validate="'required'"
                  :md-close-on-blur="false"
                >
                  <label>
                    {{ $tc("phrases.toDate") }}
                  </label>
                  <span class="md-error">
                    {{ errors.first($tc("phrases.toDate")) }}
                  </span>
                </md-datepicker>
              </div>
            </div>
            <div style="margin-top: 5px">
              <md-progress-bar md-mode="indeterminate" v-if="loading" />
              <button
                style="width: 100%"
                v-if="!loading"
                class="btn btn-primary"
                @click="onPeriodChange"
              >
                {{ $tc("words.send") }}
              </button>
            </div>
          </div>
        </div>
        <div class="md-layout-item md-size-100">
          <box-group
            ref="box"
            :mini-grid-id="miniGridId"
            :miniGridData="miniGridData"
          ></box-group>
        </div>

        <div class="md-layout-item md-layout md-gutter md-size-100">
          <div
            class="md-layout-item md-medium-size-100 md-size-33"
            style="min-height: 500px"
          >
            <revenue-per-customer-type
              :donutData="donutData"
              :donutChartOptions="donutChartOptions"
            />
          </div>
          <div
            class="md-layout-item md-medium-size-100 md-size-66"
            style="min-height: 500px"
          >
            <revenue-target-per-customer-type
              :targetRevenueChartData="targetRevenueChartData"
            />
          </div>
        </div>
        <div class="md-layout-item md-size-100 map-area">
          <Widget :title="$tc('phrases.miniGridMap')" id="miniGrid-map">
            <mini-grid-map
              ref="miniGridMapRef"
              :mapping-service="mappingService"
              :edit="true"
              :miniGridId="miniGridId"
              @locationEdited="deviceLocationsEditedSet"
            />
          </Widget>
        </div>
        <!--
                <div class="md-layout-item md-size-100">
                        TODO: Refactor this component later!
                        <target-list
                        ref="target"
                        :target-id="miniGridId"
                        target-type="mini-grid"
                        :base="highlighted.base"
                        :compared="highlighted.compared"
                    />
                </div>
                -->
        <div
          class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100"
        >
          <revenue-trends
            :trendChartData="trendChartData"
            :chartOptions="chartOptions"
          />
        </div>

        <div
          class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100"
        >
          <tickets-overview
            :chart-options="chartOptions"
            :ticketData="openedTicketChartData"
            ref="ticketsOverview"
          />
        </div>
      </div>

      <md-dialog
        :md-active.sync="showModal"
        :md-close-on-esc="true"
        :md-click-outside-to-close="true"
      >
        <md-dialog-title>{{ $tc("phrases.editMiniGrid") }}</md-dialog-title>
        <md-dialog-content>
          <md-field>
            <label>{{ $tc("words.name") }}</label>
            <md-input v-model="editName" />
          </md-field>
        </md-dialog-content>
        <md-dialog-actions>
          <md-button @click="showModal = false">
            {{ $tc("words.cancel") }}
          </md-button>
          <md-button class="md-primary" @click="updateMiniGrid">
            {{ $tc("words.save") }}
          </md-button>
        </md-dialog-actions>
      </md-dialog>
    </section>
  </div>
</template>

<script>
import moment from "moment"

import i18n from "@/i18n.js"
import { currency } from "@/mixins/currency.js"
import { notify } from "@/mixins/notify.js"
import MiniGridMap from "@/modules/Map/MiniGridMap.vue"
import BoxGroup from "@/modules/MiniGrid/BoxGroup.vue"
import RevenuePerCustomerType from "@/modules/MiniGrid/RevenuePerCustomerType.vue"
import RevenueTargetPerCustomerType from "@/modules/MiniGrid/RevenueTargetPerCustomerType.vue"
import RevenueTrends from "@/modules/MiniGrid/RevenueTrends.vue"
import TicketsOverview from "@/modules/MiniGrid/TicketsOverview.vue"
import { BatchRevenueService } from "@/services/BatchRevenueService.js"
import { DeviceAddressService } from "@/services/DeviceAddressService.js"
import { ICONS, MappingService } from "@/services/MappingService.js"
import { MiniGridService } from "@/services/MiniGridService.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Dashboard",
  components: {
    RevenueTargetPerCustomerType,
    MiniGridMap,
    RevenueTrends,
    BoxGroup,
    TicketsOverview,
    RevenuePerCustomerType,
    Widget,
  },
  mixins: [currency, notify],
  data() {
    return {
      miniGridService: new MiniGridService(),
      mappingService: new MappingService(),
      batchRevenueService: new BatchRevenueService(),
      deviceAddressService: new DeviceAddressService(),
      showModal: false,
      editName: "",
      miniGridData: {},
      miniGridId: null,
      chartOptions: {
        isStacked: true,
        chart: {
          legend: {
            position: "top",
          },
        },
        hAxis: {
          textPosition: "out",
          slantedText: true,
        },
        vAxis: {
          //scaleType: 'mirrorLog',
        },
        height: "600",
      },
      loading: true,
      setPeriod: false,
      openedTicketChartData: [],
      trendChartData: {
        base: [],
        compare: [],
        overview: [],
      },
      donutData: [],
      donutChartOptions: {
        pieHole: 1,
        legend: "bottom",
        height: 500,
      },
      targetRevenueChartData: [],
      period: {
        from: null,
        to: null,
      },
      periodText: "-",
    }
  },
  created() {
    this.miniGridId = this.$route.params.id
    this.redirectionUrl += "/" + this.miniGridId
    this.mappingService.setMarkerUrl(ICONS.MINI_GRID)
  },
  mounted() {
    this.getMiniGridData()
    EventBus.$on("getEditedGeoDataItems", (editedItems) => {
      this.$swal({
        title: this.$tc("phrases.relocateMeter", 1),
        text: this.$tc("phrases.relocateMeter", 2),
        type: "question",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: this.$tc("words.relocate"),
        cancelButtonText: this.$tc("words.dismiss"),
      }).then((result) => {
        if (result) {
          let meters = []
          editedItems.forEach((e) => {
            let meter = {
              id: e.id,
              lat: e.lat.toFixed(5),
              lng: e.lng.toFixed(5),
            }
            meters.push(meter)
          })
          this.updateEditedMeters(meters)
        }
      })
    })
  },
  watch: {
    $route: function () {
      this.$router.go()
    },
  },
  methods: {
    async getMiniGridData() {
      this.loading = true
      EventBus.$emit("miniGridCachedDataLoading", this.loading)
      await this.$store.dispatch("miniGridDashboard/update")
      this.$store.dispatch("miniGridDashboard/get", this.miniGridId)
      this.miniGridData =
        this.$store.getters["miniGridDashboard/getMiniGridData"]
      this.setDashboardData()
      this.loading = false
      EventBus.$emit("miniGridCachedDataLoading", this.loading)
    },
    async onPeriodChange() {
      let validator = await this.$validator.validateAll()
      if (!validator) {
        return
      }
      this.loading = true
      EventBus.$emit("miniGridCachedDataLoading", this.loading)
      const from =
        this.period.from !== null
          ? moment(this.period.from).format("YYYY-MM-DD")
          : null
      const to =
        this.period.to !== null
          ? moment(this.period.to).format("YYYY-MM-DD")
          : null
      if (from !== null) {
        this.periodText = from + " - " + to
      }
      await this.$store.dispatch("miniGridDashboard/updateByPeriod", {
        from,
        to,
      })
      this.$store.dispatch("miniGridDashboard/get", this.miniGridId)
      this.miniGridData =
        this.$store.getters["miniGridDashboard/getMiniGridData"]
      this.setDashboardData()
      this.setPeriod = false
      this.loading = false
      EventBus.$emit("miniGridCachedDataLoading", this.loading)
    },
    async deviceLocationsEditedSet(editedItems) {
      try {
        await this.deviceAddressService.updateDeviceAddresses(editedItems)
        this.alertNotify("success", "Device locations updated successfully!")
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    setDashboardData() {
      this.miniGridData.revenueList.averages = this.calculateAverages(
        this.miniGridData.revenueList,
      )
      this.donutData = this.batchRevenueService.initializeDonutCharts(
        [this.$tc("words.connection"), this.$tc("words.revenue")],
        this.miniGridData,
      )
      this.targetRevenueChartData =
        this.batchRevenueService.initializeColumnChart(this.miniGridData)
      this.setDonutChartOptions()
      this.fillTicketChart()
      this.fillRevenueTrendsOverView()
      this.fillRevenueTrends(this.tab)
    },
    checkToday() {
      if (moment().format("YYYY-MM-DD") === this.endDate) {
        return "(Today)"
      }
    },
    setMiniGrid(miniGridId) {
      this.$router.replace("/dashboards/mini-grid/" + miniGridId)
    },
    togglePeriod() {
      this.period = {
        from: null,
        to: null,
      }
      this.setPeriod = !this.setPeriod
    },
    setDonutChartOptions() {
      this.donutChartOptions = {
        pieHole: 1,
        legend: "bottom",
        height: 500,
      }
    },
    calculateAverages(list) {
      let data = {}
      for (let connection in list.target.targets) {
        let result = "-"
        if (list.revenue[connection] > 0) {
          result =
            parseInt(list.revenue[connection]) /
            list.totalConnections[connection]
        }
        data[connection] = result
      }
      return data
    },
    fillTicketChart() {
      let openedTicketChartData = []
      let closedTicketChartData = []

      // Check if tickets data exists and has categories
      if (!this.miniGridData.tickets || !this.miniGridData.tickets.categories) {
        this.openedTicketChartData = []
        return
      }

      openedTicketChartData.push([i18n.tc("words.period")])
      closedTicketChartData.push([i18n.tc("words.period")])
      for (let category in this.miniGridData.tickets.categories) {
        openedTicketChartData[0].push(
          this.miniGridData.tickets.categories[category].label_name,
        )
        closedTicketChartData[0].push(
          this.miniGridData.tickets.categories[category].label_name,
        )
      }

      for (let oT in this.miniGridData.tickets) {
        if (oT === "categories") {
          continue
        }
        let ticketCategoryData = this.miniGridData.tickets[oT]

        let ticketChartDataOpened = [oT]
        let ticketChartDataClosed = [oT]

        // Ensure we have data for all categories in the same order as the header
        for (let category in this.miniGridData.tickets.categories) {
          const categoryName =
            this.miniGridData.tickets.categories[category].label_name
          const ticketData = ticketCategoryData[categoryName] || {
            opened: 0,
            closed: 0,
          }
          ticketChartDataOpened.push(ticketData.opened || 0)
          ticketChartDataClosed.push(ticketData.closed || 0)
        }

        openedTicketChartData.push(ticketChartDataOpened)
        openedTicketChartData.push(ticketChartDataClosed)
        closedTicketChartData.push(ticketChartDataClosed)
      }

      this.openedTicketChartData = openedTicketChartData
    },
    fillRevenueTrendsOverView() {
      this.trendChartData.overview = [[i18n.tc("words.date")]]

      for (let dt in this.miniGridData.period) {
        for (let tariffNames in this.miniGridData.period[dt]) {
          this.trendChartData.overview[0].push(tariffNames)
        }
        this.trendChartData.overview[0].push(i18n.tc("words.total"))
        break
      }
      for (let x in this.miniGridData.period) {
        let tmpChartData = [x]
        let totalRev = 0
        for (let d in this.miniGridData.period[x]) {
          tmpChartData.push(this.miniGridData.period[x][d].revenue)
          totalRev += this.miniGridData.period[x][d].revenue
        }
        tmpChartData.push(totalRev)
        this.trendChartData.overview.push(tmpChartData)
      }

      return this.trendChartData.overview
    },
    fillRevenueTrends(tab) {
      this.trendChartData.base = [[i18n.tc("words.date")]]
      this.trendChartData.compare = [[i18n.tc("words.date")]]

      for (let dt in this.miniGridData.period) {
        for (let tariffNames in this.miniGridData.period[dt]) {
          this.trendChartData.base[0].push(tariffNames)
          this.trendChartData.compare[0].push(tariffNames)
        }
        this.trendChartData.base[0].push(i18n.tc("words.total"))
        this.trendChartData.compare[0].push(i18n.tc("words.total"))
        if (tab !== "weekly") {
          break
        }
      }

      for (let x in this.miniGridData.period) {
        let tmpChartData = [x]
        let totalRev = 0
        for (let d in this.miniGridData.period[x]) {
          tmpChartData.push(this.miniGridData.period[x][d].revenue)
          totalRev += this.miniGridData.period[x][d].revenue
        }
        tmpChartData.push(totalRev)
        this.trendChartData.base.push(tmpChartData)
        this.trendChartData.base.splice(50)
      }
      return this.trendChartData.base
    },
    openEditModal() {
      this.editName = this.miniGridData.name || ""
      this.showModal = true
    },
    async updateMiniGrid() {
      if (!this.editName || !this.editName.trim()) {
        this.alertNotify("error", this.$tc("phrases.nameRequired"))
        return
      }
      try {
        await this.miniGridService.updateMiniGrid(this.miniGridId, {
          name: this.editName.trim(),
        })
        this.showModal = false
        this.alertNotify("success", this.$tc("phrases.miniGridUpdated"))
        await this.getMiniGridData()
      } catch (e) {
        this.alertNotify("error", e.message || this.$tc("phrases.updateFailed"))
      }
    },
    confirmDelete() {
      this.$swal({
        type: "question",
        title: this.$tc("phrases.deleteMiniGrid"),
        text: this.$tc("phrases.deleteMiniGridNotify", 0, {
          name: this.miniGridData.name,
        }),
        width: "35%",
        confirmButtonText: this.$tc("words.confirm"),
        showCancelButton: true,
        cancelButtonText: this.$tc("words.cancel"),
        focusCancel: true,
      }).then((result) => {
        if (result.value) {
          this.deleteMiniGrid()
        }
      })
    },
    async deleteMiniGrid() {
      try {
        await this.miniGridService.deleteMiniGrid(this.miniGridId)
        this.alertNotify("success", this.$tc("phrases.miniGridDeleted"))
        this.$router.push("/dashboards/mini-grid")
      } catch (e) {
        this.alertNotify("error", e.message || this.$tc("phrases.deleteFailed"))
      }
    },
    calculateRevenuePercent(current, compared) {
      if (current + compared === 0) return -1
      return Math.round((current * 100) / compared)
    },
  },
  computed: {
    miniGridList() {
      return this.$store.getters["miniGridDashboard/getMiniGridsData"].map(
        (miniGrid) => {
          return {
            id: miniGrid.id,
            name: miniGrid.name,
          }
        },
      )
    },
  },
}
</script>

<style scoped lang="scss">
.map-area {
  z-index: 1 !important;
}
.period-selector {
  position: absolute;
  top: 120px;
  right: 20px;
  z-index: 10;
  padding: 15px;
  background-color: white;
  border: 1px solid #ccc;
  margin-right: 1rem;
  margin-top: 2rem;
}

.close-period > button {
  font-size: 2rem;
  background-color: #7f9919;
  color: whitesmoke;
}

.period-navigation > .arrows {
  position: absolute;
  top: 1.5rem;
}
.vdp-datepicker__calendar .cell.selected {
  background: #90caf9 !important;
}
</style>
