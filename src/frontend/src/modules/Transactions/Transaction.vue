<template>
  <section id="widget-grid" v-if="transaction">
    <div class="row">
      <div class="md-layout md-gutter">
        <div
          class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100 md-xsmall-size-100"
        >
          <div class="transaction-detail-card">
            <widget
              :title="$tc('phrases.providerSpecificInformation')"
              :show-spinner="false"
              color="primary"
            >
              <md-card>
                <md-card-content>
                  <component :is="providerDetail" :ot="ot" />
                </md-card-content>
              </md-card>
            </widget>
          </div>
        </div>

        <div
          class="md-layout-item md-xlarge-size-50 md-large-size-50 md-medium-size-50 md-small-size-100 md-xsmall-size-100"
        >
          <div class="transaction-detail-card">
            <widget :title="$tc('words.detail', 2)" :show-spinner="false">
              <md-card>
                <md-card-content>
                  <div class="md-layout">
                    <div class="md-layout-item md-subheader">
                      {{ $tc("words.sender") }}
                    </div>
                    <div class="md-layout-item md-subheader n-font">
                      {{ transaction.sender }}
                    </div>
                  </div>
                  <hr class="hr-d" />
                  <div class="md-layout">
                    <div class="md-layout-item md-subheader">
                      {{ $tc("words.amount") }}
                    </div>
                    <div class="md-layout-item md-subheader n-font">
                      {{ moneyFormat(transaction.amount) }}
                    </div>
                  </div>
                  <hr class="hr-d" />
                  <div class="md-layout">
                    <div class="md-layout-item md-subheader">
                      {{ $tc("phrases.paymentType") }}
                    </div>
                    <div class="md-layout-item md-subheader n-font">
                      <span>{{ transactionTypeLabel }}</span>
                      <div style="margin-left: 0.2em">
                        <small
                          v-if="
                            transaction.type === 'energy' && transaction.token
                          "
                        >
                          ({{ readable(transaction.token.token_amount) }}kWh)
                        </small>
                        <small
                          v-else-if="
                            [
                              'deferred_payment',
                              'eaas_rate',
                              'down_payment',
                            ].includes(transaction.type) && transaction.token
                          "
                        >
                          ({{ readable(transaction.token.token_amount) }} day's)
                        </small>
                      </div>
                    </div>
                  </div>
                  <hr class="hr-d" />
                  <div class="md-layout">
                    <div class="md-layout-item md-subheader">
                      {{ $tc("words.deviceType") }}
                    </div>
                    <div class="md-layout-item md-subheader n-font">
                      {{ deviceType }}
                    </div>
                  </div>
                  <hr class="hr-d" />
                  <div class="md-layout">
                    <div class="md-layout-item md-subheader">
                      {{
                        transaction.device
                          ? $tc("words.device")
                          : $tc("words.appliance")
                      }}
                    </div>
                    <div
                      class="md-layout-item md-subheader n-font"
                      v-if="
                        transaction.device &&
                        transaction.device.device_type === 'meter'
                      "
                    >
                      <router-link
                        :to="{
                          path: '/meters/' + transaction.message,
                        }"
                        class="nav-link"
                      >
                        {{ transaction.message }}
                      </router-link>
                    </div>
                    <div
                      class="md-layout-item md-subheader n-font"
                      v-else-if="
                        transaction.device &&
                        transaction.device.device_type === 'solar_home_system'
                      "
                    >
                      <router-link
                        :to="{
                          path:
                            '/solar-home-systems/' +
                            transaction.device.device_id,
                        }"
                        class="nav-link"
                      >
                        {{ transaction.message }}
                      </router-link>
                    </div>
                    <div
                      class="md-layout-item md-subheader n-font"
                      v-else-if="
                        transaction.appliance && transaction.appliance.id
                      "
                    >
                      <router-link
                        :to="{
                          path:
                            '/sold-appliance-detail/' +
                            transaction.appliance.id,
                        }"
                        class="nav-link"
                      >
                        {{ deviceDisplay }}
                      </router-link>
                    </div>
                    <div
                      class="md-layout-item md-subheader n-font"
                      v-else-if="
                        isApplianceTransaction && applianceIdFromMessage
                      "
                    >
                      <router-link
                        :to="{
                          path:
                            '/sold-appliance-detail/' + applianceIdFromMessage,
                        }"
                        class="nav-link"
                      >
                        {{ deviceDisplay }}
                      </router-link>
                    </div>
                    <div class="md-layout-item md-subheader n-font" v-else>
                      {{ deviceDisplay }}
                    </div>
                  </div>
                  <hr class="hr-d" />
                  <div class="md-layout">
                    <div class="md-layout-item md-subheader">
                      {{ $tc("words.customer") }}
                    </div>
                    <div
                      class="md-layout-item md-subheader n-font"
                      v-if="personId"
                    >
                      <router-link
                        :to="{
                          path: '/people/' + personId,
                        }"
                        class="nav-link"
                      >
                        {{ personName }}
                      </router-link>
                    </div>
                    <div class="md-layout-item md-subheader n-font" v-else>
                      {{ transaction.payment_histories[0].personName }}
                    </div>
                  </div>
                  <hr class="hr-d" />
                  <div class="md-layout">
                    <div class="md-layout-item md-subheader">
                      {{ $tc("words.date") }}
                    </div>
                    <div class="md-layout-item md-subheader n-font">
                      {{ timeForHuman(transaction.created_at) }}
                      <small style="margin-left: 0.2rem">
                        ({{ timeForTimeZone(transaction.created_at) }})
                      </small>
                    </div>
                  </div>
                </md-card-content>
              </md-card>
            </widget>
          </div>
        </div>
      </div>
      <div class="md-layout md-gutter">
        <div
          class="md-layout-item md-size-50 md-small-size-100"
          v-if="isAdHoc && transaction.token"
        >
          <div class="transaction-detail-card">
            <widget
              :title="$tc('phrases.generatedToken')"
              :show-spinner="false"
              color="primary"
            >
              <md-card>
                <md-card-content>
                  <div class="md-layout">
                    <div class="md-layout-item md-subheader">
                      {{ $tc("words.token") }}
                    </div>
                    <div
                      class="md-layout-item md-subheader n-font issued-token"
                    >
                      {{ formatToken(transaction.token.token) }}
                    </div>
                  </div>
                  <hr class="hr-d" />
                  <div class="md-layout">
                    <div class="md-layout-item md-subheader">
                      {{ $tc("phrases.issuedCredit") }}
                    </div>
                    <div class="md-layout-item md-subheader n-font">
                      {{ readable(transaction.token.token_amount) }}
                      {{ unitLabel(transaction.token.token_unit) }}
                    </div>
                  </div>
                  <hr class="hr-d" />
                  <div class="md-layout">
                    <div class="md-layout-item md-subheader">
                      {{ $tc("words.date") }}
                    </div>
                    <div class="md-layout-item md-subheader n-font">
                      {{ timeForHuman(transaction.token.created_at) }}
                      <small style="margin-left: 0.2rem">
                        ({{ timeForTimeZone(transaction.token.created_at) }})
                      </small>
                    </div>
                  </div>
                </md-card-content>
              </md-card>
            </widget>
          </div>
        </div>
        <div
          class="md-layout-item md-size-50 md-small-size-100"
          v-if="!isAdHoc || showConflicts"
        >
          <div class="transaction-detail-card">
            <widget
              title="Transaction Processing"
              :show-spinner="false"
              color="primary"
            >
              <md-card>
                <md-card-content v-if="ot.status === 1 && hasPaymentHistory">
                  <div class="md-layout md-gutter md-size-100">
                    <div
                      class="md-layout-item md-size-55"
                      style="margin: auto"
                    >
                      <payment-history-chart
                        :paymentdata="transaction.payment_histories"
                      />
                    </div>
                    <div
                      class="md-layout-item md-size-45"
                      style="max-height: 320px; overflow-y: scroll"
                    >
                      <md-table>
                        <md-table-row>
                          <md-table-head>
                            {{ $tc("phrases.paidFor") }}
                          </md-table-head>
                          <md-table-head>
                            {{ $tc("words.amount") }}
                          </md-table-head>
                        </md-table-row>
                        <md-table-row
                          v-for="(p, i) in transaction.payment_histories"
                          :key="i"
                        >
                          <md-table-cell>
                            <p>
                              {{ formatPaymentType(p.payment_type) }}
                            </p>
                          </md-table-cell>
                          <md-table-cell>
                            {{ moneyFormat(p.amount) }}
                          </md-table-cell>
                        </md-table-row>
                      </md-table>
                    </div>
                  </div>
                </md-card-content>
                <md-card-content
                  v-else-if="
                    transaction.original_transaction_type ===
                    'third_party_transaction'
                  "
                >
                  <div class="md-layout md-gutter md-size-100">
                    <ul style="margin: auto">
                      <li>
                        {{ $tc("phrases.untraceableTransaction") }}
                      </li>
                    </ul>
                  </div>
                </md-card-content>
                <md-card-content v-if="showConflicts">
                  <md-list>
                    <md-subheader
                      :class="
                        isCancelled
                          ? 'conflict-cancelled'
                          : 'conflict-attention'
                      "
                    >
                      {{ conflictHeadline }}
                    </md-subheader>

                    <md-list-item
                      :key="conflict.id"
                      v-for="conflict in conflicts"
                    >
                      <span class="conflict-state">
                        {{ conflict.state }}
                      </span>
                    </md-list-item>
                  </md-list>
                </md-card-content>
              </md-card>
            </widget>
          </div>
        </div>
        <div
          class="md-layout-item md-size-50 md-small-size-100"
          v-if="transaction.sms"
        >
          <div class="transaction-detail-card">
            <widget
              :title="$tc('phrases.outgoingSms')"
              :show-spinner="false"
              v-show="
                transaction.original_transaction_type !== 'agent_transaction' &&
                transaction.original_transaction_type !==
                  'third_party_transaction'
              "
              color="secondary"
            >
              <md-card>
                <md-card-content>
                  <div class="md-layout md-gutter md-size-100">
                    <div class="md-layout-item md-subheader md-size-20">
                      {{ $tc("words.to") }}
                    </div>
                    <div class="md-layout-item md-subheader md-size-80">
                      {{ transaction.sms.receiver }}
                    </div>
                  </div>
                  <div class="md-layout md-gutter md-size-100">
                    <div class="md-layout-item md-subheader md-size-20">
                      {{ $tc("words.body") }}
                    </div>
                    <div
                      class="md-layout-item md-subheader md-size-75 message-box"
                    >
                      {{ transaction.sms.body }}
                    </div>
                  </div>
                  <div
                    class="md-layout md-gutter md-size-100"
                    v-if="smsDeliveryIsKnown"
                  >
                    <div class="md-layout-item md-subheader md-size-20">
                      {{ $tc("phrases.smsDelivery") }}
                    </div>
                    <div class="md-layout-item md-subheader md-size-75">
                      <sms-delivery-status
                        class="delivery--flush"
                        :status="transaction.sms.status"
                        :error-message="transaction.sms.error_message"
                      />
                    </div>
                  </div>
                </md-card-content>
              </md-card>
            </widget>
          </div>
        </div>
        <div
          class="md-layout-item md-size-50 md-small-size-100"
          v-if="ot && ot.raw_message"
        >
          <div class="transaction-detail-card">
            <widget
              title="Incoming SMS"
              :show-spinner="false"
              color="secondary"
            >
              <md-card>
                <md-card-content>
                  <div class="md-layout md-gutter md-size-100">
                    <div class="md-layout-item md-subheader md-size-20">
                      {{ $tc("words.body") }}
                    </div>
                    <div
                      class="md-layout-item md-subheader md-size-75 message-box"
                    >
                      {{ ot.raw_message }}
                    </div>
                  </div>
                </md-card-content>
              </md-card>
            </widget>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import { currency } from "@/mixins/currency.js"
