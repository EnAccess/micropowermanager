<template>
  <widget
    v-if="transactions !== null"
    :title="$tc('phrases.meterTransaction')"
    class="col-sm-7"
    :id="'meter-transactions'"
    :paginator="transactions.paginator"
    :subscriber="subscriber"
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
          <md-table-row
            v-for="transaction in transactions.list"
            :key="transaction.id"
          >
            <md-table-cell>
              {{
                transaction.type === "ad_hoc"
                  ? transactionTypeLabel(transaction.type)
                  : transaction.original_transaction_type
              }}
            </md-table-cell>
            <md-table-cell
              v-text="moneyFormat(transaction.amount)"
            ></md-table-cell>
            <md-table-cell>
              {{ tokenTypeLabel(transaction.token.token_type) }}
              ({{ formatToken(transaction.token.token) }})
            </md-table-cell>
            <md-table-cell v-if="transaction.token.token_amount">
              {{ readable(transaction.token.token_amount) }}
              {{ unitLabel(transaction.token.token_unit) }}
            </md-table-cell>
            <md-table-cell v-else>-</md-table-cell>
            <md-table-cell
              v-text="timeForTimeZone(transaction.created_at)"
            ></md-table-cell>
          </md-table-row>
        </md-table>
      </md-card-content>
    </md-card>
  </widget>
</template>

<script>
import { currency } from "@/mixins/currency.js"
import { timing } from "@/mixins/timing.js"
import { token } from "@/mixins/token.js"
import { transactionType } from "@/mixins/transactionType.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "Transactions.vue",
  mixins: [currency, timing, token, transactionType],
  components: { Widget },
  props: {
    transactions: {
      type: Object,
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
        this.transactions.list.length,
      )
    },
  },
}
</script>

<style scoped lang="scss"></style>
