<template>
  <div class="md-layout md-gutter">
    <div class="md-layout-item md-size-40">
      <client-detail-card
        :person-id="personId"
        :show-customer-information="false"
        v-if="personId"
      />
      <sold-appliances-list
        :sold-appliances-list="soldAppliancesList"
        :personId="personId"
        :key="updateList"
        v-if="personId"
      />

      <widget
        :title="deviceInfoTitle"
        color="primary"
        id="device-info"
        style="margin-top: 2rem"
      >
        <md-list class="md-double-line" v-if="soldAppliance.device">
          <md-list-item>
            <div class="md-list-item-text">
              <span>{{ $tc("phrases.serialNumber") }}</span>
              <span>{{ soldAppliance.device.device_serial || "N/A" }}</span>
            </div>
          </md-list-item>
          <md-divider></md-divider>
          <md-list-item>
            <div class="md-list-item-text">
              <span>{{ $tc("words.deviceType") }}</span>
              <span>{{ soldAppliance.device.device_type || "N/A" }}</span>
            </div>
          </md-list-item>
          <md-divider></md-divider>
          <md-list-item>
            <div class="md-list-item-text">
              <span>{{ $tc("words.manufacturer") }}</span>
              <span>{{ detailedDeviceInfo?.manufacturer?.name || "N/A" }}</span>
            </div>
          </md-list-item>
          <md-divider></md-divider>
          <md-list-item>
            <div class="md-list-item-text">
              <span>{{ $tc("words.appliance") }}</span>
              <span>
                {{
                  detailedDeviceInfo?.appliance?.name ||
                  soldAppliance.applianceType?.name ||
                  "N/A"
                }}
              </span>
            </div>
          </md-list-item>
        </md-list>
        <div v-else style="padding: 2rem; text-align: center">
          <p>Device information not available</p>
        </div>
      </widget>
    </div>
    <div class="md-layout-item md-size-60">
      <widget
        :title="'Details of ' + soldAppliance.applianceType.name"
        color="primary"
        :key="updateDetail"
        :subscriber="subscriber"
      >
        <confirmation-box
          :title="$tc('phrases.editRate')"
          @confirmed="editRate"
        ></confirmation-box>
        <md-dialog :md-active.sync="getPayment">
          <md-dialog-title>How Much Do You Want to Pay?</md-dialog-title>
          <div style="padding: 2vh">
            <md-field
              :class="{
                'md-invalid': errors.has($tc('words.amount')),
              }"
            >
              <label for="amount">Amount</label>
              <span class="md-prefix">{{ currency }}</span>
              <md-input
                type="number"
                v-model="payment"
                :id="$tc('words.amount')"
                :name="$tc('words.amount')"
                v-validate="'required|numeric|min_value:1'"
                @change="checkPaymentForTotalRemaining()"
              />
              <span class="md-error">
                {{ errors.first($tc("words.amount")) }}
              </span>
            </md-field>
            <md-content class="md-accent" v-if="errorLabel">
              {{
                isEnergyService
                  ? "Payment Amount can not be less than Minimum Payable Amount"
                  : "Payment Amount can not bigger than Total Remaining Amount !!!"
              }}
            </md-content>
          </div>
          <md-progress-bar
            v-if="paymentProgress"
            md-mode="indeterminate"
          ></md-progress-bar>
          <md-dialog-actions>
            <md-button
              class="md-accent md-dense md-raised"
              @click="closeGetPayment()"
            >
              {{ $tc("words.cancel") }}
            </md-button>
            <md-button
              class="md-primary md-dense md-raised"
              @click="getAppliancePayment()"
              :disabled="paymentProgress"
            >
              {{ $tc("words.save") }}
            </md-button>
          </md-dialog-actions>
        </md-dialog>

        <div class="md-layout md-gutter dialog-place">
          <div
            v-if="isEnergyService"
            class="md-layout-item md-layout md-gutter md-size-100"
            style="padding: 2vw"
          >
            <div class="md-layout-item md-size-50">
              <h2>
                <b>{{ $tc("phrases.paymentType") }}:</b>
                Energy as a Service
              </h2>
              <h4>
                <b>{{ $tc("phrases.pricePerDay") }}:</b>
                {{ moneyFormat(soldAppliance.pricePerDay) }}
              </h4>
              <h4>
                <b>{{ $tc("phrases.downPayment") }}:</b>
                {{ moneyFormat(soldAppliance.downPayment) }}
              </h4>
              <h4>
                <b>{{ $tc("phrases.minimumPayableAmount", 0) }}:</b>
                {{
                  soldAppliance.minimumPayableAmount
                    ? moneyFormat(soldAppliance.minimumPayableAmount)
                    : "N/A"
                }}
              </h4>
            </div>
            <div class="md-layout-item md-size-50">
              <h3>
                <b>{{ $tc("phrases.soldDate") }}:</b>
                {{ formatReadableDate(soldAppliance.createdAt) }}
              </h3>
              <h3>
                <b>Total Payments:</b>
                {{ moneyFormat(soldAppliance.totalPayments) }}
              </h3>
              <h3>
                <b>{{ $tc("phrases.lastPaidDate") }}:</b>
                {{ lastPaidDate }}
              </h3>
            </div>
          </div>
          <div
            v-else
            class="md-layout-item md-layout md-gutter md-size-100"
            style="padding: 2vw"
          >
            <div class="md-layout-item md-size-50">
              <h2>
                <b>{{ $tc("phrases.totalCost") }}:</b>
                {{ moneyFormat(soldAppliance.totalCost) }}
              </h2>
              <h4>
                <b>{{ $tc("phrases.downPayment") }}:</b>
                {{ moneyFormat(soldAppliance.downPayment) }}
              </h4>
              <h4>
                <b>Total Payments :</b>
                {{ moneyFormat(soldAppliance.totalPayments) }}
              </h4>
              <h4>
                <b>Total Remaining Amount:</b>
                {{ moneyFormat(soldAppliance.totalRemainingAmount) }}
              </h4>
            </div>
            <div class="md-layout-item md-size-50">
              <h3>
                <b>{{ $tc("phrases.soldDate") }}:</b>
                {{ formatReadableDate(soldAppliance.createdAt) }}
              </h3>
              <h3>
                <b>{{ $tc("phrases.ratesCount") }}:</b>
                {{ soldAppliance.rateCount }}
              </h3>
            </div>
          </div>
          <div class="md-layout-item md-size-100">
            <widget
              :title="isEnergyService ? 'Payment History' : 'Payment Plan'"
              color="primary"
              :paginator="applianceRateService.paginator"
              :subscriber="ratesSubscriber"
            >
              <md-table
                v-if="soldAppliance.rates && soldAppliance.rates.length > 0"
              >
                <md-table-toolbar>
                  <div class="md-toolbar-section-end" style="margin-left: auto">
                    <md-button
                      class="md-primary md-raised md-dense"
                      @click="getPayment = true"
                      :disabled="
                        !isEnergyService && !soldAppliance.totalRemainingAmount
                      "
                    >
                      <md-icon style="color: white">payments</md-icon>
                      Get Payment
                    </md-button>
                  </div>
                </md-table-toolbar>
                <md-table-row>
                  <md-table-head>ID</md-table-head>
                  <md-table-head>
                    <strong>{{ $tc("words.amount") }}</strong>
                  </md-table-head>
                  <md-table-head v-if="!isEnergyService">
                    <strong>{{ $tc("phrases.remainingAmount") }}</strong>
                  </md-table-head>
                  <md-table-head>
                    <strong>{{ $tc("words.date") }}</strong>
                  </md-table-head>
                  <md-table-head v-if="!isEnergyService">
                    <strong>Edit Rate</strong>
                  </md-table-head>
                </md-table-row>
                <md-table-row
                  v-for="(rate, index) in soldAppliance.rates"
                  :key="rate.id"
                >
                  <md-table-cell>
                    {{ (applianceRateService.paginator.from || 0) + index }}
                    <md-icon v-if="rate.remaining === 0">
                      check
                      <md-tooltip md-direction="top">Paid</md-tooltip>
                    </md-icon>
                  </md-table-cell>
                  <md-table-cell v-if="editRow === 'rate' + '_' + rate.id">
                    <md-field
                      :class="{ 'md-invalid': errors.has($tc('words.cost')) }"
                    >
                      <span class="md-prefix">{{ currency }}</span>
                      <md-input
                        :id="$tc('words.cost')"
                        :name="$tc('words.cost')"
                        v-model="tempCost"
                        v-validate="'required|numeric|min_value:0'"
                        type="number"
                      />
                      <span class="md-error">
                        {{ errors.first($tc("words.cost")) }}
                      </span>
                    </md-field>
                  </md-table-cell>
                  <md-table-cell v-else>
                    {{ moneyFormat(rate.rateCost || rate.rate_cost) }}
                  </md-table-cell>
                  <md-table-cell v-if="!isEnergyService">
                    {{ moneyFormat(rate.remaining) }}
                  </md-table-cell>
                  <md-table-cell>
                    {{ formatReadableDate(rate.dueDate || rate.due_date) }}
                  </md-table-cell>
                  <template v-if="!isEnergyService">
                    <div
                      v-if="
                        (rate.rateCost || rate.rate_cost) === rate.remaining &&
                        soldAppliance.applianceType.appliance_type_id !== 1
                      "
                    >
                      <md-table-cell v-if="editRow === 'rate' + '_' + rate.id">
                        <md-button
                          class="md-icon-button"
                          @click="showConfirm(rate)"
                        >
                          <md-icon style="color: green">save</md-icon>
                        </md-button>
                        <md-button
                          class="md-icon-button"
                          @click="
                            closeEditRateAmount(rate.rateCost || rate.rate_cost)
                          "
                        >
                          <md-icon style="color: red">cancel</md-icon>
                        </md-button>
                      </md-table-cell>
                      <md-table-cell v-else>
                        <md-button
                          class="md-icon-button"
                          @click="
                            changeRateAmount(
                              rate.id,
                              rate.rateCost || rate.rate_cost,
                            )
                          "
                        >
                          <md-icon>edit</md-icon>
                        </md-button>
                      </md-table-cell>
                    </div>
                    <div v-else>
                      <md-table-cell>
                        <md-button class="md-icon-button" disabled="">
                          <md-icon>edit_off</md-icon>
                        </md-button>
                      </md-table-cell>
                    </div>
                  </template>
                </md-table-row>
              </md-table>
            </widget>
          </div>
          <div class="md-layout-item md-size-100">
            <widget
              title="History"
              color="primary"
              :paginator="applianceLogService.paginator"
              :subscriber="logsSubscriber"
            >
              <md-table
                v-if="soldAppliance.logs && soldAppliance.logs.length > 0"
              >
                <md-table-row>
                  <md-table-cell>#</md-table-cell>
                  <md-table-cell>Log</md-table-cell>
                  <md-table-cell>Date</md-table-cell>
                </md-table-row>
                <md-table-row
                  v-for="(log, index) in soldAppliance.logs"
                  :key="log.id"
                >
                  <md-table-cell>
                    {{ (applianceLogService.paginator.from || 0) + index }}
                  </md-table-cell>
                  <md-table-cell>{{ log.action }}</md-table-cell>
                  <md-table-cell>
                    {{ formatReadableDate(log.createdAt || log.created_at) }}
                  </md-table-cell>
                </md-table-row>
              </md-table>
            </widget>
          </div>
        </div>
      </widget>
    </div>
  </div>