import { notify } from "@/mixins/notify.js"
import { timing } from "@/mixins/timing.js"
import { token } from "@/mixins/token.js"
import AgentTransactionDetail from "@/modules/Agent/AgentTransactionDetail.vue"
import CashTransactionDetail from "@/modules/Transactions/CashTransactionDetail.vue"
import PaymentHistoryChart from "@/modules/Transactions/PaymentHistoryChart.vue"
import SmsTransactionDetail from "@/modules/Transactions/SmsTransactionDetail.vue"
import { PersonService } from "@/services/PersonService.js"
import { TransactionService } from "@/services/TransactionService.js"
import SmsDeliveryStatus, {
  hasDeliveryStatus,
} from "@/shared/SmsDeliveryStatus.vue"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Transaction",
  mixins: [timing, currency, notify, token],
  components: {
    Widget,
    AgentTransactionDetail,
    CashTransactionDetail,
    SmsDeliveryStatus,
    SmsTransactionDetail,
    PaymentHistoryChart,
  },
  created() {
    this.transactionId = this.$route.params.id
  },
  mounted() {
    this.getDetail(this.transactionId)
  },
  data() {
    return {
      transactionService: new TransactionService(),
      personService: new PersonService(),
      transactionId: null,
      transaction: null,
      personName: null,
      personId: null,
      showCustomer: true,
    }
  },
  computed: {
    ot() {
      return this.transaction.original_transaction
    },
    // TransactionService pushes a placeholder entry when the backend returns none,
    // so the flag on the first entry is what says whether any payment was recorded.
    hasPaymentHistory() {
      return this.transaction?.payment_histories?.[0]?.paymentHistory === true
    },
    conflicts() {
      return this.ot?.conflicts ?? []
    },
    showConflicts() {
      return this.isCancelled || this.conflicts.length > 0
    },
    isCancelled() {
      return this.ot?.status === -1
    },
    // Three different situations land here and an operator acts on each differently:
    // the payment failed, or it settled and the customer got nothing for it, or the
    // provider reported something we could not match against what we recorded, which
    // leaves the transaction pending and the money unaccounted for.
    conflictHeadline() {
      if (this.isCancelled) {
        return this.$tc("phrases.transactionCancelled")
      }
      if (this.ot?.status === 1) {
        return this.$tc("phrases.paymentNotDelivered")
      }
      return this.$tc("phrases.paymentNeedsReview")
    },
    smsDeliveryIsKnown() {
      return hasDeliveryStatus(this.transaction.sms?.status)
    },
    providerDetail() {
      const transactionType = this.transaction.original_transaction_type
      switch (transactionType) {
        case "vodacom_mz_transaction":
          return "VodacomMzTransactionDetail"
        case "agent_transaction":
          return "AgentTransactionDetail"
        case "third_party_transaction":
          return "ThirdPartyTransactionDetail"
        case "wave_money_transaction":
          return "WaveMoneyTransactionDetail"
        case "swifta_transaction":
          return "SwiftaTransactionDetail"
        case "wavecom_transaction":
          return "WaveComTransactionDetail"
        case "paystack_transaction":
          return "PaystackTransactionDetail"
        case "flutterwave_transaction":
          return "FlutterwaveTransactionDetail"
        case "pesapal_transaction":
          return "PesapalTransactionDetail"
        case "safaricom_transaction":
          return "SafaricomTransactionDetail"
        case "mesomb_transactions":
          return "MesombTransactionDetail"
        case "cash_transaction":
          return "CashTransactionDetail"
        case "sms_transaction":
          return "SmsTransactionDetail"
        default:
          return null
      }
    },
    deviceType() {
      if (this.transaction.device && this.transaction.device.device_type) {
        return this.$tc(`words.${this.transaction.device.device_type}`)
      }
      if (this.transaction.appliance && this.transaction.appliance.appliance) {
        return this.transaction.appliance.appliance.name
      }
      return this.$tc("words.appliance")
    },
    deviceDisplay() {
      if (this.transaction.device) {
        return this.transaction.message
      }
      if (this.transaction.appliance && this.transaction.appliance.appliance) {
        return this.transaction.appliance.appliance.name
      }
      return this.transaction.message !== "-"
        ? this.transaction.message
        : this.$tc("phrases.noDeviceAssigned")
    },
    isAdHoc() {
      return this.transaction.type === "ad_hoc"
    },
    transactionTypeLabel() {
      const labels = {
        energy: this.$tc("words.energy"),
        deferred_payment: this.$tc("phrases.deferredPayment"),
        eaas_rate: this.$tc("phrases.eaasRate"),
        down_payment: this.$tc("phrases.downPayment"),
        ad_hoc: this.$tc("phrases.adHoc"),
      }
      return labels[this.transaction.type] || this.transaction.type
    },
    isApplianceTransaction() {
      return (
        ["deferred_payment", "eaas_rate", "down_payment"].includes(
          this.transaction.type,
        ) &&
        this.transaction.original_transaction_type === "cash_transaction" &&
        !this.transaction.device
      )
    },
    applianceIdFromMessage() {
      const message = this.transaction.message
      if (message && message !== "-" && /^\d+$/.test(message)) {
        return parseInt(message, 10)
      }
      return null
    },
  },
  methods: {
    formatPaymentType(type) {
      const labels = {
        energy_service: this.$tc("phrases.eaasRate"),
        eaas_rate: this.$tc("phrases.eaasRate"),
        down_payment: this.$tc("phrases.downPayment"),
        installment: this.$tc("phrases.deferredPayment"),
        deferred_payment: this.$tc("phrases.deferredPayment"),
        energy: this.$tc("words.energy"),
        access_rate: this.$tc("phrases.accessRate"),
      }
      return labels[type] || type
    },
    async getDetail(id) {
      try {
        this.transaction = await this.transactionService.getTransaction(id)
        if (this.hasPaymentHistory) {
          await this.getRelatedPerson(
            this.transaction.payment_histories[0].payer_id,
          )

          return
        }
        this.setPersonFromDeviceOwner()
      } catch (e) {
        if (e.response && e.response.status === 403) {
          this.alertNotify(
            "error",
            "You do not have permission to view this transaction",
          )
          this.$router.push({ path: "/transactions" })
        } else {
          this.alertNotify("error", e.message)
        }
      }
    },
    setPersonFromDeviceOwner() {
      const owner = this.transaction.device?.person
      if (!owner) {
        return
      }

      this.personName = `${owner.name} ${owner.surname}`
      this.personId = owner.id
    },
    async getRelatedPerson(personId) {
      try {
        let person = await this.personService.getPerson(personId)
        this.personName = person.name + " " + person.surname
        this.personId = person.id
      } catch (e) {
        if (e.response && e.response.status === 403) {
          console.warn("Customer details: Insufficient permissions")
        } else {
          this.alertNotify("error", e.message)
        }
      }
    },
  },
}
</script>

