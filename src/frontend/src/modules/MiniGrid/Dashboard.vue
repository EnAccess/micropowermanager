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
              <div v-if="setPeriod" class="period-selector">
                <p>{{ $tc("phrases.selectPeriod") }}</p>
                <div class="md-layout md-gutter">
                  <div class="md-layout-item md-size-100">
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
                  <div class="md-layout-item md-size-100">
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
          </md-toolbar>
        </div>
        <div class="md-layout-item md-size-100">
          <box-group
            ref="box"
            :mini-grid-id="miniGridId"
            :miniGridData="miniGridData"
          ></box-group>
        </div>

        <div
          class="md-layout-item md-layout md-gutter md-size-100"
          style="z-index: -1"
        >
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
          />
        </div>
      </div>

      <transition name="modal" v-if="showModal">
        <div class="modal-mask">
          <div class="modal-wrapper">
            <div class="modal-container">
              <md-card class="md-size-100">
                <md-card-header>
                  <h3>
                    {{ $tc("words.edit") }}
                    {{ miniGridData.name }}
                  </h3>
                </md-card-header>
                <md-card-content>
                  <md-field>
                    <label for="mini-grid-name">
                      {{ $tc("words.name") }}
                    </label>
                    <md-input
                      type="text"
                      id="mini-grid-name"
                      class="form-control"
                      :value="miniGridData.name"
                    ></md-input>
                  </md-field>

                  <md-field>
                    <label for="mini-grid-location">
                      {{ $tc("words.location") }}
                    </label>
                    <md-input
                      type="text"
                      id="mini-grid-location"
                      class="form-control"
                      :value="
                        miniGridData.location !== undefined
                          ? miniGridData.location.points
                          : ''
                      "
                      placeholder="Latitude, Longitude"
                    ></md-input>
                  </md-field>
                </md-card-content>
                <md-card-actions>
                  <md-button
                    class="md-raised md-accent"
                    @click="showModal = false"
                  >
                    <md-icon>cancel</md-icon>
                    {{ $tc("words.close") }}
                  </md-button>

                  <md-button
                    @click="updateMiniGrid"
                    class="md-raised md-primary"
                  >
                    {{ $tc("words.update") }}
                  </md-button>
                </md-card-actions>
              </md-card>
            </div>
          </div>
        </div>
      </transition>
    </section>
  </div>
</template>

