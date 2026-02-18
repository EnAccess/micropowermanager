<template>
  <div class="customer-filter-card">
    <md-card>
      <md-card-header>
        {{ $tc("words.filter") }}
      </md-card-header>
      <md-card-content>
        <div class="md-layout md-gutter">
          <!-- Activity (status) -->
          <div class="md-layout-item md-size-100">
            <md-field>
              <label>{{ $tc("words.activity") }}</label>
              <md-select v-model="localFilters.status">
                <md-option value="all">
                  {{ $tc("words.all") }}
                </md-option>
                <md-option value="active">
                  {{ $tc("words.active") }}
                </md-option>
                <md-option value="inactive">
                  {{ $tc("words.inactive") }}
                </md-option>
              </md-select>
            </md-field>
          </div>

          <!-- Agent -->
          <div class="md-layout-item md-size-100">
            <md-field>
              <label>
                {{ $tc("words.agent") }}
                <span v-if="agents && agents.length">
                  ({{ agents.length }} {{ $tc("words.agents") }})
                </span>
              </label>
              <md-select v-model="localFilters.agentId">
                <md-option :value="null">
                  {{ $tc("words.all") }}
                </md-option>
                <md-option
                  v-for="agent in agents"
                  :key="agent.id"
                  :value="agent.id"
                >
                  {{
                    agent.person
                      ? `${agent.person.name} ${agent.person.surname}`
                      : agent.email
                  }}
                </md-option>
              </md-select>
            </md-field>
          </div>

          <!-- Total paid interval -->
          <div class="md-layout-item md-size-50">
            <md-field>
              <label>{{ $tc("phrases.totalPaidFrom") }}</label>
              <md-input
                v-model.number="localFilters.totalPaidMin"
                type="number"
                min="0"
              />
            </md-field>
          </div>
          <div class="md-layout-item md-size-50">
            <md-field>
              <label>{{ $tc("phrases.totalPaidTo") }}</label>
              <md-input
                v-model.number="localFilters.totalPaidMax"
                type="number"
                min="0"
              />
            </md-field>
          </div>

          <!-- Province / Village -->
          <div class="md-layout-item md-size-100">
            <md-field>
              <label>{{ $tc("phrases.provinceVillage") }}</label>
              <md-select v-model="localFilters.cityId">
                <md-option :value="null">
                  {{ $tc("words.all") }}
                </md-option>
                <md-option
                  v-for="city in cities"
                  :key="city.id"
                  :value="city.id"
                >
                  {{ city.name }}
                </md-option>
              </md-select>
            </md-field>
          </div>

          <!-- Latest payment date -->
          <div class="md-layout-item md-size-50">
            <md-datepicker
              md-immediately
              v-model="localFilters.latestPaymentFrom"
              :md-close-on-blur="false"
            >
              <label>{{ $tc("phrases.latestPaymentFrom") }}</label>
            </md-datepicker>
          </div>
          <div class="md-layout-item md-size-50">
            <md-datepicker
              md-immediately
              v-model="localFilters.latestPaymentTo"
              :md-close-on-blur="false"
            >
              <label>{{ $tc("phrases.latestPaymentTo") }}</label>
            </md-datepicker>
          </div>

          <!-- Registration date -->
          <div class="md-layout-item md-size-50">
            <md-datepicker
              md-immediately
              v-model="localFilters.registrationFrom"
              :md-close-on-blur="false"
            >
              <label>{{ $tc("phrases.registrationFrom") }}</label>
            </md-datepicker>
          </div>
          <div class="md-layout-item md-size-50">
            <md-datepicker
              md-immediately
              v-model="localFilters.registrationTo"
              :md-close-on-blur="false"
            >
              <label>{{ $tc("phrases.registrationTo") }}</label>
            </md-datepicker>
          </div>

          <!-- Device -->
          <div class="md-layout-item md-size-100">
            <md-field>
              <label>{{ $tc("words.deviceType") }}</label>
              <md-select v-model="localFilters.deviceType">
                <md-option value="">{{ $tc("words.all") }}</md-option>
                <md-option
                  v-for="device in deviceTypes"
                  :key="device.type"
                  :value="device.type"
                >
                  {{ device.display }}
                </md-option>
              </md-select>
            </md-field>
          </div>
        </div>
      </md-card-content>
      <md-card-actions>
        <md-button class="md-raised md-primary" @click="apply">
          {{ $tc("words.apply") }}
        </md-button>
        <md-button class="md-raised md-accent" @click="clear">
          {{ $tc("words.clear") }}
        </md-button>
      </md-card-actions>
    </md-card>
  </div>
</template>

<script>
import { mapGetters } from "vuex"
import moment from "moment"

export default {
  name: "CustomerFilter",
  props: {
    agents: {
      type: Array,
      default: () => [],
    },
    cities: {
      type: Array,
      default: () => [],
    },
    value: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      localFilters: {
        status: "all",
        agentId: null,
        totalPaidMin: null,
        totalPaidMax: null,
        cityId: null,
        latestPaymentFrom: null,
        latestPaymentTo: null,
        registrationFrom: null,
        registrationTo: null,
        deviceType: null,
        ...this.value,
      },
    }
  },
  computed: {
    ...mapGetters({
      deviceTypes: "device/getDeviceTypes",
    }),
  },
  watch: {
    value: {
      deep: true,
      handler(newVal) {
        this.localFilters = {
          ...this.localFilters,
          ...newVal,
          latestPaymentFrom: newVal.latestPaymentFrom
            ? moment(newVal.latestPaymentFrom).toDate()
            : null,
          latestPaymentTo: newVal.latestPaymentTo
            ? moment(newVal.latestPaymentTo).toDate()
            : null,
          registrationFrom: newVal.registrationFrom
            ? moment(newVal.registrationFrom).toDate()
            : null,
          registrationTo: newVal.registrationTo
            ? moment(newVal.registrationTo).toDate()
            : null,
        }
      },
    },
  },
  methods: {
    normalizeDate(date, endOfDay = false) {
      if (!date) return null
      const m = moment(date)
      if (!m.isValid()) return null

      return (endOfDay ? m.endOf("day") : m.startOf("day")).toISOString()
    },
    buildPayload() {
      return {
        status: this.localFilters.status,
        agentId: this.localFilters.agentId,
        totalPaidMin: this.localFilters.totalPaidMin,
        totalPaidMax: this.localFilters.totalPaidMax,
        cityId: this.localFilters.cityId,
        latestPaymentFrom: this.normalizeDate(
          this.localFilters.latestPaymentFrom,
        ),
        latestPaymentTo: this.normalizeDate(
          this.localFilters.latestPaymentTo,
          true,
        ),
        registrationFrom: this.normalizeDate(
          this.localFilters.registrationFrom,
        ),
        registrationTo: this.normalizeDate(
          this.localFilters.registrationTo,
          true,
        ),
        deviceType: this.localFilters.deviceType,
      }
    },
    apply() {
      const payload = this.buildPayload()
      this.$emit("input", { ...this.localFilters })
      this.$emit("apply", payload)
    },
    clear() {
      this.localFilters = {
        status: "all",
        agentId: null,
        totalPaidMin: null,
        totalPaidMax: null,
        cityId: null,
        latestPaymentFrom: null,
        latestPaymentTo: null,
        registrationFrom: null,
        registrationTo: null,
        deviceType: null,
      }
      const payload = this.buildPayload()
      this.$emit("input", { ...this.localFilters })
      this.$emit("clear", payload)
    },
  },
}
</script>

<style scoped>
.customer-filter-card {
  margin: 1rem 0;
}
</style>
