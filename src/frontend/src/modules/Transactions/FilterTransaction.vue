<template>
  <div style="margin: 2vh">
    <md-card>
      <md-card-header>
        {{ $tc("words.filter") }}
      </md-card-header>
      <md-card-content>
        <div class="md-layout">
          <div
            class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100 md-xsmall-size-100"
          >
            <md-field>
              <label for="device">Device Type</label>
              <md-select v-model="selectedDevice" name="device" id="device">
                <md-option
                  v-for="device in deviceTypes"
                  :value="device.type"
                  :key="device.type"
                >
                  {{ device.display }}
                </md-option>
              </md-select>
            </md-field>
          </div>
          <div
            v-if="selectedDevice === 'meter'"
            class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100 md-xsmall-size-100"
          >
            <md-field>
              <label for="tariff">Tariff Name</label>
              <md-select
                v-model="selectedTariff"
                name="tariff"
                id="tariff"
                @md-selected="setTariff"
              >
                <md-option
                  v-for="tariff in tariffs"
                  :value="tariff.id"
                  :key="tariff.id"
                >
                  {{ tariff.name }}
                </md-option>
              </md-select>
            </md-field>
          </div>
          <div
            class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100 md-xsmall-size-100"
          >
            <md-field>
              <label for="provider">Transaction Provider</label>
              <md-select
                name="provider"
                id="provider"
                v-model="selectedProvider"
              >
                <md-option
                  v-for="(p, i) in transactionProviders"
                  :key="i"
                  :value="p.value"
                >
                  {{ p.name }}
                </md-option>
              </md-select>
            </md-field>
          </div>
          <div
            class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100 md-xsmall-size-100"
          >
            <md-field>
              <label for="transaction">Status</label>
              <md-select
                v-model="transaction_"
                name="transaction"
                id="transaction"
                @md-selected="seTransaction"
              >
                <md-option value="All">All</md-option>
                <md-option value="Only Approved">
                  {{ $tc("phrases.onlyApproved") }}
                </md-option>
                <md-option value="Only Rejected">
                  {{ $tc("phrases.onlyRejected") }}
                </md-option>
              </md-select>
            </md-field>
          </div>

          <div
            class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100 md-xsmall-size-100"
          >
            <md-datepicker
              md-immediately
              v-model="filterFrom"
              :md-close-on-blur="false"
            >
              <label>{{ $tc("phrases.fromDate") }}</label>
            </md-datepicker>
          </div>

          <div
            class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100 md-xsmall-size-100"
          >
            <md-datepicker
              md-immediately
              v-model="filterTo"
              :md-close-on-blur="false"
            >
              <label>{{ $tc("phrases.toDate") }}</label>
            </md-datepicker>
          </div>
        </div>
      </md-card-content>
      <md-card-actions>
        <md-button
          class="md-raised md-primary"
          v-if="!loading"
          @click="submitFilter"
        >
          {{ $tc("words.search") }}
        </md-button>
        <md-button class="md-raised md-accent" @click="closeFilter">
          {{ $tc("words.close") }}
        </md-button>
      </md-card-actions>
      <md-progress-bar md-mode="indeterminate" v-if="loading" />
    </md-card>
  </div>
</template>

<script>
import { TransactionService } from "@/services/TransactionService"
import { TariffService } from "@/services/TariffService"
import { EventBus } from "@/shared/eventbus"
import { TransactionProviderService } from "@/services/TransactionProviderService"
import { mapGetters } from "vuex"
import store from "@/store/store"
import { TransactionExportService } from "@/services/TransactionExportService"
import { notify } from "@/mixins"
import { MiniGridService } from "@/services/MiniGridService"
import { CityService } from "@/services/CityService"
import { CurrencyListService } from "@/services/CurrencyListService"

