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
              color="green"
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
                      <span
                        v-text="
                          transaction.type === 'energy'
                            ? $tc('words.energy')
                            : $tc('phrases.deferredPayment')
                        "
                      ></span>
                      <div style="margin-left: 0.2em">
                        <small
                          v-if="
                            transaction.type === 'energy' && transaction.token
                          "
                        >
                          ({{ readable(transaction.token.load) }}kWh)
                        </small>
                        <small
                          v-else-if="
                            transaction.type === 'deferred_payment' &&
                            transaction.token
                          "
                        >
                          ({{ readable(transaction.token.load) }}day's)
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
                      {{ $tc(`words.${transaction.device.device_type}`) }}
                    </div>
                  </div>
                  <hr class="hr-d" />
                  <div class="md-layout">
                    <div class="md-layout-item md-subheader">
                      {{ $tc("words.device") }}
                    </div>
                    <div
                      class="md-layout-item md-subheader n-font"
                      v-if="
                        transaction.payment_histories[0].paymentHistory &&
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
                        transaction.payment_histories[0].paymentHistory &&
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
                    <div class="md-layout-item md-subheader n-font" v-else>
                      {{ transaction.message }}
                    </div>
                  </div>
                  <hr class="hr-d" />
                  <div class="md-layout">
                    <div class="md-layout-item md-subheader">
                      {{ $tc("words.customer") }}
                    </div>
                    <div
                      class="md-layout-item md-subheader n-font"
                      v-if="transaction.payment_histories[0].paymentHistory"
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
        <div class="md-layout-item md-size-50 md-small-size-100">
          <div class="transaction-detail-card">
            <widget
              title="Transaction Processing"
              :show-spinner="false"
              color="green"
            >
              <md-card>
                <div
                  v-if="
                    transaction.original_transaction_type ===
                    'third_party_transaction'
                  "
                >
                  <md-card-content>
                    <div class="md-layout md-gutter md-size-100">
                      <ul style="margin: auto">
                        <li>
                          {{ $tc("phrases.untraceableTransaction") }}
                        </li>
                      </ul>
                    </div>
                  </md-card-content>
                </div>
                <div v-else>
                  <md-card-content v-if="ot.status === 1">
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
                        <md-table
                          v-if="transaction.payment_histories[0].paymentHistory"
                        >
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
                                {{ p.payment_type }}
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
                  <md-card-content v-if="ot.status === -1">
                    <h2>Transaction cancelled</h2>
                    <md-list class="md-double-line">
                      <md-subheader style="color: #a81e10">
                        {{ $tc("phrases.transactionCancelled") }}
                      </md-subheader>

                      <md-list-item
                        :key="conflict.id"
                        v-for="conflict in ot.conflicts"
                      >
                        <span class="margin-top-5">
                          {{ conflict.state }}
                        </span>
                      </md-list-item>
                    </md-list>
                  </md-card-content>
                </div>
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
              color="red"
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
import { timing } from "@/mixins/timing"
import { currency } from "@/mixins/currency"
import PaymentHistoryChart from "@/modules/Transactions/PaymentHistoryChart"
import AgentTransactionDetail from "@/modules/Agent/AgentTransactionDetail"
import Widget from "@/shared/Widget.vue"
import { TransactionService } from "@/services/TransactionService"
import { PersonService } from "@/services/PersonService"

import { notify } from "@/mixins/notify"

export default {
  name: "Transaction",
  mixins: [timing, currency, notify],
  components: {
    Widget,
    AgentTransactionDetail,
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
    providerDetail() {
      const transactionType = this.transaction.original_transaction_type
      switch (transactionType) {
        case "vodacom_transaction":
          return "VodacomTransactionDetail"
        case "airtel_transaction":
          return "AirtelTransactionDetail"
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
        default:
          return null
      }
    },
  },
  methods: {
    async getDetail(id) {
      try {
        this.transaction = await this.transactionService.getTransaction(id)
        console.log(this.transaction)
        if (this.transaction.payment_histories[0].paymentHistory === true) {
          await this.getRelatedPerson(
            this.transaction.payment_histories[0].payer_id,
          )
        }
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
    async getRelatedPerson(personId) {
      try {
        let person = await this.personService.getPerson(personId)
        this.personName = person.name + " " + person.surname
        this.personId = person.id
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>

<style scoped>
.transaction-detail-card {
  margin-top: 1rem !important;
  margin-right: 1rem !important;
}

.n-font {
  font-weight: 100 !important;
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
  background-color: #f5e8e8;
  -moz-border-radius: 10px;
  border-radius: 14px;
  margin-top: 2vh;
}

p:first-letter {
  text-transform: capitalize;
}
</style>
