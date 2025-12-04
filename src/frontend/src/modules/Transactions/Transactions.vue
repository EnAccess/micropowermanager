<template>
  <div>
    <div>
      <div :class="{ 'box-margin': showBoxes }">
        <md-toolbar class="md-dense">
          <div class="md-toolbar-section-start md-small-size-100">
            <div class="md-layout md-size-40 md-small-size-100">
              <md-field class="period-area">
                <label for="period">
                  {{ $tc("words.period") }}
                </label>
                <md-select
                  v-model="period"
                  name="period"
                  id="period"
                  @md-selected="getPeriod"
                >
                  <md-option value="Yesterday">
                    {{ $tc("words.yesterday") }}
                  </md-option>
                  <md-option value="Same day last week">
                    {{ $tc("phrases.sameDayLastWeek") }}
                  </md-option>
                  <md-option value="Past 7 days">
                    {{ $tc("phrases.lastXDays", 1, { x: 7 }) }}
                  </md-option>
                  <md-option value="Past 30 days">
                    {{ $tc("phrases.lastXDays", 1, { x: 30 }) }}
                  </md-option>
                </md-select>
              </md-field>
            </div>
            <div
              class="md-layout md-gutter md-size-60 md-small-size-100 summary"
              v-if="!showBoxes"
            >
              <div class="md-layout-item">
                <div class="md-layout">
                  <span>
                    {{ analyticsData.current.confirmed }}
                  </span>
                </div>
                <div class="md-layout">
                  <md-icon class="md-primary">check</md-icon>
                </div>
                <div class="md-layout">
                  <small>{{ $tc("words.confirm", 2) }}</small>
                </div>
              </div>
              <div class="md-layout-item">
                <div class="md-layout">
                  <span>
                    {{ analyticsData.current.cancelled }}
                  </span>
                </div>
                <div class="md-layout">
                  <md-icon class="md-accent">cancel</md-icon>
                </div>
                <div class="md-layout">
                  <small>{{ $tc("words.cancel", 2) }}</small>
                </div>
              </div>
              <div class="md-layout-item">
                <div class="md-layout">
                  <span>
                    {{ moneyFormat(analyticsData.current.amount) }}
                  </span>
                </div>
                <div class="md-layout">
                  <md-icon>attach_money</md-icon>
                </div>
                <div class="md-layout">
                  <small>{{ $tc("words.revenue") }}</small>
                </div>
              </div>
            </div>
          </div>
          <div class="md-toolbar-section-end md-small-size-100 summary">
            <md-button
              class="md-dense md-button-icon"
              @click="showBoxes = !showBoxes"
            >
              {{ showBoxes ? $tc("words.collapse") : $tc("words.expand") }}
              <md-icon>
                {{ showBoxes ? "keyboard_arrow_down" : "keyboard_arrow_left" }}
              </md-icon>
            </md-button>
          </div>
        </md-toolbar>
      </div>
      <div class="md-layout md-gutter" v-if="showBoxes">
        <div
          v-if="analyticsData"
          class="md-layout-item md-size-25 md-small-size-50"
        >
          <box
            :box-color="'blue'"
            :center-text="true"
            :header-text="$tc('phrases.incomingTransactions')"
            :sub-text="
              analyticsData.current.total + '/' + analyticsData.past.total
            "
            :box-icon="'add'"
            :additional-text="
              analyticsData.analytics.totalPercentage.percentage +
              '% of ' +
              analyticsPeriods[analyticsPeriod]
            "
          />
        </div>
        <div
          v-if="analyticsData"
          class="md-layout-item md-size-25 md-small-size-50"
        >
          <box
            :box-color="'green'"
            :center-text="true"
            :header-text="$tc('words.confirm', 2)"
            :sub-text="
              analyticsData.current.confirmed +
              '/' +
              analyticsData.past.confirmed
            "
            :box-icon="'check'"
            :additional-text="
              analyticsData.analytics.confirmationPercentage.percentage +
              '% of ' +
              analyticsPeriods[analyticsPeriod]
            "
          />
        </div>
        <div
          v-if="analyticsData"
          class="md-layout-item md-size-25 md-small-size-50"
        >
          <box
            :box-color="'red'"
            :center-text="true"
            :header-text="$tc('words.cancel', 2)"
            :sub-text="
              analyticsData.current.cancelled +
              '/' +
              analyticsData.past.cancelled
            "
            :box-icon="'cancel'"
            :additional-text="
              analyticsData.analytics.cancelationPercentage.percentage +
              '% of ' +
              analyticsPeriods[analyticsPeriod]
            "
          />
        </div>
        <div
          v-if="analyticsData"
          class="md-layout-item md-size-25 md-small-size-50"
        >
          <box
            :box-color="'orange'"
            :center-text="true"
            :header-text="$tc('words.revenue')"
            :sub-text="moneyFormat(analyticsData.current.amount)"
            :box-icon="'attach_money'"
            :additional-text="
              analyticsData.analytics.amountPercentage.percentage +
              '% of ' +
              analyticsPeriods[analyticsPeriod]
            "
          />
        </div>

        <div
          class="md-layout-item md-size-25 md-small-size-50"
          v-if="analyticsData === null && loading === false"
        >
          <h5>{{ $tc("phrases.transactionNotify") }}</h5>
        </div>
      </div>
      <div class="md-layout">
        <div class="transaction-filter" v-if="showFilter">
          <filter-transaction @searchSubmit="filterTransaction" />
        </div>
        <div class="md-layout-item md-size-100">
          <widget
            :id="'transaction-list'"
            :title="$tc('words.transaction', 2)"
            :paginator="transactionService.paginator"
            :search="false"
            :subscriber="subscriber"
            :route_name="'/transactions'"
            :show_per_page="true"
            color="green"
            :button="true"
            :empty-state-create-button="false"
            :button-text="$tc('words.filter')"
            @widgetAction="
              () => {
                showFilter = !showFilter
              }
            "
            button-icon="filter_list"
          >
            <div>
              <div class="export-button-container">
                <md-button
                  class="md-raised md-primary export-csv-button"
                  @click="showExportModal = true"
                >
                  <md-icon>download</md-icon>
                  {{ $tc("phrases.exportTransactions") }}
                </md-button>
              </div>
              <md-table style="width: 100%" md-card>
                <md-table-row>
                  <md-table-head>
                    {{ $tc("words.status") }}
                  </md-table-head>
                  <md-table-head>
                    <md-icon>person</md-icon>
                    {{ $tc("words.service") }}
                  </md-table-head>
                  <md-table-head>
                    <md-icon>phone</md-icon>
                    {{ $tc("words.sender") }}
                  </md-table-head>
                  <md-table-head>
                    <md-icon>money</md-icon>
                    {{ $tc("words.amount") }}
                  </md-table-head>
                  <md-table-head>
                    {{ $tc("words.type") }}
                  </md-table-head>
                  <md-table-head>
                    {{ $tc("words.message") }}
                  </md-table-head>
                  <md-table-head>
                    <md-icon>calendar_today</md-icon>
                    {{ $tc("phrases.sentDate") }}
                  </md-table-head>
                  <md-table-head>
                    <md-icon>calendar_view_day</md-icon>
                    {{ $tc("phrases.processTime") }}
                  </md-table-head>
                </md-table-row>

                <md-table-row
                  v-for="transaction in transactionService.list"
                  :key="transaction.id"
                  style="cursor: pointer"
                  @click="transactionDetail(transaction.id)"
                >
                  <md-table-cell>
                    <md-icon
                      v-if="transaction.status === 1"
                      style="color: green"
                      md-toolt
                    >
                      check_circle_outline
                      <md-tooltip md-direction="right">
                        {{ $tc("words.confirm", 2) }}
                      </md-tooltip>
                    </md-icon>
                    <md-icon
                      v-if="transaction.status === 0"
                      style="color: goldenrod"
                    >
                      contact_support
                      <md-tooltip md-direction="right">
                        {{ $tc("words.process", 3) }}
                      </md-tooltip>
                    </md-icon>
                    <md-icon
                      v-if="transaction.status === -1"
                      style="color: red"
                    >
                      cancel
                      <md-tooltip md-direction="right">
                        {{ $tc("words.reject", 2) }}
                      </md-tooltip>
                    </md-icon>
                  </md-table-cell>

                  <md-table-cell style="text-align: center !important">
                    <img
                      v-if="transaction.service === 'vodacom_transaction'"
                      class="logo"
                      alt="logo"
                      :src="vodacomLogo"
                      style="max-height: 20px"
                    />
                    <img
                      v-if="transaction.service === 'third_party_transaction'"
                      class="logo"
                      alt="logo"
                      :src="thirdPartyLogo"
                      style="max-height: 24px"
                    />
                    <img
                      v-if="transaction.service === 'agent_transaction'"
                      :src="agentIcon"
                      style="max-height: 32px"
                    />
                    <img
                      v-if="transaction.service === 'cash_transaction'"
                      :src="moneyIcon"
                      style="max-height: 32px"
                    />
                    <img
                      v-if="transaction.service === 'wave_money_transaction'"
                      :src="waveMoneyLogo"
                      style="max-height: 34px"
                    />
                    <img
                      v-if="transaction.service === 'swifta_transaction'"
                      :src="swiftaLogo"
                      style="max-height: 20px"
                    />
                    <img
                      v-if="transaction.service === 'wavecom_transaction'"
                      :src="waveComLogo"
                      style="max-height: 32px"
                    />
                    <img
                      v-if="transaction.service === 'paystack_transaction'"
                      :src="paystackLogo"
                      style="max-height: 32px; max-width: 100px"
                    />
                  </md-table-cell>
                  <md-table-cell>
                    {{ transaction.sender }}
                  </md-table-cell>
                  <md-table-cell>
                    {{ moneyFormat(transaction.amount) }}
                  </md-table-cell>
                  <md-table-cell>
                    {{ transaction.type }}
                  </md-table-cell>
                  <md-table-cell>
                    {{ transaction.message }}
                  </md-table-cell>
                  <md-table-cell>
                    <div v-if="transaction != undefined">
                      {{ timeForHuman(transaction.sentDate) }}
                      <small style="margin-left: 0.2rem">
                        ({{ timeForTimeZone(transaction.sentDate) }})
                      </small>
                    </div>
                  </md-table-cell>
                  <md-table-cell>
                    <div v-if="transaction != undefined">
                      {{
                        $tc("phrases.inXSeconds", 1, {
                          x: timeDiffForHuman(
                            transaction.sentDate,
                            transaction.lastUpdate,
                          ),
                        })
                      }}
                    </div>
                  </md-table-cell>
                </md-table-row>
              </md-table>
            </div>
          </widget>
        </div>
      </div>

      <md-dialog :md-active.sync="showExportModal" class="export-dialog">
        <md-dialog-title>
          {{ $tc("phrases.exportTransactions") }}
        </md-dialog-title>

        <md-dialog-content>
          <div class="md-layout md-gutter">
            <div class="md-layout-item md-size-25">
              <md-field>
                <label>{{ $tc("words.deviceType") }}</label>
                <md-select v-model="exportFilters.deviceType">
                  <md-option value="all">{{ $tc("words.all") }}</md-option>
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
            <div class="md-layout-item md-size-25">
              <md-field>
                <label>{{ $tc("words.provider") }}</label>
                <md-select v-model="exportFilters.provider">
                  <md-option value="">{{ $tc("words.all") }}</md-option>
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
            <div class="md-layout-item md-size-25">
              <md-field>
                <label>{{ $tc("words.status") }}</label>
                <md-select v-model="exportFilters.status">
                  <md-option value="">{{ $tc("words.all") }}</md-option>
                  <md-option :value="1">
                    {{ $tc("words.confirm", 2) }}
                  </md-option>
                  <md-option :value="0">
                    {{ $tc("words.process", 3) }}
                  </md-option>
                  <md-option :value="-1">
                    {{ $tc("words.reject", 2) }}
                  </md-option>
                </md-select>
              </md-field>
            </div>
            <div class="md-layout-item md-size-25">
              <md-field>
                <label>{{ $tc("words.format") }}</label>
                <md-select v-model="exportFilters.format">
                  <md-option value="csv">CSV</md-option>
                  <md-option value="xlsx">Excel</md-option>
                </md-select>
              </md-field>
            </div>
          </div>
        </md-dialog-content>

        <md-dialog-actions>
          <md-button @click="showExportModal = false">
            {{ $tc("words.cancel") }}
          </md-button>
          <md-button class="md-primary" @click="exportTransactions">
            {{ $tc("words.export") }}
          </md-button>
        </md-dialog-actions>
      </md-dialog>
    </div>
  </div>
