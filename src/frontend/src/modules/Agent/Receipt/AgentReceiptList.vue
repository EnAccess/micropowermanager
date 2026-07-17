<template>
  <div>
    <new-receipt :addNewReceipt="showNewReceipt" :agent="agent" />
    <widget
      :class="'col-sm-12'"
      :button-text="$tc('phrases.addReceipt', 0)"
      :button="true"
      :title="$tc('words.receipt')"
      @widgetAction="newReceipt"
      :paginator="agentReceiptService.paginator"
      :subscriber="subscriber"
      :resetKey="resetKey"
      color="primary"
    >
      <md-table md-sort="id" md-sort-order="asc">
        <md-table-row>
          <md-table-head v-for="(item, index) in headers" :key="index">
            {{ item }}
          </md-table-head>
        </md-table-row>
        <md-table-row v-for="item in agentReceiptService.list" :key="item.id">
          <md-table-cell md-sort-by="id" md-label="ID">
            {{ item.id }}
          </md-table-cell>
          <md-table-cell md-label="Amount">
            {{ moneyFormat(item.amount) }}
          </md-table-cell>
          <md-table-cell md-label="Commission Credited">
            {{ moneyFormat(item.commissionCredited) }}
          </md-table-cell>
          <md-table-cell md-label="Due at Visit">
            {{ moneyFormat(item.dueAtVisit) }}
          </md-table-cell>
          <md-table-cell md-label="Collected Since Last Visit">
            {{ moneyFormat(item.collectedSinceLastVisit) }}
          </md-table-cell>
          <md-table-cell md-label="Receiver">
            {{ item.receiver }}
          </md-table-cell>
          <md-table-cell md-label="Date">
            {{ item.createdAt }}
          </md-table-cell>
        </md-table-row>
      </md-table>
    </widget>
  </div>
</template>
<script>
import NewReceipt from "./NewReceipt.vue"

import { currency } from "@/mixins/currency.js"
import { notify } from "@/mixins/notify.js"
import { AgentReceiptService } from "@/services/AgentReceiptService.js"
import { AgentService } from "@/services/AgentService.js"
import { EventBus } from "@/shared/eventbus.js"
import Widget from "@/shared/Widget.vue"

export default {
  name: "AgentReceiptList",
  mixins: [notify, currency],
  data() {
    return {
      subscriber: "agent-receipts",
      showNewReceipt: false,
      agent: {},
      agentReceiptService: new AgentReceiptService(this.agentId),
      agentService: new AgentService(),
      resetKey: 0,
      headers: [
        this.$tc("words.id"),
        this.$tc("words.amount"),
        this.$tc("phrases.commissionCredited"),
        this.$tc("phrases.dueAtVisit"),
        this.$tc("phrases.collectedSinceLastVisit"),
        this.$tc("words.receiver"),
        this.$tc("words.date"),
      ],
      tableName: "Agent Receipt",
    }
  },
  components: {
    NewReceipt,
    Widget,
  },
  props: {
    agentId: {
      default: null,
    },
  },
  mounted() {
    EventBus.$on("pageLoaded", this.reloadList)
    EventBus.$on("receiptAdded", this.closeNewReceipt)
    EventBus.$on("newReceiptClosed", () => {
      this.showNewReceipt = false
    })
  },
  beforeDestroy() {
    EventBus.$off("receiptAdded", this.closeNewReceipt)
    EventBus.$off("pageLoaded", this.reloadList)
  },
  methods: {
    reloadList(subscriber, data) {
      if (subscriber !== this.subscriber) return
      this.agentReceiptService.updateList(data)
      EventBus.$emit(
        "widgetContentLoaded",
        this.subscriber,
        this.agentReceiptService.list.length,
      )
    },
    async closeNewReceipt() {
      this.showNewReceipt = false
      this.resetKey += 1
    },
    async newReceipt() {
      await this.getAgentDetail()
      this.showNewReceipt = true
    },
    async getAgentDetail() {
      try {
        this.agent = await this.agentService.getAgent(this.agentId)
      } catch (e) {
        this.alertNotify("error", e.message)
      }
    },
  },
}
</script>
<style scoped lang="scss"></style>