<style scoped lang="scss">
.md-subheader.conflict-cancelled {
  color: #a81e10;
  font-weight: 500;
}

// Amber rather than red: the payment was not rejected, so this is something to
// reconcile rather than a transaction that is over.
.md-subheader.conflict-attention {
  color: #b26a00;
  font-weight: 500;
}

.conflict-state {
  white-space: normal;
}

.transaction-detail-card {
  margin-top: 1rem !important;
  margin-right: 1rem !important;
}

.n-font {
  font-weight: 100 !important;
}

// The code is read off this page and typed into the device, so it stays in full
// and in a font where a 0 cannot be mistaken for an O.
.issued-token {
  font-family: monospace;
  font-weight: 500 !important;
  white-space: normal;
  word-break: break-word;
}

.hr-d {
  height: 1pt;
  margin: auto;
  padding: 0;
  display: block;
  border: 0;
  /* transition: margin-left .3s cubic-bezier(.4,0,.2,1); */
  /* will-change: margin-left; */
  background-color: rgba(0, 0, 0, 0.12);
}

.message-box {
  padding: 10px;
  background-color: white;
  -moz-border-radius: 10px;
  border-radius: 14px;
  margin-top: 2vh;
}

p:first-letter {
  text-transform: capitalize;
}
</style>
