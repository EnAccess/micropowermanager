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
    </div>
    <div class="md-layout-item md-size-60">
      <widget
        :title="'Details of ' + soldAppliance.applianceType.name"
        color="green"
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
              Amount is not bigger than total remaining amount !!!
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
            class="md-layout-item md-layout md-gutter md-size-100"
            style="padding: 2vw"
          >
            <div class="md-layout-item md-size-50">
              <h2>
                <b>{{ $tc("phrases.totalCost") }}:</b>
                {{ moneyFormat(soldAppliance.totalCost) }}
              </h2>
              <h4>
                <b>Down Payment:</b>
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
            <md-table v-if="soldAppliance.rateCount > 0">
              <md-table-toolbar>
                <div class="md-toolbar-section-start">
                  <h1 class="md-title">Payment Plan</h1>
                </div>
                <div class="md-toolbar-section-end">
                  <md-button
                    class="md-primary md-raised md-dense"
                    @click="getPayment = true"
                    :disabled="!soldAppliance.totalRemainingAmount"
                  >
                    <md-icon style="color: white">payments</md-icon>
                    Get Payment
                  </md-button>
                </div>
              </md-table-toolbar>
              <md-table-row>
                <md-table-head>ID</md-table-head>
                <md-table-head>
                  <strong>{{ $tc("words.cost") }}</strong>
                </md-table-head>
                <md-table-head>
                  <strong>
                    {{ $tc("phrases.remainingAmount") }}
                  </strong>
                </md-table-head>
                <md-table-head>
                  <strong>
                    {{ $tc("phrases.dueDate") }}
                  </strong>
                </md-table-head>
                <md-table-head>
                  <strong>Edit Rate</strong>
                </md-table-head>
              </md-table-row>
              <md-table-row
                v-for="(rate, index) in getApplianceRates()"
                :key="rate.id"
              >
                <md-table-cell>
                  {{ index + 1 }}
                  <md-icon v-if="rate.remaining === 0">
                    check
                    <md-tooltip md-direction="top">Paid</md-tooltip>
                  </md-icon>
                </md-table-cell>
                <md-table-cell v-if="editRow === 'rate' + '_' + rate.id">
                  <md-field
                    :class="{
                      'md-invalid': errors.has($tc('words.cost')),
                    }"
                  >
                    <span class="md-prefix">
                      {{ currency }}
                    </span>
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
                  {{ moneyFormat(rate.rate_cost) }}
                </md-table-cell>
                <md-table-cell>
                  {{ moneyFormat(rate.remaining) }}
                </md-table-cell>

                <md-table-cell>
                  {{ formatReadableDate(rate.due_date) }}
                </md-table-cell>

                <!--                                soldAppliance.applianceType.asset_type_id means the appliance type is not a SHS-->
                <div
                  v-if="
                    rate.rate_cost === rate.remaining &&
                    soldAppliance.applianceType.asset_type_id !== 1
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
                      @click="closeEditRateAmount(rate.rate_cost)"
                    >
                      <md-icon style="color: red">cancel</md-icon>
                    </md-button>
                  </md-table-cell>
                  <md-table-cell v-else>
                    <md-button
                      class="md-icon-button"
                      @click="changeRateAmount(rate.id, rate.rate_cost)"
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
              </md-table-row>
            </md-table>
            <div v-else>
              <span class="md-subheader md-layout-item">
                <h1>
                  <md-icon>price_check</md-icon>
                  Fully paid.
                </h1>
              </span>
            </div>
            <md-progress-bar
              v-if="progress"
              md-mode="indeterminate"
            ></md-progress-bar>
          </div>
          <div
            class="md-layout-item md-size-100"
            v-if="soldAppliance.logs.length > 0"
          >
            <md-table>
              <md-table-toolbar>
                <h1 class="md-title">History</h1>
              </md-table-toolbar>
              <md-table-row>
                <md-table-cell>#</md-table-cell>
                <md-table-cell>Log</md-table-cell>
                <md-table-cell>Date</md-table-cell>
                <!--                                <md-table-cell>Initiator</md-table-cell>-->
              </md-table-row>
              <md-table-row
                v-for="(log, index) in soldAppliance.logs"
                :key="log.id"
              >
                <md-table-cell>{{ index + 1 }}</md-table-cell>
                <md-table-cell>{{ log.action }}</md-table-cell>
                <md-table-cell>
                  {{ formatReadableDate(log.created_at) }}
                </md-table-cell>
                <!--                                <md-table-cell>{{ log.owner.name }}</md-table-cell>-->
              </md-table-row>
            </md-table>
          </div>
        </div>
      </widget>
    </div>
  </div>