</template>

<script>
import { timing } from "@/mixins/timing"
import { currency } from "@/mixins/currency"
import { EventBus } from "@/shared/eventbus"
import Widget from "@/shared/Widget.vue"
import FilterTransaction from "@/modules/Transactions/FilterTransaction"
import Box from "@/shared/Box"
import { mapGetters } from "vuex"
import { TransactionService } from "@/services/TransactionService"
import { TransactionProviderService } from "@/services/TransactionProviderService"
import { TransactionExportService } from "@/services/TransactionExportService"
import { MainSettingsService } from "@/services/MainSettingsService"

import vodacomLogo from "@/assets/icons/vodacom.png"
import waveMoneyLogo from "@/assets/icons/WaveMoney.png"
import swifta from "@/assets/icons/Swifta.png"
import agentIcon from "@/assets/icons/agent-icon.png"
import moneyIcon from "@/assets/icons/money-icon.png"
import thirdPartyLogo from "@/assets/icons/third_party_transaction_icon.png"
import WaveComLogo from "@/assets/icons/WaveComLogo.png"
import paystackLogo from "@/assets/icons/Paystack.png"
import { notify } from "@/mixins/notify"

export default {
  name: "Transactions.vue",
  mixins: [timing, currency, notify],
  components: { Box, FilterTransaction, Widget },
  data() {
    return {
      transactionService: new TransactionService(),
      transactionProviderService: new TransactionProviderService(),
      transactionExportService: new TransactionExportService(),
      mainSettingsService: new MainSettingsService(),
      period: "Yesterday",
      filter: [],
      loading: false,
      subscriber: "transactionList",
      tab: "all",
      paginator: null,
      analyticsData: null,
      analyticsPeriod: null,
      showFilter: false,
      showBoxes: true,
      showExportModal: false,
      analyticsPeriods: [
        "Yesterday",
        "Same day last week",
        "Past 7 days",
        "Past 30 days",
      ],
      exportFilters: {
        format: "csv",
        deviceType: "all",
        provider: "",
        status: "",
      },
      transactionProviders: [],
      vodacomLogo: vodacomLogo,
      thirdPartyLogo: thirdPartyLogo,
      waveMoneyLogo: waveMoneyLogo,
      agentIcon: agentIcon,
      moneyIcon: moneyIcon,
      swiftaLogo: swifta,
      waveComLogo: WaveComLogo,
      paystackLogo: paystackLogo,
    }
  },
  mounted() {
    this.checkRouteChanges()
    this.loadAnalytics()
    this.getPeriod()
    this.getTransactionProviders()
    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("transactionFilterClosed", this.closeFilter)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
    EventBus.$off("searching", this.searching)
  },
  methods: {
    checkRouteChanges() {
      let isFiltering = false
      let queryParams = this.$route.query
      if (Object.keys(queryParams).length > 0) {
        for (let k of Object.keys(queryParams)) {
          if (k !== "page" && k !== "per_page") {
            isFiltering = true
          }
        }
      }
      if (isFiltering) {
        this.getFilterTransactions(queryParams)
      }
    },
    closeFilter() {
      this.showFilter = false
    },
    filterTransaction(filterData) {
      let data = {}
      for (let i in filterData) {
        if (filterData[i] === null) {
          continue
        }
        data[i] = filterData[i]
      }
      this.filter = data
      const { ...params } = this.$route.query
      for (let k of Object.keys(params)) {
        if (k !== "page" && k !== "per_page") {
          delete params[k]
        }
      }
      for (let [k, v] of Object.entries(data)) {
        params[k] = v
      }
      this.$router.push({ query: Object.assign(params) })
    },
    getFilterTransactions(data) {
      this.filterProgress = true
      this.transactionService.searchAdvanced(data)
    },
    reloadList(sub, data) {
      if (sub !== this.subscriber) return
      this.transactionService.updateList(data)
      EventBus.$emit("dataLoaded")
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.transactionService.list.length,
      )
    },
    transactionDetail(id) {
      this.$router.push({ path: "/transactions/" + id })
    },
    async exportTransactions() {
      try {
        const data = {
          format: this.exportFilters.format,
        }
        if (this.exportFilters.deviceType !== "") {
          data.deviceType = this.exportFilters.deviceType
        }
        if (this.exportFilters.provider !== "") {
          data.provider = this.exportFilters.provider
        }
        if (this.exportFilters.status !== "") {
          data.status = this.exportFilters.status
        }

        const response =
          await this.transactionExportService.exportTransactions(data)

        const blob = new Blob([response.data])
        const downloadUrl = window.URL.createObjectURL(blob)
        const a = document.createElement("a")
        a.href = downloadUrl

        const contentDisposition = response.headers["content-disposition"]
        const filename =
          contentDisposition?.split("filename=")[1]?.replace(/['"]/g, "") ??
          (this.exportFilters.format === "excel" ? "export.xlsx" : "export.csv")

        a.download = filename
        document.body.appendChild(a)
        a.click()
        a.remove()
        window.URL.revokeObjectURL(downloadUrl)
        this.alertNotify("success", "Transactions exported successfully!")
        this.showExportModal = false
      } catch (e) {
        this.alertNotify("error", "Error occurred while exporting transactions")
      }
    },
    async loadAnalytics() {
      this.loading = true
      this.analyticsPeriod =
        this.analyticsPeriod === null ? 0 : this.analyticsPeriod
      try {
        this.analyticsData = await this.transactionService.getAnalytics(
          this.analyticsPeriod,
        )
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    getPeriod(period = "Yesterday") {
      switch (period) {
        case "Yesterday":
          this.analyticsPeriod = 0
          break

        case "Same day last week":
          this.analyticsPeriod = 1
          break

        case "Past 7 days":
          this.analyticsPeriod = 2
          break

        case "Past 30 days":
          this.analyticsPeriod = 3
          break

        default:
          break
      }

      this.loadAnalytics()
    },
    async getTransactionProviders() {
      this.transactionProviders =
        await this.transactionProviderService.getTransactionProviders()
    },
  },
  computed: {
    ...mapGetters({
      deviceTypes: "device/getDeviceTypes",
    }),
  },
  watch: {
    //for query param filtering
    $route() {
      this.checkRouteChanges()
    },
  },
}
</script>
<style scoped>
span {
  text-align: center !important;
  margin-left: auto;
  margin-right: auto;
}

.information {
  font-size: 2.5rem;
  margin: 0.5rem 0;
}

.information.green {
  color: #0dba9a;
}

.information.red {
  color: #ba0f0d;
}

.information > small {
  font-size: 1.5rem;
}

.sub-information > .green {
  color: #61c7b3;
}

.sub-information > .red {
  color: #ba0f0d;
}

.header {
  clear: both;
}

.card-list {
  display: -webkit-inline-box !important;
  width: 100%;
}

.card-list-item {
  width: 25% !important;
}

.card-list-item-content {
  width: 100% !important;
}

.transaction-list-grid {
  padding: 1rem;
}

.transaction-filter {
  min-width: 300px;
  width: 30%;
  z-index: 3;
  right: 0;
  position: absolute;
}

.box-margin {
  margin-bottom: 35px;
}

.period-area {
  width: 30% !important;
  min-width: 300px;
  margin-right: 1vw;
}

@media screen and (max-width: 991px) {
  .summary {
    display: none;
  }
}

.export-button-container {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 1rem;
  padding: 0 1rem;
}

.export-csv-button {
  margin-left: auto;
}

.export-dialog {
  min-width: 600px;
}

.export-dialog .md-dialog-content {
  padding: 20px;
}
</style>