<script>
import RevenueTargetPerCustomerType from "@/modules/MiniGrid/RevenueTargetPerCustomerType.vue"
import MiniGridMap from "@/modules/Map/MiniGridMap.vue"
import RevenueTrends from "@/modules/MiniGrid/RevenueTrends.vue"
import BoxGroup from "@/modules/MiniGrid/BoxGroup.vue"
import TicketsOverview from "@/modules/MiniGrid/TicketsOverview.vue"
import RevenuePerCustomerType from "@/modules/MiniGrid/RevenuePerCustomerType.vue"
import { currency } from "@/mixins/currency"
import { notify } from "@/mixins/notify"
import { MiniGridService } from "@/services/MiniGridService"
import { BatchRevenueService } from "@/services/BatchRevenueService"
import { EventBus } from "@/shared/eventbus"
import i18n from "@/i18n"
import { ICONS, MappingService } from "@/services/MappingService"
import { DeviceAddressService } from "@/services/DeviceAddressService"
import moment from "moment"
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
      this.setDonutChartOptions(this.donutData)
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
    setDonutChartOptions(donutData) {
      let value = donutData.reduce((acc, curr) => {
        if (curr[1] > 0) {
          acc = true
        }
        return acc
      }, false)
      if (value) {
        this.donutChartOptions = {
          pieHole: 1,
          legend: "bottom",
          height: 500,
        }
      } else {
        this.donutData = []
        this.donutData.push([
          this.$tc("words.connection"),
          this.$tc("words.revenue"),
        ])
        this.donutData.push(["", { v: 1, f: this.$tc("phrases.noData") }])
        this.donutChartOptions.chartArea = {
          left: "15%",
        }
        this.donutChartOptions.colors = ["transparent"]
        this.donutChartOptions.pieSliceBorderColor = "#9e9e9e"
        this.donutChartOptions.pieSliceText = "value"
        this.donutChartOptions.pieSliceTextStyle = {
          color: "#9e9e9e",
        }
        this.donutChartOptions.tooltip = {
          trigger: "none",
        }
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

      openedTicketChartData.push([i18n.tc("words.period")])
      closedTicketChartData.push([i18n.tc("words.period")])
      for (let category in this.miniGridData.tickets.categories) {
        openedTicketChartData[0].push(
          this.miniGridData.tickets.categories[category].label_name,
        )
        openedTicketChartData[0].push({
          type: "string",
          role: "tooltip",
        })
        closedTicketChartData[0].push(
          this.miniGridData.tickets.categories[category].label_name,
        )
        closedTicketChartData[0].push({
          type: "string",
          role: "tooltip",
        })
      }

      for (let oT in this.miniGridData.tickets) {
        if (oT === "categories") {
          continue
        }
        let ticketCategoryData = this.miniGridData.tickets[oT]

        let ticketChartDataOpened = [oT]
        let ticketChartDataClosed = [oT]

        for (let tD in ticketCategoryData) {
          let ticketData = ticketCategoryData[tD]
          ticketChartDataOpened.push(
            ticketData.opened,
            oT +
              "\n" +
              [tD] +
              " : " +
              ticketData.opened +
              " " +
              i18n.tc("words.open", 2),
          )
          ticketChartDataClosed.push(
            ticketData.closed,
            oT +
              "\n" +
              [tD] +
              " : " +
              ticketData.closed +
              " " +
              i18n.tc("words.close", 2),
          )
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
    editMiniGrid() {
      this.showModal = true
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

<style>
.map-area {
  z-index: 1 !important;
}

.period-selector {
  position: absolute;
  top: 0;
  right: 0;
  z-index: 9999;
  padding: 15px;
  background-color: white;
  border: 1px solid #ccc;
  margin-right: 1rem;
  margin-top: 3rem;
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

.progress {
  height: 10px;
  background: #333;
  border-radius: 0;
  box-shadow: none;
  margin-bottom: 30px;
  overflow: visible;
}

.progress .progress-bar {
  position: relative;
  -webkit-animation: animate-positive 2s;
  animation: animate-positive 2s;
}

.progress .progress-bar:after {
  content: "";
  display: inline-block;
  width: 9px;
  background: #fff;
  position: absolute;
  top: -10px;
  bottom: -10px;
  right: -1px;
  z-index: 1;
  transform: rotate(35deg);
}

.progress .progress-value {
  display: block;
  font-size: 16px;
  font-weight: 600;
  color: #333;
  position: absolute;
  top: -30px;
  right: -25px;
}

.tooltip .tooltip-inner {
  background: black;
  color: white;
  border-radius: 16px;
  padding: 5px 10px 4px;
}

.tooltip .tooltip-arrow {
  width: 0;
  height: 0;
  border-style: solid;
  position: absolute;
  margin: 5px;
  border-color: black;
  z-index: 1;
}

.tooltip[x-placement^="top"] .tooltip-arrow {
  border-width: 5px 5px 0 5px;
  border-left-color: transparent !important;
  border-right-color: transparent !important;
  border-bottom-color: transparent !important;
  bottom: -5px;
  left: calc(50% - 5px);
  margin-top: 0;
  margin-bottom: 0;
}

.tooltip[x-placement^="bottom"] .tooltip-arrow {
  border-width: 0 5px 5px 5px;
  border-left-color: transparent !important;
  border-right-color: transparent !important;
  border-top-color: transparent !important;
  top: -5px;
  left: calc(50% - 5px);
  margin-top: 0;
  margin-bottom: 0;
}

.tooltip[x-placement^="right"] .tooltip-arrow {
  border-width: 5px 5px 5px 0;
  border-left-color: transparent !important;
  border-top-color: transparent !important;
  border-bottom-color: transparent !important;
  left: -5px;
  top: calc(50% - 5px);
  margin-left: 0;
  margin-right: 0;
}

.tooltip[x-placement^="left"] .tooltip-arrow {
  border-width: 5px 0 5px 5px;
  border-top-color: transparent !important;
  border-right-color: transparent !important;
  border-bottom-color: transparent !important;
  right: -5px;
  top: calc(50% - 5px);
  margin-left: 0;
  margin-right: 0;
}

.tooltip.popover .popover-inner {
  background: #f9f9f9;
  color: black;
  padding: 24px;
  border-radius: 5px;
  box-shadow: 0 5px 30px rgba(black, 0.1);
}

.tooltip.popover .popover-arrow {
  border-color: #f9f9f9;
}

.modal-mask {
  position: fixed;
  z-index: 1001;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: table;
  transition: opacity 0.3s ease;
}

.modal-wrapper {
  display: table-cell;
  vertical-align: middle;
}

.modal-container {
  margin: 0px auto;
  padding: 20px 30px;
  background-color: #fff;
  border-radius: 2px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
  transition: all 0.3s ease;
  font-family: Helvetica, Arial, sans-serif;
  max-height: 85%;
  overflow-y: scroll;
}

@media only screen and (max-width: 1024px) {
  .modal-container {
    width: 99% !important;
  }
}

@media only screen and (min-width: 1024px) {
  .modal-container {
    width: 55% !important;
  }
}

.modal-header h3 {
  margin-top: 0;
  color: #42b983;
}

.modal-enter .modal-container,
.modal-leave-active .modal-container {
  -webkit-transform: scale(1.1);
  transform: scale(1.1);
}

.data-stream-switch {
  margin-left: 3rem !important;
}

.vdp-datepicker__calendar .cell.selected {
  background: #90caf9 !important;
}
</style>
