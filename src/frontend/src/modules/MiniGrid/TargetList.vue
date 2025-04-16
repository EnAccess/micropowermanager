<template>
  <widget
    :id="'revenue-types'"
    :title="$tc('phrases.revenueAnalysis')"
    :subscriber="subscriber"
    color="green"
  >
    <md-table>
      <md-table-row>
        <md-table-head>Type</md-table-head>
        <md-table-head colspan="2">
          {{ $tc("words.connection", 2) }}
        </md-table-head>
        <md-table-head :colspan="compareAnalysisAvailable ? 3 : 2">
          {{ $tc("words.revenue", 2) }}
        </md-table-head>
        <md-table-head class="md-xsmall-hide">
          {{ $tc("phrases.avgRevenuePerConnection") }}
        </md-table-head>
      </md-table-row>

      <md-table-row>
        <md-table-head class="md-xsmall-hide">
          {{ $tc("phrases.statusNow") }}
        </md-table-head>
        <md-table-head class="md-xsmall-hide">
          {{ $tc("phrases.newConnectionsTarget") }}
        </md-table-head>
        <md-table-head>
          {{ $tc("phrases.thisPeriod") }}
          <small>{{ base.from + "-" + base.to }}</small>
        </md-table-head>
        <md-table-head v-if="compareAnalysisAvailable">
          {{ $tc("phrases.comparedPeriod") }}
          <small>{{ compared.from + "-" + compared.to }}</small>
        </md-table-head>
        <md-table-head>{{ $tc("words.target") }}</md-table-head>
        <md-table-head class="md-xsmall-hide">
          {{ $tc("phrases.thisPeriod") }}
        </md-table-head>
        <md-table-head class="md-xsmall-hide" v-if="compareAnalysisAvailable">
          {{ $tc("phrases.comparedPeriod") }}
        </md-table-head>
      </md-table-row>

      <template v-if="batchRevenues.revenueList !== null">
        <md-table-row
          v-for="(revenue, index) in batchRevenues.revenueList.target.targets"
          :key="index"
        >
          <md-table-cell>{{ index }}</md-table-cell>
          <md-table-cell>
            {{ batchRevenues.revenueList.total_connections[index] }}
          </md-table-cell>
          <md-table-cell>
            {{ batchRevenues.revenueList.new_connections[index] }} /
            {{ revenue.new_connections }}
          </md-table-cell>
          <md-table-cell v-if="batchRevenues.revenueList.revenue">
            {{ readable(batchRevenues.revenueList.revenue[index]) }}
          </md-table-cell>
          <md-table-cell
            v-if="
              comparedRevenues.revenueList !== null &&
              'revenue' in comparedRevenues.revenueList
            "
          >
            {{ readable(comparedRevenues.revenueList.revenue[index]) }}
          </md-table-cell>
          <md-table-cell>
            {{ readable(revenue.revenue) }}
          </md-table-cell>
          <md-table-cell v-if="batchRevenues.revenueList !== null">
            {{ readable(batchRevenues.revenueList.averages[index]) }}
          </md-table-cell>
          <md-table-cell v-else>-</md-table-cell>
          <md-table-cell v-if="compareAnalysisAvailable">
            {{ readable(comparedRevenues.revenueList.averages[index]) }}
          </md-table-cell>
        </md-table-row>
      </template>

      <md-table-row style="margin-top: 2rem">
        <md-table-cell>Total</md-table-cell>
        <md-table-cell>
          {{ totalRevenues.totalConnections }}
        </md-table-cell>
        <md-table-cell>
          {{ totalRevenues.newConnections }} /
          {{ totalRevenues.targetConnections }}
        </md-table-cell>
        <md-table-cell>
          {{ readable(totalRevenues.revenue) }}
        </md-table-cell>
        <md-table-cell v-if="compareAnalysisAvailable">
          {{ readable(totalRevenues.comparedRevenue) }}
        </md-table-cell>
        <md-table-cell>
          {{ readable(totalRevenues.totalTargetRevenue) }}
        </md-table-cell>
        <md-table-cell>
          {{ readable(totalRevenues.totalAverage) }}
        </md-table-cell>
        <md-table-cell v-if="compareAnalysisAvailable">
          {{ totalRevenues.comparedTotalAverage }}
        </md-table-cell>
      </md-table-row>
    </md-table>
  </widget>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import moment from "moment"
import { currency } from "@/mixins/currency"
import { EventBus } from "@/shared/eventbus"
import {
  BatchRevenue,
  BatchRevenueService,
} from "@/services/BatchRevenueService"
import { notify } from "@/mixins/notify"

