<template>
  <widget
    :class="'col-sm-6 col-md-5'"
    :title="$tc('phrases.lastTransactions')"
    color="green"
    :paginator="agentTransactionService.paginator"
    :subscriber="subscriber"
  >
    <md-table md-sort="id" md-sort-order="asc">
      <md-table-row>
        <md-table-head v-for="(item, index) in headers" :key="index">
          {{ item }}
        </md-table-head>
      </md-table-row>
      <md-table-row
        v-for="(item, index) in agentTransactionService.list"
        :key="index"
      >
        <md-table-cell md-sort-by="id" md-label="ID">
          {{ item.id }}
        </md-table-cell>
        <md-table-cell md-label="Amount">
          {{ moneyFormat(item.amount) }}
        </md-table-cell>
        <md-table-cell md-label="Device">
          {{ item.meter }}
        </md-table-cell>
        <md-table-cell md-label="Customer">
          {{ item.customer }}
        </md-table-cell>
        <md-table-cell md-label="Date">
          {{ item.createdAt }}
        </md-table-cell>
      </md-table-row>
    </md-table>
  </widget>
</template>
<script>
import Widget from "@/shared/Widget.vue"
import { AgentTransactionService } from "@/services/AgentTransactionService"
import { EventBus } from "@/shared/eventbus"
import { currency } from "@/mixins/currency"

export default {
  name: "AgentTransactionList",
  mixins: [currency],
  data() {
    return {
      subscriber: "agent-transactions",
      agentTransactionService: new AgentTransactionService(this.agentId),
      headers: [
        this.$tc("words.id"),
        this.$tc("words.amount"),
        this.$tc("words.device"),
        this.$tc("words.customer"),
        this.$tc("words.date"),
      ],
    }
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
  },
  beforeDestroy() {
    EventBus.$off("pageLoaded", this.reloadList)
  },
  components: {
    Widget,
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) {
        return
      }
      this.agentTransactionService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.agentTransactionService.list.length,
      )
    },
  },
  props: {
    agentId: {
      default: null,
    },
  },
}
</script>
<style scoped></style>
