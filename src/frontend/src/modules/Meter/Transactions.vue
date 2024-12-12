<template>
  <widget
    v-if="transactions !== null"
    :title="$tc('phrases.meterTransaction')"
    class="col-sm-7"
    :id="'meter-transactions'"
    :paginator="transactions.paginator"
    :subscriber="subscriber"
    color="green"
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
            <md-table-cell
              v-text="moneyFormat(token.transaction.amount)"
            ></md-table-cell>
            <md-table-cell v-if="token.paid_for_type === 'App\\Models\\Token'">
              Token ({{ formatToken(token.paid_for.token) }})
            </md-table-cell>
            <md-table-cell v-else>
              {{ token.paid_for_type }}
            </md-table-cell>
            <md-table-cell
              v-if="token.paid_for_type === 'App\\Models\\Token'"
              v-text="readable(token.paid_for.energy) + ' kWh'"
            ></md-table-cell>
            <md-table-cell v-else>-</md-table-cell>
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
import Widget from "../../shared/widget"
import { EventBus } from "@/shared/eventbus"
import { currency } from "@/mixins/currency"
import { timing } from "@/mixins/timing"
import { token } from "@/mixins/token"

export default {
  name: "Transactions.vue",
  mixins: [currency, timing, token],
  components: { Widget },
  props: {
    transactions: {
      type: Object,
    },
  },
  computed: {
    transactionType: () => {
      return this.token.transaction.original_transaction_type
    },
  },
  created() {
    EventBus.$on("pageLoaded", this.reloadList)
  },
  data() {
    return {
      subscriber: "meter.transactions",
      headers: [
        this.$tc("words.provider"),
        this.$tc("words.amount"),
        this.$tc("phrases.paidFor"),
        this.$tc("phrases.inReturn"),
        this.$tc("words.date"),
      ],
      tableName: "Meter Transactions",
    }
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) {
        return
      }
      this.transactions.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.transactions.tokens.length,
      )
    },
  },
}
</script>

<style scoped></style>