export default {
  name: "TargetList",
  components: { Widget },
  mixins: [currency, notify],
  props: {
    targetId: {
      type: String,
      required: true,
    },
    targetType: {
      type: String,
      required: true,
    },
    base: {
      type: Object,
      required: true,
    },
    compared: {
      type: Object,
      default: () => {
        return {}
      },
    },
  },
  data() {
    return {
      //bach revenue controller instance
      batchRevenueService: new BatchRevenueService(),
      batchRevenues: new BatchRevenue(),
      comparedRevenues: new BatchRevenue(),
      datesSet: 0, //when 2 fire batchData
      subscriber: "mini-grid-revenues",
      totalRevenues: {
        comparedRevenue: 0,
        totalAverage: 0,
        revenue: 0,
        compareRevenue: 0,
        connections: 0,
        newConnections: 0,
        compareNewConnections: 0,
        totalConnections: 0,
        targetConnections: 0,
        revenuePerConnection: 0,
        compareRevenuePerConnection: 0,
        totalTargetRevenue: 0,
      },
    }
  },
  computed: {
    existingRevenueList: () => {
      return this.batchRevenues.filter(function (revenue) {
        return revenue.revenueList !== null
      })
    },
    compareAnalysisAvailable() {
      return this.comparedRevenues.revenueList !== null
    },
  },
  watch: {
    base: function () {
      this.dataSet = 1
      setTimeout(() => {
        if (this.dataSet === 1) {
          this.getBatchData()
        }
      }, 1500)
    },
    compared: function () {
      this.dataSet = 2
      setTimeout(() => {
        if (this.dataSet === 2) {
          this.getBatchData()
        }
      }, 1500)
    },
  },
  methods: {
    async getBatchData() {
      this.expanded = true
      this.targets = []
      if (Object.keys(this.base).length > 0) {
        let startDate = moment(this.base.from).format("YYYY-MM-DD")
        let endDate = moment(this.base.to).format("YYYY-MM-DD")
        try {
          this.batchRevenues =
            await this.batchRevenueService.getRevenueForPeriod(
              this.targetId,
              this.targetType,
              startDate,
              endDate,
            )
          this.$emit("baseDataAvailable", this.batchRevenues)
          if (Object.keys(this.compared).length === 0) this.totals()
        } catch (e) {
          this.alertNotify("error", e.message)
        }
      }
      if (Object.keys(this.compared).length > 0) {
        //compare data is also available.
        let startDate = moment(this.compared.from).format("YYYY-MM-DD")
        let endDate = moment(this.compared.to).format("YYYY-MM-DD")
        try {
          this.comparedRevenues =
            await this.batchRevenueService.getRevenueForPeriod(
              this.targetId,
              this.targetType,
              startDate,
              endDate,
            )
          this.totals()
        } catch (e) {
          this.alertNotify("error", e.message)
        }
      }
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.batchRevenues.revenueList,
      )
      EventBus.$emit("batchRevenuesLoaded", this.batchRevenues)
    },
    totals() {
      let newConnections = 0
      let totalConnections = 0
      let totalRevenue = 0
      let totalTargetNewConnections = 0
      let totalTargetRevenue = 0

      let comparedRevenue = 0
      let totalAverage = 0
      let affectedAverageConnections = 0

      let comparedTotalAverage = 0
      let comparedAffectedAverageConnections = 0

      for (let connection in this.batchRevenues.revenueList.target.targets) {
        newConnections +=
          this.batchRevenues.revenueList.new_connections[connection]
        totalTargetNewConnections +=
          this.batchRevenues.revenueList.target.targets[connection]
            .new_connections === "-"
            ? 0
            : this.batchRevenues.revenueList.target.targets[connection]
                .new_connections

        totalConnections +=
          this.batchRevenues.revenueList.total_connections[connection] === "-"
            ? 0
            : this.batchRevenues.revenueList.total_connections[connection]
        totalRevenue += parseInt(
          this.batchRevenues.revenueList.revenue[connection],
        )
        totalTargetRevenue +=
          this.batchRevenues.revenueList.target.targets[connection].revenue ===
          "-"
            ? 0
            : this.batchRevenues.revenueList.target.targets[connection].revenue

        if (
          this.batchRevenues.revenueList.total_connections[connection] > 0 &&
          this.batchRevenues.revenueList.averages[connection] !== "-"
        ) {
          totalAverage += this.batchRevenues.revenueList.averages[connection]
          affectedAverageConnections++
        }
      }
      if (this.comparedRevenues.revenueList !== null) {
        for (let connection in this.comparedRevenues.revenueList.target
          .targets) {
          if (
            this.comparedRevenues.revenueList !== "null" &&
            "revenue" in this.comparedRevenues.revenueList
          )
            comparedRevenue += parseInt(
              this.comparedRevenues.revenueList.revenue[connection],
            )

          if (
            this.comparedRevenues.revenueList.total_connections[connection] >
              0 &&
            this.comparedRevenues.revenueList.averages[connection] !== "-"
          ) {
            comparedTotalAverage +=
              this.comparedRevenues.revenueList.averages[connection]
            comparedAffectedAverageConnections++
          }
        }
      }
      this.totalRevenues.totalConnections = totalConnections

      this.totalRevenues.connections = totalConnections
      this.totalRevenues.newConnections = newConnections
      this.totalRevenues.targetConnections = totalTargetNewConnections

      this.totalRevenues.revenue = totalRevenue
      this.totalRevenues.totalTargetRevenue = totalTargetRevenue
      this.totalRevenues.comparedRevenue = comparedRevenue
      this.totalRevenues.totalAverage =
        totalAverage /
        (affectedAverageConnections > 0 ? affectedAverageConnections : 1)
      this.totalRevenues.comparedTotalAverage =
        comparedTotalAverage /
        (comparedAffectedAverageConnections > 0
          ? comparedAffectedAverageConnections
          : 1)

      this.$emit("complete", totalConnections)
    },
  },
}
</script>

<style scoped></style>
