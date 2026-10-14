<template>
  <widget
    v-if="transactions"
    :title="$tc('phrases.solarHomeSystemTransaction')"
    class="col-sm-7"
    :id="'shs-transactions'"
    :paginator="transactions.paginator"
    :subscriber="subscriber"
    :show_per_page="true"
    color="primary"
  >
    <md-card>
      <md-card-content>
        <md-table>
          <md-table-row>
            <md-table-head v-for="(item, index) in headers" :key="index">
              {{ item }}
            </md-table-head>
          </md-table-row>
          <md-table-row v-for="token in transactions.tokens" :key="token.id">
            <md-table-cell
              v-text="token.transaction.original_transaction_type"
            ></md-table-cell>
            <md-table-cell v-text="moneyFormat(token.amount)"></md-table-cell>
            <md-table-cell v-if="token.paid_for_type === 'App\\Models\\Token'">
              Token ({{ formatToken(token.paid_for.token) }})
            </md-table-cell>
            <md-table-cell v-else>
              {{ token.paid_for_type }}
            </md-table-cell>
            <md-table-cell>
              <template v-if="paymentToken(token)">
                {{ formatToken(paymentToken(token).token) }}
                <md-button
                  class="md-icon-button"
                  :aria-label="$tc('phrases.copyToken')"
                  @click.stop="copyToken(paymentToken(token).token)"
                >
                  <md-icon>content_copy</md-icon>
                  <md-tooltip md-direction="top">
                    {{ $tc("phrases.copyToken") }}
                  </md-tooltip>
                </md-button>
              </template>
              <template v-else>-</template>
            </md-table-cell>
            <md-table-cell>
              {{ tokenCredit(paymentToken(token)) }}
            </md-table-cell>
            <md-table-cell>
              <template v-if="token.transaction?.token_issuance">
                {{ token.transaction.token_issuance.status }}
                <div>{{ token.transaction.token_issuance.message }}</div>
              </template>
              <template v-else>-</template>
            </md-table-cell>
            <md-table-cell
              v-text="timeForTimeZone(token.created_at)"
            ></md-table-cell>
          </md-table-row>
        </md-table>
      </md-card-content>
    </md-card>
  </widget>
</template>

<script>
import { currency } from "@/mixins/currency.js"
import { notify } from "@/mixins/notify.js"
import { timing } from "@/mixins/timing.js"
import { token } from "@/mixins/token.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "SolarHomeSystemTransactions",
  mixins: [currency, timing, token, notify],
  components: { Widget },
  props: {
    transactions: {
      type: Object,
      required: true,
    },
  },
  created() {
    EventBus.$on("pageLoaded", this.reloadList)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  data() {
    return {
      subscriber: "shs.transactions",
      headers: [
        this.$tc("words.provider"),
        this.$tc("words.amount"),
        this.$tc("phrases.paidFor"),
        this.$tc("words.token"),
        this.$tc("phrases.inReturn"),
        this.$tc("words.status"),
        this.$tc("words.date"),
      ],
      tableName: "Solar Home System Transactions",
    }
  },
  methods: {
    paymentToken(payment) {
      return (
        payment.transaction?.token ||
        (["App\\Models\\Token", "token"].includes(payment.paid_for_type)
          ? payment.paid_for
          : null)
      )
    },
    tokenCredit(paymentToken) {
      if (!paymentToken) return "-"
      if (!paymentToken.token_unit)
        return this.tokenTypeLabel(paymentToken.token_type)

      const amount =
        paymentToken.token_unit === "kWh"
          ? this.readable(paymentToken.token_amount)
          : paymentToken.token_amount
      return `${amount} ${this.unitLabel(paymentToken.token_unit)}`
    },
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) {
        return
      }
      this.transactions.updateList(data)
      this.$forceUpdate()
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.transactions.tokens.length,
      )
      this.$emit("widget-loaded", "transactions")
    },
  },
}
</script>

<style scoped lang="scss"></style>
