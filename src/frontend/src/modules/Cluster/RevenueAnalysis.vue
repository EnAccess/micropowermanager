<template>
  <widget :id="'revenue-types'" :title="'Revenue Analysis'">
    <table-list>
      <thead slot="header">
        <tr>
          <th>fdsfsdaType</th>
          <th class="hidden-xs" colspan="2">Connections</th>
          <th :colspan="compareAnalysisAvailable ? 3 : 1">Revenues</th>
          <th class="hidden-xs" :colspan="compareAnalysisAvailable ? 2 : 1">
            Avg Revenue Per Connection
          </th>
        </tr>
        <tr>
          <th></th>
          <th class="hidden-xs">Status now</th>
          <th class="hidden-xs">New Connections/Target</th>
          <th>This Period</th>
          <th v-if="compareAnalysisAvailable">Compared Period</th>
          <th>Target</th>
          <th class="hidden-xs">This period</th>
          <th class="hidden-xs" v-if="compareAnalysisAvailable">
            Compared Period
          </th>
        </tr>
      </thead>
      <tbody slot="body">
        <tr v-for="(revenue, index) in revenues" :key="index">
          <td>{{ revenue.tariff }}</td>
          <td class="hidden-xs">
            <span class="base-color">
              {{ revenue.totalConnections }}
            </span>
            <span
              v-if="compareAnalysisAvailable && false"
              class="compare-color"
            >
              / {{ revenue.compareNewConnections }}
            </span>
          </td>
          <td class="hidden-xs">
            {{ revenue.newConnections }}/{{
              baseTargetData(revenue.tariff, "connections")
            }}
          </td>

          <td>
            {{ readable(revenue.revenue) }}
            {{ $store.state.mSettings.currency }}
            <span
              v-if="
                compareAnalysisAvailable &&
                calculateRevenuePercent(
                  revenue.revenue,
                  revenue.compareRevenue,
                ) !== -1
              "
              :class="
                calculateRevenuePercent(
                  revenue.revenue,
                  revenue.compareRevenue,
                ) < 100
                  ? 'red'
                  : 'green'
              "
            >
              <br />
              {{
                calculateRevenuePercent(revenue.revenue, revenue.compareRevenue)
              }}
              %
            </span>
            <span v-else>
              <br />
              -
            </span>
          </td>

          <td class="hidden-xs" v-if="compareAnalysisAvailable">
            {{ readable(revenue.compareRevenue) }}
            {{ $store.state.mSettings.currency }}
          </td>
          <td class="hidden-xs">
            {{ baseTargetData(revenue.tariff, "revenue") }}
            <br />
            <small
              :class="
                calculateRevenueTargetPercentage(
                  revenue.revenue,
                  baseTargetData(revenue.tariff, 'revenue'),
                ) > 99
                  ? 'green'
                  : 'red'
              "
            >
              ({{
                calculateRevenueTargetPercentage(
                  revenue.revenue,
                  baseTargetData(revenue.tariff, "revenue"),
                )
              }}
              %)
            </small>
          </td>
          <td>
            {{ readable(revenue.revenuePerConnection) }}
            {{ $store.state.mSettings.currency }}
          </td>
          <td v-if="compareAnalysisAvailable">
            {{ readable(revenue.compareRevenuePerConnection) }}
            {{ $store.state.mSettings.currency }}
          </td>
        </tr>
        <tr>
          <td colspan="8"></td>
        </tr>
        <tr>
          <td>Total</td>
          <td>{{ totalRevenues.totalConnections }}</td>
          <td>
            {{ totalRevenues.connections }} /
            {{ totalConnectionsByTarget() }}
          </td>
          <td>
            {{ readable(totalRevenues.revenue) }}
            {{ $store.state.mSettings.currency }}
          </td>
          <td v-if="compareAnalysisAvailable">&nbsp;</td>
          <td v-if="compareAnalysisAvailable">
            {{ readable(totalRevenues.compareRevenue) }}
            {{ $store.state.mSettings.currency }}
          </td>
          <td>
            {{ readable(totalRevenues.revenuePerConnection) }}
            {{ $store.state.mSettings.currency }}
          </td>
          <td v-if="compareAnalysisAvailable">
            {{ readable(totalRevenues.compareRevenuePerConnection) }}
            {{ $store.state.mSettings.currency }}
          </td>
        </tr>
      </tbody>
    </table-list>
  </widget>
</template>

<script>
export default {
  name: "RevenueAnalysis",
  props: {
    clusterId: {
      type: String,
      required: true,
    },
  },
  mounted() {
    this.getMiniGridsAnalysis()
  },
  data() {
    return {
      clusterTrends: null,
      period: {},
    }
  },
  methods: {
    getMiniGridsAnalysis() {},
    fillAnalysis() {},
  },
}
</script>

<style scoped></style>
