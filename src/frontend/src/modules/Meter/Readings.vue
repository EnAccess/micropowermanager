<template>
  <widget
    :title="$tc('phrases.meterReadings')"
    :id="'meter-readings'"
    button
    button-text="Set Period"
    button-color="red"
    @widgetAction="toggleDateSelection"
    button-icon="calendar_today"
  >
    <div v-if="dates.show_selector" class="period-selector">
      <p>{{ $tc("phrases.selectPeriod") }}</p>
      <div class="md-layout md-gutter">
        <div class="md-layout-item md-size-100">
          <md-datepicker
            :class="{
              'md-invalid': errors.has($tc('phrases.fromDate')),
            }"
            :name="$tc('phrases.fromDate')"
            md-immediately
            v-model="dates.dateOne"
            v-validate="'required'"
            :md-close-on-blur="false"
          >
            <label>{{ $tc("phrases.fromDate") }}</label>
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
            v-model="dates.dateTwo"
            v-validate="'required'"
            :md-close-on-blur="false"
          >
            <label>{{ $tc("phrases.toDate") }}</label>
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
          @click="getConsumptions"
        >
          {{ $tc("words.send") }}
        </button>
      </div>
    </div>

    <md-card>
      <md-card-content>
        <div v-if="chartData.length > 0">
          <GChart
            type="LineChart"
            :data="chartData"
            :options="chartOptions"
          ></GChart>
        </div>

        <div
          v-if="chartData.length === 0 && loading === false"
          class="text-center"
        >
          <md-card-content class="no-data-placeholder">
            <h2>
              {{ $tc("phrases.noData") }} {{ dates.dateOne }} -
              {{ dates.dateTwo }}
            </h2>
          </md-card-content>
        </div>
      </md-card-content>
    </md-card>
  </widget>
</template>

<script>
import Widget from "@/shared/Widget.vue"
import moment from "moment"
import { Consumptions } from "@/services/MeterConsumptionService"
import { currency } from "@/mixins/currency"

export default {
  name: "Readings.vue",
  components: { Widget },
  mixins: [currency],
  props: {
    meter: {
      type: Object,
    },
  },
  data() {
    return {
      chartData: [],
      chartOptions: {
        chart: {
          title: "Company Performance",
          subtitle: "Sales, Expenses, and Profit: 2014-2017",
        },
        height: 400,
        colors: ["#1b9e77", "#d95f02", "#7570b3"],
      },
      dates: {
        dateTwo: null,
        dateOne: null,
        today: null,
        difference: 0,
        show_selector: false,
      },
      loading: true,
      consumptions: null,
    }
  },
  created() {
    //initialize dates
    let baseDate = moment()
    this.dates.today = baseDate.format("YYYY-MM-DD")
    this.dates.dateTwo = baseDate.add(-1, "days").format("YYYY-MM-DD")
    this.dates.dateOne = baseDate.add(-1, "weeks").format("YYYY-MM-DD")
  },
  mounted() {
    this.consumptions = new Consumptions(this.$route.params.id)
    this.getConsumptions()
  },
  methods: {
    toggleDateSelection() {
      this.dates.show_selector = !this.dates.show_selector
    },
    getConsumptions() {
      this.loading = true
      this.chartData = []
      this.consumptions
        .getData(this.dates.dateOne, this.dates.dateTwo)
        .then(() => {
          this.loading = false
          if (this.consumptions.data.length === 0) {
            this.chartData = []
            return
          }
          this.chartData.push([
            this.$tc("words.date"),
            this.$tc("words.consumption"),
            this.$tc("words.credit"),
          ])
          this.chartData = this.chartData.concat(this.consumptions.data)
          this.dates.show_selector = false
        })
    },
  },
}
</script>

<style scoped>
.no-data-placeholder {
  margin-top: 100px;
  margin-bottom: 100px;
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
  margin-top: 2rem;
}
</style>
