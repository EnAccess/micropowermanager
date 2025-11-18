<template>
  <div>
    <widget :id="'ticketing-trends'" :title="$tc('phrases.ticketsOverview')">
      <div v-if="loading">
        <loader />
      </div>
      <div class="md-layout md-gutter" v-else>
        <div
          class="md-layout-item md-medium-size-100 md-xsmall-size-100 md-size-100"
        >
          <h5 class="chart-title">{{ $tc("phrases.ticketsOverview", 2) }}</h5>
          <div v-if="!hasValidData" class="no-data-message">
            <p>{{ $tc("phrases.noData") }}</p>
          </div>
          <div v-else style="height: 600px; width: 100%; position: relative">
            <v-chart
              v-if="
                echartsOption &&
                echartsOption.series &&
                echartsOption.series[0] &&
                echartsOption.series[0].data &&
                echartsOption.series[0].data.length > 0
              "
              :options="echartsOption"
              :autoresize="true"
              style="height: 600px; width: 100%; min-height: 600px"
            />
            <div
              v-else
              style="
                padding: 2rem;
                text-align: center;
                color: #999;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
              "
            >
              {{ $tc("phrases.noData") || "No Data Available" }}
            </div>
          </div>
        </div>
      </div>
    </widget>
  </div>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import Loader from "@/shared/Loader.vue"
import { EventBus } from "@/shared/eventbus"

export default {
  name: "TicketsOverview",
  components: { Loader, Widget },
  props: {
    chartOptions: {
      required: true,
    },
    ticketData: {
      required: true,
    },
  },
  mounted() {
    EventBus.$on("miniGridCachedDataLoading", (loading) => {
      this.loading = loading
    })
  },
  watch: {
    ticketData: {
      handler(newData) {
        if (newData && Array.isArray(newData) && newData.length > 0) {
          this.normalizedData = this.normalizeChartData(newData)
        } else {
          this.normalizedData = null
        }
      },
      immediate: true,
    },
  },
  data() {
    return {
      loading: false,
      normalizedData: null,
    }
  },
  computed: {
    hasValidData() {
      return (
        this.ticketData &&
        Array.isArray(this.ticketData) &&
        this.ticketData.length > 0
      )
    },
    displayData() {
      if (!this.hasValidData) {
        return [
          ["Period", "No Data"],
          ["", 0],
        ]
      }

      return (
        this.normalizedData || [
          ["Period", "No Data"],
          ["", 0],
        ]
      )
    },
    echartsOption() {
      if (
        !this.hasValidData ||
        !this.displayData ||
        this.displayData.length < 2
      ) {
        return null
      }

      const data = this.displayData
      const headers = data[0] || []
      const periodIndex = 0
      const seriesNames = headers.slice(1)

      const periods = data.slice(1).map((row) => row[periodIndex] || "")

      const series = seriesNames.map((name, index) => {
        const seriesIndex = index + 1
        return {
          name: String(name || `Series ${index + 1}`),
          type: "bar",
          stack: "total",
          data: data.slice(1).map((row) => parseFloat(row[seriesIndex]) || 0),
        }
      })

      return {
        tooltip: {
          trigger: "axis",
          axisPointer: {
            type: "shadow",
          },
        },
        legend: {
          data: seriesNames.map((name) => String(name)),
          top: 10,
        },
        grid: {
          left: "3%",
          right: "4%",
          bottom: "15%",
          containLabel: true,
        },
        xAxis: {
          type: "category",
          data: periods,
          axisLabel: {
            rotate: 45,
            interval: 0,
            showMaxLabel: true,
            showMinLabel: true,
          },
        },
        yAxis: {
          type: "value",
          min: 0,
        },
        series: series,
      }
    },
  },
  methods: {
    normalizeChartData(data) {
      if (!data || data.length === 0) {
        return [
          ["Period", "No Data"],
          ["", 0],
        ]
      }

      // Limit data to prevent performance issues and improve readability
      // These limits ensure the chart remains responsive even with large datasets
      const maxRows = 101 // 1 header + 100 data rows (approximately 2 years of weekly data)
      const maxColumns = 21 // 1 period column + 20 ticket categories max
      const limitedData = data.length > maxRows ? data.slice(0, maxRows) : data

      // Limit columns to most relevant categories (first few categories + period column)
      const limitedColumnsData = limitedData.map((row) => {
        if (Array.isArray(row)) {
          return row.slice(0, maxColumns)
        }
        return row
      })

      // Get the expected number of columns from the header row
      const expectedColumns = limitedColumnsData[0]
        ? limitedColumnsData[0].length
        : 0

      if (expectedColumns === 0) {
        return [
          ["Period", "No Data"],
          ["", 0],
        ]
      }

      // Normalize each row to have the same number of columns
      const normalizedData = new Array(limitedColumnsData.length)

      for (let i = 0; i < limitedColumnsData.length; i++) {
        const row = limitedColumnsData[i]

        if (!Array.isArray(row)) {
          normalizedData[i] = new Array(expectedColumns).fill(null)
          continue
        }

        const rowLength = row.length

        if (rowLength === expectedColumns) {
          // Row already has correct number of columns
          normalizedData[i] = [...row]
        } else if (rowLength < expectedColumns) {
          // Pad with null values
          normalizedData[i] = [
            ...row,
            ...new Array(expectedColumns - rowLength).fill(null),
          ]
        } else {
          // Truncate to expected length
          normalizedData[i] = row.slice(0, expectedColumns)
        }
      }

      // Additional validation: ensure all numeric values are proper numbers
      for (let i = 1; i < normalizedData.length; i++) {
        for (let j = 1; j < normalizedData[i].length; j++) {
          const value = normalizedData[i][j]
          if (value === null || value === undefined || value === "") {
            normalizedData[i][j] = 0
          } else if (typeof value === "string") {
            const numValue = parseFloat(value)
            normalizedData[i][j] = isNaN(numValue) ? 0 : numValue
          } else if (typeof value === "number" && value < 0) {
            normalizedData[i][j] = 0 // Ensure no negative values
          }
        }
      }

      return normalizedData
    },
  },
}
</script>

<style scoped>
.no-data-message {
  text-align: center;
  padding: 2rem;
  color: #666;
}

.no-data-message p {
  margin-bottom: 0.5rem;
  font-size: 1.1rem;
}

.no-data-message small {
  color: #999;
  font-style: italic;
}

.chart-title {
  text-align: center;
  margin: 0.5rem 0 1rem 0;
  padding: 0 1rem;
  line-height: 2;
  word-wrap: break-word;
}

.chart-note {
  font-size: 0.9rem;
  color: #666;
  margin: 0.5rem 0;
  font-style: italic;
}
</style>