export default {
  name: "FilterTransaction",
  mixins: [notify],
  data() {
    return {
      transactionService: new TransactionService(),
      transactionProviderService: new TransactionProviderService(),
      tariffService: new TariffService(),
      transactionExportService: new TransactionExportService(),
      selectedProvider: "-1",
      selectedDevice: "meter",
      tariffs: [],
      selectedTariff: "",
      loading: false,
      provider_: "All",
      transaction_: "All",
      filterFrom: null,
      filterTo: null,
      filter: {
        status: null,
        tariff: null,
        provider: null,
        from: null,
        to: null,
        deviceType: null,
      },
      transactionProviders: [],
      miniGridService: new MiniGridService(),
      cityService: new CityService(),
      currencyListService: new CurrencyListService(),
      exportFilters: {
        format: "csv",
        currency: null,
        timeZone: "UTC",
        deviceType: null,
        provider: null,
        status: null,
      },
    }
  },
  created() {
    this.hydrateFromRoute(this.$route.query)
  },
  mounted() {
    this.getTariffs()
    this.getSearch()
    this.getTransactionProviders()
    EventBus.$on("dataLoaded", this.dataLoaded)
    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("searching", this.searching)
    EventBus.$on("end_searching", this.endSearching)
    this.loadMiniGrids()
    this.loadCities()
    this.loadCurrencyList()
    this.exportFilters.currency =
      store.getters["settings/getMainSettings"].currency
  },
  methods: {
    hydrateFromRoute(query) {
      // Reset to defaults first
      this.selectedProvider = "-1"
      this.selectedDevice = "meter"
      this.selectedTariff = this.tariffs[0]?.id || "all"
      this.transaction_ = "All"
      this.filterFrom = null
      this.filterTo = null
      this.filter = {
        status: null,
        tariff: null,
        provider: null,
        from: null,
        to: null,
        deviceType: null,
      }

      if (query.deviceType) {
        this.selectedDevice = query.deviceType
      }
      this.filter.deviceType = this.selectedDevice

      if (query.provider) {
        this.selectedProvider = query.provider
        this.filter.provider = query.provider
      }

      if (query.status) {
        if (String(query.status) === "1") {
          this.transaction_ = "Only Approved"
          this.filter.status = "1"
        } else if (String(query.status) === "-1") {
          this.transaction_ = "Only Rejected"
          this.filter.status = "-1"
        } else {
          this.transaction_ = "All"
          this.filter.status = null
        }
      }

      if (query.tariff) {
        this.filter.tariff = query.tariff
        this.selectedTariff = query.tariff
      }

      if (query.from) {
        const [fromDateStr] = String(query.from).split(" ")
        this.filterFrom = new Date(fromDateStr)
        this.filter.from = query.from
      }
      if (query.to) {
        const [toDateStr] = String(query.to).split(" ")
        this.filterTo = new Date(toDateStr)
        this.filter.to = query.to
      }
    },
    reloadList() {
      this.loading = false
    },
    searching() {
      this.loading = true
    },
    endSearching() {
      this.loading = false
    },
    async getTariffs() {
      let tariffs = await this.tariffService.getTariffs()
      tariffs.forEach((e) => {
        let tariff = {
          id: e.id,
          name: e.name,
        }
        this.tariffs.push(tariff)
      })
      this.tariffs.unshift({ id: "all", name: "All" })
      this.selectedTariff = this.tariffs[0].id
    },
    async getTransactionProviders() {
      this.transactionProviders = [
        {
          name: "All",
          value: "-1",
        },
        ...(await this.transactionProviderService.getTransactionProviders()),
      ]
    },
    dataLoaded() {
      this.loading = false
      this.closeFilter()
    },
    setTariff(tariff) {
      this.filter.tariff = tariff
    },
    closeFilter() {
      EventBus.$emit("transactionFilterClosed")
    },
    seTransaction(transaction) {
      switch (transaction) {
        case "All":
          this.filter.status = "all"
          break
        case "Only Approved":
          this.filter.status = "1"
          break
        case "Only Rejected":
          this.filter.status = "-1"
          break

        default:
          break
      }
    },
    submitFilter() {
      this.loading = true
      this.adjustFilter()
      this.$emit("searchSubmit", this.filter)
    },
    getSearch() {
      let search = this.$store.getters.search

      if (Object.keys(search).length) {
        if ("from" in search) {
          this.filter["from"] = search["from"]
        }
        if ("to" in search) {
          this.filter["to"] = search["to"]
        }
      }
    },
    adjustFilter() {
      this.filter.provider = this.selectedProvider
      if (this.filter.provider === -1 || this.filter.provider === "-1") {
        this.filter.provider = null
      }
      if (this.filter.tariff === "all") {
        this.filter.tariff = null
      }
      if (this.filter.status === "all") {
        this.filter.status = null
      }
      this.filter.from = null
      this.filter.to = null
      if (this.filterFrom !== null) {
        const fromDate = new Date(this.filterFrom)
        const fromYear = fromDate.getFullYear()
        const fromMonth = String(fromDate.getMonth() + 1).padStart(2, "0")
        const fromDay = String(fromDate.getDate()).padStart(2, "0")
        // Build date range using local calendar date to avoid timezone shifts
        this.filter.from = `${fromYear}-${fromMonth}-${fromDay} 00:00:00`
      }
      if (this.filterTo !== null) {
        const toDate = new Date(this.filterTo)
        const toYear = toDate.getFullYear()
        const toMonth = String(toDate.getMonth() + 1).padStart(2, "0")
        const toDay = String(toDate.getDate()).padStart(2, "0")
        // Build date range using local calendar date to avoid timezone shifts
        this.filter.to = `${toYear}-${toMonth}-${toDay} 23:59:59`
      }
    },
    async loadMiniGrids() {
      try {
        await this.miniGridService.getMiniGrids()
      } catch (error) {
        console.error("Failed to load mini grids:", error)
      }
    },

    async loadCities() {
      try {
        await this.cityService.getCities()
      } catch (error) {
        console.error("Failed to load cities:", error)
      }
    },

    async loadCurrencyList() {
      try {
        await this.currencyListService.getCurrencyList()
      } catch (error) {
        console.error("Failed to load currency list:", error)
      }
    },
  },
  computed: {
    ...mapGetters({
      deviceTypes: "device/getDeviceTypes",
    }),
  },
  watch: {
    selectedDevice(val) {
      this.filter.deviceType = val
    },
    // Keep filter UI in sync with URL when route query changes
    $route() {
      this.hydrateFromRoute(this.$route.query)
    },
  },
}
</script>