</template>

<script>
import ConfirmationBox from "@/shared/ConfirmationBox"
import ClientDetailCard from "@/shared/ClientDetailCard"
import SoldAppliancesList from "./SoldAppliancesList"
import { AssetPersonService } from "@/services/AssetPersonService"
import { PersonService } from "@/services/PersonService"
import Widget from "@/shared/Widget.vue"
import { currency, notify } from "@/mixins"
import { AssetRateService } from "@/services/AssetRateService"
import moment from "moment"
import { EventBus } from "@/shared/eventbus"
import { AppliancePaymentService } from "@/services/AppliancePaymentService"

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
      assetRateService: new AssetRateService(),
      assetPersonService: new AssetPersonService(),
      personService: new PersonService(),
      soldAppliance: {
        applianceType: {
          name: "",
        },
        logs: [],
      },
      adminId:
        this.$store.getters["auth/authenticationService"].authenticateUser.id,
      personId: null,
      getPayment: false,
      editRow: null,
      errorLabel: false,
      progress: false,
      updateList: 0,
      tempCost: null,
      soldAppliancesList: [],
      payment: null,
      paymentProgress: false,
      updateDetail: 0,
      subscriber: "sold-appliance-detail",
      currency: this.$store.getters["settings/getMainSettings"].currency,
    }
  },
  watch: {
    $route() {
      this.selectedApplianceId = this.$route.params.id
      this.getSoldApplianceDetail()
    },
  },
  created() {
    this.selectedApplianceId = this.$route.params.id
    this.getSoldApplianceDetail().then((personId) => {
      this.getPersonSoldAppliances(personId)
    })
  },
  methods: {
    getApplianceRates() {
      if (this.soldAppliance.downPayment > 0) {
        return this.soldAppliance.rates.slice(1)
      } else {
        return this.soldAppliance.rates
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
          await this.assetRateService.editAssetRate(
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
        }
      }
    },
    async getSoldApplianceDetail() {
      try {
        this.soldAppliance = await this.assetPersonService.show(
          this.selectedApplianceId,
        )
        this.personId = this.soldAppliance.personId
        this.updateDetail++
        await this.getPersonSoldAppliances()
        EventBus.$emit(
          "widgetContentLoaded",
          this.subscriber,
          Object.keys(this.soldAppliance),
        )
        return this.personId
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getPersonSoldAppliances() {
      try {
        this.soldAppliancesList = await this.assetPersonService.getPersonAssets(
          this.personId,
        )
        this.updateList++
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getAppliancePayment() {
      const validator = await this.$validator.validateAll()
      if (validator) {
        if (this.checkPaymentForTotalRemaining()) {
          return
        }
        this.paymentProgress = true
        try {
          const rates = this.getApplianceRates()

          if (rates.length) {
            const installmentCost = rates[1].rate_cost
            if (this.payment < installmentCost) {
              this.alertNotify(
                "info",
                this.$tc("messages.paymentAmountCannotBeLess", {
                  amount: installmentCost,
                }),
              )
              this.paymentProgress = false
              return
            }
          }

          const payment = {
            personId: this.personId,
            adminId: this.adminId,
            rates: this.soldAppliance.rates,
            amount: this.payment,
          }

          await this.appliancePayment.getPaymentForAppliance(
            this.selectedApplianceId,
            payment,
          )
          this.alertNotify(
            "success",
            this.payment + " " + this.currency + " of payment is made.",
          )
          this.payment = null
          this.getPayment = false
          this.paymentProgress = false
          await this.getSoldApplianceDetail()
        } catch (e) {
          this.paymentProgress = false
          this.alertNotify("error", e.message)
        }
      }
    },
    checkPaymentForTotalRemaining() {
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

<style scoped>
.due-date-row {
  background-color: #a1887f;
}
</style>