</template>

<script>
import moment from "moment"

import SoldAppliancesList from "./SoldAppliancesList.vue"

import { ErrorHandler } from "@/Helpers/ErrorHandler.js"
import { currency } from "@/mixins/currency.js"
import { notify } from "@/mixins/notify.js"
import { ApplianceLogService } from "@/services/ApplianceLogService.js"
import { AppliancePaymentService } from "@/services/AppliancePaymentService.js"
import { AppliancePersonService } from "@/services/AppliancePersonService.js"
import { ApplianceRateService } from "@/services/ApplianceRateService.js"
import { PersonService } from "@/services/PersonService.js"
import ClientDetailCard from "@/shared/ClientDetailCard.vue"
import ConfirmationBox from "@/shared/ConfirmationBox.vue"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "SoldApplianceDetail",
  components: {
    Widget,
    SoldAppliancesList,
    ClientDetailCard,
    ConfirmationBox,
  },
  mixins: [currency, notify],
  data() {
    return {
      appliancePayment: new AppliancePaymentService(),
      applianceRateService: null,
      applianceLogService: null,
      appliancePersonService: new AppliancePersonService(),
      personService: new PersonService(),
      soldAppliance: {
        applianceType: {
          name: "",
        },
        logs: [],
        rates: [],
        device: null,
      },
      adminId:
        this.$store.getters["auth/authenticationService"].authenticateUser.id,
      personId: null,
      getPayment: false,
      errorLabel: false,
      progress: false,
      updateList: 0,
      soldAppliancesList: [],
      payment: null,
      paymentProgress: false,
      updateDetail: 0,
      subscriber: "sold-appliance-detail",
      ratesSubscriber: "sold-appliance-rates",
      logsSubscriber: "sold-appliance-logs",
      currency: this.$store.getters["settings/getMainSettings"].currency,
      detailedDeviceInfo: null,
      editRow: null,
      tempCost: null,
    }
  },
  computed: {
    isEnergyService() {
      return this.soldAppliance.paymentType === "energy_service"
    },
    lastPaidDate() {
      if (!this.soldAppliance.rates || !this.soldAppliance.rates.length) {
        return "N/A"
      }
      const paidRates = this.soldAppliance.rates.filter(
        (r) => r.remaining === 0,
      )
      if (!paidRates.length) return "N/A"
      const latest = paidRates.reduce((a, b) => {
        const dateA = new Date(a.due_date || a.dueDate)
        const dateB = new Date(b.due_date || b.dueDate)
        return dateA > dateB ? a : b
      })
      return this.formatReadableDate(latest.due_date || latest.dueDate)
    },
    deviceInfoTitle() {
      if (this.soldAppliance.device?.device_type) {
        const deviceType = this.soldAppliance.device.device_type
        return (
          deviceType.charAt(0).toUpperCase() +
          deviceType.slice(1).replace(/_/g, " ")
        )
      }
      return "Device Information"
    },
  },
  watch: {
    $route() {
      this.selectedApplianceId = this.$route.params.id
      this.applianceRateService = new ApplianceRateService(
        this.selectedApplianceId,
      )
      this.applianceLogService = new ApplianceLogService(
        this.selectedApplianceId,
      )
      this.getSoldApplianceDetail()
    },
  },
  created() {
    this.selectedApplianceId = this.$route.params.id
    this.applianceRateService = new ApplianceRateService(
      this.selectedApplianceId,
    )
    this.applianceLogService = new ApplianceLogService(this.selectedApplianceId)
    this.getSoldApplianceDetail()
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadRatesOrLogs)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadRatesOrLogs)
  },
  methods: {
    reloadRates(data) {
      this.soldAppliance.rates = this.applianceRateService.updateRatesList(data)

      EventBus.$emit(
        "widgetContentLoaded",
        this.ratesSubscriber,
        this.soldAppliance.rates.length,
      )
    },

    reloadLogs(data) {
      this.soldAppliance.logs = this.applianceLogService.updateLogsList(data)

      EventBus.$emit(
        "widgetContentLoaded",
        this.logsSubscriber,
        this.soldAppliance.logs.length,
      )
    },

    reloadRatesOrLogs(subscriber, data) {
      if (subscriber === this.ratesSubscriber) {
        this.reloadRates(data)
        return
      }

      if (subscriber === this.logsSubscriber) {
        this.reloadLogs(data)
      }
    },
    showConfirm(data) {
      data.tempCost = parseInt(this.tempCost)
      EventBus.$emit("show.confirm", data)
    },
    formatReadableDate(date) {
      return moment(date).format("LL")
    },
    closeEditRateAmount(cost) {
      this.editRow = null
      this.tempCost = cost
    },
    changeRateAmount(id, cost) {
      this.tempCost = cost
      this.editRow = "rate_" + id
    },
    closeGetPayment() {
      this.getPayment = false
      this.payment = null
      this.errorLabel = false
    },
    async editRate(data) {
      this.progress = true
      let validator = await this.$validator.validateAll()
      if (validator) {
        try {
          await this.applianceRateService.editApplianceRate(
            data,
            this.adminId,
            this.personId,
          )
          this.editRow = null
          this.alertNotify("success", this.$tc("phrases.ratesCount", 2))
          this.progress = false
          await this.getSoldApplianceDetail()
        } catch (e) {
          this.alertNotify("error", e.message)
          this.progress = false
        }
      }
    },
    async getSoldApplianceDetail() {
      this.progress = true
      try {
        this.soldAppliance = await this.appliancePersonService.show(
          this.selectedApplianceId,
        )
        this.personId = this.soldAppliance.personId
        this.updateDetail++

        if (
          this.soldAppliance.device?.device_type &&
          this.soldAppliance.device?.device_serial
        ) {
          await this.fetchDetailedDeviceInfo()
        }

        await this.getPersonSoldAppliances()

        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          Object.keys(this.soldAppliance),
        )

        EventBus.$emit(
          "widgetContentLoaded",
          this.logsSubscriber,
          this.soldAppliance.logs.length,
        )
        return this.personId
      } catch (e) {
        this.alertNotify("error", e.message)
      } finally {
        this.progress = false
      }
    },
    async fetchDetailedDeviceInfo() {
      try {
        const { device_type, device_id, device_serial } =
          this.soldAppliance.device

        if (device_type === "solar_home_system") {
          const solarHomeSystemService = new (
            await import("@/services/SolarHomeSystemService.js")
          ).SolarHomeSystemService()

          this.detailedDeviceInfo =
            await solarHomeSystemService.getSolarHomeSystem(device_id)
        } else if (device_type === "meter") {
          const meterService = new (
            await import("@/services/MeterService.js")
          ).MeterService()
          this.detailedDeviceInfo =
            await meterService.getMeterBySerialNumber(device_serial)
        }
      } catch (e) {
        console.error("Error fetching detailed device info:", e)
      }
    },
    async getPersonSoldAppliances() {
      try {
        this.soldAppliancesList =
          await this.appliancePersonService.getPersonAppliances(this.personId)
        this.updateList++
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getAppliancePayment() {
      const validator = await this.$validator.validateAll()
      if (validator) {
        this.paymentProgress = true
        try {
          const payment = {
            personId: this.personId,
            adminId: this.adminId,
            rates: this.soldAppliance.rates,
            amount: this.payment,
          }

          const result = await this.appliancePayment.getPaymentForAppliance(
            this.selectedApplianceId,
            payment,
          )

          if (result instanceof ErrorHandler) {
            throw result
          }

          // Check if transaction_id is returned (async processing)
          if (result.transaction_id) {
            // Poll for payment processing status
            try {
              await this.appliancePayment.pollPaymentStatus(
                result.transaction_id,
                {
                  maxAttempts: 20,
                  interval: 1000,
                },
              )
            } catch (pollError) {
              // If polling fails but payment was initiated, show a warning
              this.alertNotify(
                "warning",
                "Payment initiated but processing status could not be verified. Please refresh to check status.",
              )
              this.payment = null
              this.getPayment = false
              this.paymentProgress = false
              await this.getSoldApplianceDetail()
              EventBus.$emit("reloadWidget", this.ratesSubscriber)
              EventBus.$emit("reloadWidget", this.logsSubscriber)
              return
            }
          }

          this.alertNotify(
            "success",
            this.payment + " " + this.currency + " of payment is made.",
          )
          this.payment = null
          this.getPayment = false
          this.paymentProgress = false
          await this.getSoldApplianceDetail()
          EventBus.$emit("reloadWidget", this.ratesSubscriber)
          EventBus.$emit("reloadWidget", this.logsSubscriber)
        } catch (e) {
          this.paymentProgress = false
          const errorMessage =
            e instanceof ErrorHandler
              ? e.message
              : e.message || "Payment failed"
          this.alertNotify("error", errorMessage)
        }
      }
    },
    checkPaymentForTotalRemaining() {
      if (this.isEnergyService) {
        const min = this.soldAppliance.minimumPayableAmount || 0
        if (min > 0 && this.payment < min) {
          this.errorLabel = true
          return true
        }
        this.errorLabel = false
        return false
      }
      if (this.payment > this.soldAppliance.totalRemainingAmount) {
        this.errorLabel = true
        return true
      } else {
        this.errorLabel = false
        return false
      }
    },
  },
}
</script>

<style scoped lang="scss">
.due-date-row {
  background-color: #a1887f;
}
</style>
