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
            class="md-layout-item md-xlarge-size-100 md-large-size-100 md-medium-size-100 md-small-size-100 md-xsmall-size-100"
          >
            <md-field>
              <label for="serial_number">Serial Number</label>
              <md-input
                type="text"
                name="serial_number"
                v-model="filter.serial_number"
                v-on:keyup.enter="submitFilter"
              ></md-input>
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
          class="md-raised md-secondary"
          v-if="!loading"
          @click="exportTransactions"
        >
          <md-icon>download</md-icon>
        </md-button>
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
import moment from "moment-timezone"
import store from "@/store/store"
import { TransactionExportService } from "@/services/TransactionExportService"

export default {
  name: "FilterTransaction",
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
        serial_number: null,
        tariff: null,
        provider: null,
        from: null,
        to: null,
        deviceType: null,
      },
      transactionProviders: [],
    }
  },
  created() {
    if (this.$route.query.deviceType) {
      this.selectedDevice = this.$route.query.deviceType
    }
  },
  mounted() {
    this.getTariffs()
    this.getSearch()
    this.getTransactionProviders()
    EventBus.$on("dataLoaded", this.dataLoaded)
  },
  methods: {
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
    async exportTransactions() {
      this.adjustFilter()
      const data = {
        ...this.filter,
        timeZone: moment.tz.guess(),
        currency: store.getters["settings/getMainSettings"].currency,
      }
      const email = this.$store.getters["auth/getAuthenticateUser"].email
      window.open(this.transactionExportService.exportTransactions(email, data))
    },
    getSearch() {
      let search = this.$store.getters.search

      if (Object.keys(search).length) {
        if ("serial_number" in search) {
          this.filter["serial_number"] = search["serial_number"]
        }
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
      if (this.filter.serial_number === "") {
        this.filter.serial_number = null
      }
      if (this.filter.provider === -1 || this.filter.provider === "-1") {
        this.filter.provider = null
      }
      if (this.filter.tariff === "all") {
        this.filter.tariff = null
      }
      if (this.filter.status === "all") {
        this.filter.status = null
      }
      if (this.filterFrom !== null) {
        this.filter.from = this.filterFrom.toString() + " 00:00:00"
      }
      if (this.filterTo !== null) {
        this.filter.to = this.filterTo.toString() + " 23:59:59"
      }
      console.log(this.filter)
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
  },
}
</script>
